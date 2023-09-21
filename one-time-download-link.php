<?php
/**
 * Plugin Name: One Time Download Link for WPForms
 * Description: Generate a unique one-time download link for a file in the media library through WPForms..
 * Version: 1.0
 * Author: Jordan Hall
 * Author URI: https://libertyware.co.uk/
 **/


require_once __DIR__ . '/vendor/autoload.php';

use Aws\S3\S3Client;

use OTDL\Database\Migrations;
use OTDL\WPForms\LinkGenerator;
use OTDL\WPForms\Shortcode;
use OTDL\WPForms\Notifier;
use OTDL\Admin\Menu;
use OTDL\Admin\Scripts;
use OTDL\Admin\BackupRestore;
use OTDL\Admin\Settings;
use OTDL\Utilities\Logger;

$container = new DI\Container();

global $wpdb;
$container->set(wpdb::class, $wpdb);
$container->set(Logger::class, DI\autowire()->constructorParameter('wpdb', DI\get(wpdb::class)));
$container->set(LinkGenerator::class, DI\autowire()->constructorParameter('logger', $container->get(Logger::class)));
$container->set(Settings::class, DI\autowire()
    ->constructorParameter('wpdb', DI\get(wpdb::class))
    ->constructorParameter('linkGenerator', DI\get(LinkGenerator::class)));
$container->set(Menu::class, DI\autowire()->constructorParameter('wpdb', DI\get(wpdb::class)));
$container->set(BackupRestore::class, DI\autowire()->constructorParameter('wpdb', DI\get(wpdb::class)));
$container->set(Migrations::class, DI\autowire()->constructorParameter('wpdb', DI\get(wpdb::class)));


// Initializing necessary services
$migrations = $container->get(Migrations::class);

// Run migrations upon plugin activation
register_activation_hook(__FILE__, [$migrations, 'run']);

// Registering and initializing other functionalities
$classesToInit = [
    $container->get(Shortcode::class),
    $container->get(Notifier::class),
    $container->get(Menu::class),
    $container->get(Scripts::class),
    $container->get(BackupRestore::class),
    $container->get(Settings::class)
];

foreach ($classesToInit as $classInstance) {
    if (method_exists($classInstance, 'register')) {
        $classInstance->register();
    }
}

function otdl_register_smart_tag($tags)
{
    $tags['download_link'] = 'Download Link';
    return $tags;
}
add_filter('wpforms_smart_tags', 'otdl_register_smart_tag');

// Process and replace the smart tag value using LinkGenerator
function otdl_process_smart_tag($content, $form_data, $fields, $entry_id)
{
    // If content does not contain the smart tag, return original content without processing
    if (strpos($content, '{download_link}') === false) {
        return $content;
    }

    global $container; // Use the DI container already set up.
    $linkGenerator = $container->get(LinkGenerator::class);
    $logger = $container->get(Logger::class);

    $download_link = $linkGenerator->generate($fields, $form_data);

    // Replace the smart tag in the content
    $content = str_replace('{download_link}', $download_link, $content);
    $logger->addLog('debug', "Download link before replacement: $download_link");
    return $content;
}

add_filter('wpforms_process_smart_tags', 'otdl_process_smart_tag', 10, 4);

add_action('init', 'otdl_handle_download_request');

function otdl_handle_download_request() {
    global $wpdb, $container;
    $logger = $container->get(Logger::class);

    if (!isset($_GET['otdl_key']) || !isset($_GET['_wpnonce'])) {
        return;
    }

    if (!wp_verify_nonce($_GET['_wpnonce'], 'otdl_download_nonce')) {
        wp_die('Invalid request.');
    }

    $table_name = $wpdb->prefix . 'otdl_links';
    $row = $wpdb->get_row($wpdb->prepare("SELECT file_id, downloaded FROM $table_name WHERE unique_key = %s", $_GET['otdl_key']));

    if (!$row) {
        wp_die('File not found.');
    }

    if ($row->downloaded !== '0000-00-00 00:00:00') {
        wp_die('This download link has already been used.');
    }

    $file_url = wp_get_attachment_url($row->file_id);
    if (!$file_url) {
        wp_die('File URL not found.');
    }

    $aws_settings = defined('OTDL_AWS_SETTINGS') ? unserialize(OTDL_AWS_SETTINGS) : [];
    $offload_settings = get_option('as3cf_settings');
    $cdn_domain = $aws_settings['cdn_domain'] ?? ($offload_settings['cloudfront'] ?? '');

    $logger->addLog('info', 'CDN Domain Value: ' . $cdn_domain);

    if (strpos($file_url, 's3.amazonaws.com') !== false || ($cdn_domain && strpos($file_url, $cdn_domain) !== false)) {
        try {
            $s3 = new S3Client([
                'version' => 'latest',
                'region' => $aws_settings['region'] ?? $offload_settings['region'],
                'credentials' => [
                    'key' => $aws_settings['key'] ?? $offload_settings['access-key-id'],
                    'secret' => $aws_settings['secret'] ?? $offload_settings['secret-access-key']
                ]
            ]);
            
            $cmd = $s3->getCommand('GetObject', [
                'Bucket' => $aws_settings['bucket'] ?? $offload_settings['bucket'],
                'Key' => basename($file_url)
            ]);

            $file_url = $s3->createPresignedRequest($cmd, '+1 hour')->getUri();
        } catch (Exception $e) {
            $logger->addLog('error', 'Exception while generating presigned URL: ' . $e->getMessage());
        }
    }

    $logger->addLog('info', 'Actual file to download: ' . $file_url);
    
    $content = @file_get_contents($file_url);
    $headers = @get_headers($file_url, 1);
    if ($content === false) {
        $error = error_get_last();
        $logger->addLog('error', 'Failed to retrieve file content: ' . $error['message']);

        $ch = curl_init($file_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_REFERER, get_site_url());
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        $content = curl_exec($ch);

        if ($content === false) {
            $curl_error = curl_error($ch);
            $logger->addLog('error', 'cURL Error: ' . $curl_error);
            wp_die('Unable to fetch the file. Please contact support.');
        }

        curl_close($ch);
    }

    $wpdb->update(
        $table_name,
        array('downloaded' => current_time('mysql')),
        array('unique_key' => $_GET['otdl_key'])
    );

    header("Content-Disposition: attachment; filename=" . basename($file_url));
    header("Content-Type: application/octet-stream");
    echo $content;
    exit;
}





?>