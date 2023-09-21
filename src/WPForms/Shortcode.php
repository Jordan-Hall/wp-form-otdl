<?php
namespace OTDL\WPForms;

class Shortcode {
    public function register() {
        add_shortcode('otdl_link', array($this, 'render'));
    }
    

    public function render($atts) {
        $atts = shortcode_atts(array('key' => ''), $atts, 'otdl_link');
        if (!$atts['key']) return '';
        return home_url("?otdl_key={$atts['key']}&_wpnonce=" . wp_create_nonce('otdl_download_nonce'));
    }
}
?>