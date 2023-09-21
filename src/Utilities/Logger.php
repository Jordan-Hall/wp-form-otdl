<?php 

namespace OTDL\Utilities;

use wpdb;

class Logger {
    private $wpdb;

    public function __construct(wpdb $wpdb) {
        $this->wpdb = $wpdb;
    }

    public function addLog($type, $message) {
        $logs_table_name = $this->wpdb->prefix . 'otdl_logs';
        $this->wpdb->insert($logs_table_name, array(
            'log_type' => $type,
            'message' => $message,
            'timestamp' => current_time('mysql')
        ));
    }
}
?>