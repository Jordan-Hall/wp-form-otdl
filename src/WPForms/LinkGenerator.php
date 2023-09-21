<?php
namespace OTDL\WPForms;

use Aws\S3\S3Client;
use OTDL\Utilities\Logger;

class LinkGenerator
{
    private $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function generate($fields, $form_data)
    {
        $this->logger->addLog('debug', 'Fields received: ' . json_encode($fields));
        $this->logger->addLog('debug', 'form_data received: ' . json_encode($form_data));
        global $wpdb;

        if (!function_exists('wpforms')) {
            $this->logger->addLog('error', 'WPForms is not active or not installed.');
            return $fields . '<br><strong>Error:</strong> WPForms is not active or not installed.';
        }

        $form_metadata_config = !empty($form_data['settings']['otdl_meta_fields']) ? explode(',', $form_data['settings']['otdl_meta_fields']) : array();
        $meta_data = array();

        foreach ($form_metadata_config as $field_name) {
            $field_name = trim($field_name);
            if (isset($fields[$field_name])) {
                $meta_data[$field_name] = sanitize_text_field($fields[$field_name]);
            }
        }
        $file_id = 0;

        if (isset($fields['file_id'])) {
            if (strpos($fields['file_id'], 'file_id:') === 0) {
                $file_id = intval(str_replace('file_id:', '', $fields['file_id']));
            } else {
                $file_id = intval($fields['file_id']);
            }
        } else {
            foreach ($fields as $key => $value) {
                if (strpos($value['value'], 'file_id:') === 0) {
                    $file_id = intval(str_replace('file_id:', '', $value['value']));
                    break;
                }
            }
        }

        if (!$file_id) {
            $this->logger->addLog('debug', 'Extracted file_id: ' . $file_id);
            $this->logger->addLog('error', 'file_id not found');
            $fields_json = json_encode($fields);
            $this->logger->addLog('debug', "Fields when error occurred: $fields_json");

            return '';
        }


        $unique_key = wp_generate_uuid4();
        $download_nonce = wp_create_nonce('otdl_download_nonce');
        $creation_time = current_time('mysql');

        $table_name = $wpdb->prefix . 'otdl_links';
        $inserted = $wpdb->insert(
            $table_name,
            array(
                'file_id' => $file_id,
                'unique_key' => $unique_key,
                'meta_data' => json_encode($meta_data),
                'downloaded' => '0000-00-00 00:00:00',
                'creation_time' => $creation_time
            )
        );

        if (!$inserted) {
            $this->logger->addLog('error', 'Failed to generate a download link.');
            return $fields . '<br><strong>Error:</strong> Failed to generate a download link. Please try again.';
        }

        $download_link = '';

        if (get_option('aws_enabled')) {
            $aws_settings = defined('OTDL_AWS_SETTINGS') ? unserialize(OTDL_AWS_SETTINGS) : [];

            $s3 = new S3Client([
                'version' => 'latest',
                'region' => $aws_settings['region'] ?? get_option('aws_region'),
                'credentials' => [
                    'key' => $aws_settings['key'] ?? get_option('aws_access_key'),
                    'secret' => $aws_settings['secret'] ?? get_option('aws_secret_key'),
                ]
            ]);

            $file_url = wp_get_attachment_url($file_id);
            $parsed_url = parse_url($file_url);
            $s3_key = ltrim($parsed_url['path'], '/');

            // Generate a presigned URL
            $cmd = $s3->getCommand('GetObject', [
                'Bucket' => get_option('aws_bucket'),
                'Key' => $s3_key // specify the file path in the bucket
            ]);

            $download_link = $s3->createPresignedRequest($cmd, '+1 hour')->getUri();
        } else {
            $download_link = home_url("?otdl_key=$unique_key&_wpnonce=$download_nonce");
        }
        $this->logger->addLog('info', "Generated a new download link: $download_link");

        return $download_link;
    }
}
?>