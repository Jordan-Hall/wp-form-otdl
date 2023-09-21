<?php
namespace OTDL\Admin;

use wpdb;

class Menu {
    private $wpdb;
    const LINKS_PER_PAGE = 20; // Adjust as needed

    public function __construct(wpdb $wpdb) {
        $this->wpdb = $wpdb;
    }

    public function register() {
        add_action('admin_menu', array($this, 'addMenu'));
    }

    public function addMenu() {
        add_management_page('Manage Download Links', 'Download Links', 'manage_options', 'otdl_manage_links', array($this, 'renderPage'));
    }

    public function renderPage() {
        $table_name = $this->wpdb->prefix . 'otdl_links';
        $search_term = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        
        $links = $this->wpdb->get_results($this->wpdb->prepare("SELECT id, file_id, unique_key, downloaded FROM $table_name WHERE file_id LIKE %s OR unique_key LIKE %s", '%' . $search_term . '%', '%' . $search_term . '%'));

        echo '<h2>' . esc_html__('Manage Download Links', 'otdl') . '</h2>';
        echo '<form method="get" action=""><input type="hidden" name="page" value="otdl_manage_links"><input type="search" name="s" value="' . esc_attr($search_term) . '"><input type="submit" value="' . esc_attr__('Search', 'otdl') . '"></form>';

        echo '<table border="1" cellspacing="2" cellpadding="5">';
        echo '<thead><tr><th>File ID</th><th>Unique Key</th><th>Downloaded</th><th>Action</th></tr></thead>';
        echo '<tbody>';
        foreach ($links as $link) {
            echo '<tr>';
            echo '<td>' . esc_html($link->file_id) . '</td>';
            echo '<td>' . esc_html($link->unique_key) . '</td>';
            echo '<td>' . esc_html($link->downloaded) . '</td>';
            echo '<td><a href="' . admin_url('tools.php?page=otdl_manage_links&invalidate=' . $link->unique_key) . '">Invalidate</a></td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';

        if (isset($_GET['invalidate'])) {
            $unique_key = sanitize_text_field($_GET['invalidate']);
            $this->wpdb->update($table_name, array('downloaded' => current_time('mysql')), array('unique_key' => $unique_key));
            echo '<p>Link invalidated successfully!</p>';
        }
    }
}

?>