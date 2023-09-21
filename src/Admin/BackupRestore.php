<?php
namespace OTDL\Admin;

use wpdb;

class BackupRestore {
    private $wpdb;

    public function __construct(wpdb $wpdb) {
        $this->wpdb = $wpdb;
    }

    public function register() {
        add_action('admin_menu', array($this, 'addSubmenu'));
    }

    public function addSubmenu() {
        add_submenu_page('tools.php', 'Backup OTDL Data', 'Backup OTDL', 'manage_options', 'otdl_backup', array($this, 'renderPage'));
    }

    public function renderPage() {
        if (isset($_POST['backup'])) {
            $this->backup();
        } elseif (isset($_FILES['restore_file'])) {
            $this->restore();
        }
        
        echo '<div class="wrap">';
        echo '<h2>Backup and Restore OTDL Data</h2>';
        echo '<h3>Backup</h3>';
        echo '<form method="post" action="">';
        echo '<input type="submit" name="backup" value="Download Backup" class="button">';
        echo '</form>';
        echo '<h3>Restore</h3>';
        echo '<form method="post" action="" enctype="multipart/form-data">';
        echo '<input type="file" name="restore_file" accept=".csv">';
        echo '<input type="submit" name="restore" value="Upload and Restore" class="button">';
        echo '</form>';
        echo '</div>';
    }

    private function backup() {
        $table_name = $this->wpdb->prefix . 'otdl_links';
        $rows = $this->wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename=otdl_backup.csv');

        $fp = fopen('php://output', 'w');
        $header_present = false;
        foreach ($rows as $row) {
            if (!$header_present) {
                fputcsv($fp, array_keys($row));
                $header_present = true;
            }
            fputcsv($fp, $row);
        }
        fclose($fp);
        exit;
    }

    private function restore() {
        $table_name = $this->wpdb->prefix . 'otdl_links';
        $file = $_FILES['restore_file']['tmp_name'];
        $handle = fopen($file, 'r');
        $headers = fgetcsv($handle);

        $this->wpdb->query("TRUNCATE TABLE $table_name");

        while (($data = fgetcsv($handle)) !== FALSE) {
            $this->wpdb->insert($table_name, array_combine($headers, $data));
        }
        fclose($handle);
        echo '<div class="updated"><p>Data restored successfully!</p></div>';
    }
}
?>