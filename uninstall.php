<?php

// if uninstall.php is not called by WordPress, die
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    die;
}

global $wpdb;
$plugin_table = $wpdb->base_prefix . "quetzal_simple_html_search";
$sql = "DROP TABLE IF EXISTS `{$plugin_table}`";
$wpdb->query($sql);

?>