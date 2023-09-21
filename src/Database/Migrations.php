<?php
namespace OTDL\Database;

use wpdb;

class Migrations {
    private $wpdb;

    public function __construct(wpdb $wpdb) {
        $this->wpdb = $wpdb;
    }

    public function run() {
        $charset_collate = $this->wpdb->get_charset_collate();
        $links_table_name = $this->wpdb->prefix . 'otdl_links';
        $logs_table_name = $this->wpdb->prefix . 'otdl_logs';

        $links_sql = "CREATE TABLE $links_table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            file_id mediumint(9) NOT NULL,
            unique_key varchar(55) DEFAULT '' NOT NULL,
            meta_data text DEFAULT NULL,
            downloaded datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            creation_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        $logs_sql = "CREATE TABLE $logs_table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            log_type varchar(55) DEFAULT '' NOT NULL,
            message text DEFAULT NULL,
            timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($links_sql);
        dbDelta($logs_sql);
    }
}
?>