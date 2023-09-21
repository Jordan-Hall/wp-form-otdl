<?php
namespace OTDL\Admin;


class Scripts {

    public function __construct() {
        $this->enqueue();
    }

    public function enqueue() {
        add_action('admin_enqueue_scripts', array($this, 'loadScripts'));
    }

    public function loadScripts($hook) {
        if ($hook != 'tools_page_otdl_manage_links') {
            return;
        }

        wp_enqueue_script('jquery');
        wp_enqueue_script('otdl-admin-js', plugins_url('../../Assets/js/admin.js', __FILE__), array('jquery'), '1.0.0', true);
        wp_localize_script('otdl-admin-js', 'otdl_ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
        wp_enqueue_style('otdl-admin-css', plugins_url('../../Assets/css/admin.css', __FILE__));
    }
}
?>