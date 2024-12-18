<?php
/*
Plugin Name: Custom Product Management
Description: Use [product_management] shortcode to use this plugin to manage WooCommerce products from the front end.
Version: 1.0
Author: I-genesys Technology 
Author URI: https://www.i-genesys.com/
*/

if (!defined('ABSPATH')) exit;

define('CPM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('CPM_PLUGIN_URL', plugin_dir_url(__FILE__));



add_action('after_setup_theme', 'plugin_register_custom_image_size');
function plugin_register_custom_image_size()
{
    add_image_size('small_product_thumbnail', 90, 90, true);
}


function cpm_enqueue_scripts()
{
    wp_enqueue_style('cpm-style', CPM_PLUGIN_URL . 'assets/css/cpm-style.css');
    wp_enqueue_script(
        'cpm-ajax-script',
        CPM_PLUGIN_URL . 'assets/js/cpm_ajax.js',
        array('jquery'),
        '1.0',
        true
    );

    wp_localize_script(
        'cpm-ajax-script',
        'cpm_ajax',
        array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cpm_product_nonce')
        ),
    );
    wp_enqueue_script('cpm-script', CPM_PLUGIN_URL . 'assets/js/cpm-script.js', array('jquery'), null, true);


    wp_enqueue_style('datatables-css', 'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css');

    wp_enqueue_script('jquery');

    wp_enqueue_script('datatables-js', 'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js', array('jquery'), null, true);
}
add_action('wp_enqueue_scripts', 'cpm_enqueue_scripts');




$required_files = [
    CPM_PLUGIN_PATH . 'includes/handle-ajax.php',
    CPM_PLUGIN_PATH . 'includes/product-form.php',
    CPM_PLUGIN_PATH . 'includes/product-list.php',

];

foreach ($required_files as $file) {
    if (file_exists($file)) {
        include_once $file;
    } else {
        error_log("File missing: $file");
    }
}

function cpm_product_management_shortcode()
{
    ob_start();

    if (isset($_GET['add_new'])) {
        cpm_display_product_form();
        cpm_display_product_list();
        echo '<a href="#" class="button cpm-add-new">Add New Product</a>';
    } elseif (isset($_GET['edit_product'])) {
        cpm_display_product_form();
        cpm_display_product_list();
    } else {
        cpm_display_product_form();
        cpm_display_product_list();
        echo '<a href="#" class="button cpm-add-new">Add New Product</a>';
    }

    return ob_get_clean();
}
add_shortcode('product_management', 'cpm_product_management_shortcode');


