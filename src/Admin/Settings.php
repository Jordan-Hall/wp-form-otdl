<?php
namespace OTDL\Admin;

use wpdb;
use OTDL\WPForms\LinkGenerator;

class Settings {
    private $wpdb;
    private $linkGenerator;

    public function __construct(wpdb $wpdb, LinkGenerator $linkGenerator) {
        $this->wpdb = $wpdb;
        $this->linkGenerator = $linkGenerator;
    }

    public function register() {
        add_action('admin_menu', [$this, 'addSettingsPage']);
    }

    public function addSettingsPage() {
        add_options_page('OTDL AWS Settings', 'OTDL AWS Settings', 'manage_options', 'otdl-aws-settings', [$this, 'displaySettings']);
    }


    public function displaySettings() {
        if (isset($_POST['aws_region'], $_POST['aws_access_key'], $_POST['aws_secret_key']) && wp_verify_nonce($_POST['_wpnonce'], 'otdl_aws_settings')) {
            update_option('otdl_aws_region', sanitize_text_field($_POST['aws_region']));
            update_option('otdl_aws_access_key', sanitize_text_field($_POST['aws_access_key']));
            update_option('otdl_aws_secret_key', sanitize_text_field($_POST['aws_secret_key']));
            echo "<div class='updated'><p>AWS settings updated successfully.</p></div>";
        }

        if (isset($_POST['delete_from_db']) && wp_verify_nonce($_POST['_wpnonce'], 'otdl_delete_aws_settings')) {
            delete_option('otdl_aws_region');
            delete_option('otdl_aws_access_key');
            delete_option('otdl_aws_secret_key');
            echo "<div class='updated'><p>AWS settings removed from database.</p></div>";
        }

        if (isset($_POST['file_name']) && wp_verify_nonce($_POST['_wpnonce'], 'otdl_aws_generate_link')) {
            $fields = ['file_id' => sanitize_text_field($_POST['file_name'])];
            $form_data = [];  // If you have any specific form data, pass it here.
            $generatedFields = $this->linkGenerator->generate($fields, $form_data);
            $presignedUrl = $generatedFields['{download_link}'] ?? '';

            echo "<div class='updated'><p>Generated Presigned URL: <a href='{$presignedUrl}' target='_blank'>Click here</a></p></div>";
        }

        $aws_region = get_option('otdl_aws_region', '');
        $aws_access_key = get_option('otdl_aws_access_key', '');
        $aws_secret_key = get_option('otdl_aws_secret_key', '');

        echo '<div class="wrap">';
        echo '<h2>OTDL AWS Settings</h2>';
        echo '<form method="post">';
        wp_nonce_field('otdl_aws_settings');
        echo '<label for="aws_region">AWS Region:</label>';
        echo '<input type="text" name="aws_region" value="' . esc_attr($aws_region) . '"><br>';
        echo '<label for="aws_access_key">AWS Access Key:</label>';
        echo '<input type="text" name="aws_access_key" value="' . esc_attr($aws_access_key) . '"><br>';
        echo '<label for="aws_secret_key">AWS Secret Key:</label>';
        echo '<input type="password" name="aws_secret_key" value="' . esc_attr($aws_secret_key) . '"><br>';
        echo '<input type="submit" value="Save AWS Settings">';
        echo '</form>';

        echo '<form method="post" style="margin-top: 20px;">';
        wp_nonce_field('otdl_delete_aws_settings');
        echo '<input type="hidden" name="delete_from_db" value="1">';
        echo '<input type="submit" value="Remove AWS Settings from Database">';
        echo '</form>';


        echo '<h3>Generate AWS Presigned URL</h3>';
        echo '<form method="post">';
        wp_nonce_field('otdl_aws_generate_link');
        echo '<label for="file_name">File Name in S3:</label>';
        echo '<input type="text" name="file_name" required><br>';
        echo '<input type="submit" value="Generate AWS Presigned URL">';
        echo '</form>';

        echo '</div>';

        echo '</div>';
    }
}
?>
