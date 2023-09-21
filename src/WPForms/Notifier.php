<?php
namespace OTDL\WPForms;

class Notifier {
    public function notify($file_id) {
        $admin_email = get_option('admin_email');
        $subject = 'File Downloaded!';
        $message = "File with ID $file_id has been downloaded.";
        wp_mail($admin_email, $subject, $message);
    }
}
?>