<?php

/*
    Plugin Name: CJ Poster Pro
    Plugin URI: http://www.cjproductposterpro.com
    Description:Comission Junction Product Poster Pro - Add CJ affiliate Products
    Author: W. Keller
    Version: 0.4
    Author URI: http://www.rellekdesigns.com
*/
/*
Copyright 2016  Rellek Designs  (email : contact@rellekdesigns.com)
This plugin is valid for licensed use only; you may not redistribute it and/or modify it under any circumstance without permission from us
*/


// Set up our WordPress Plugin
function cjpro_check_WP_ver()
{
   if ( version_compare( get_bloginfo( 'version' ), '4.1', '<' ) )
   {
      wp_die( "You must update WordPress to use this plugin!" );
   }
}
register_activation_hook( __FILE__, 'cjpro_check_WP_ver' );

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly


/*
 *  Set Globals
*/
$plugin_url = WP_PLUGIN_URL . '/cj-pro';
$options = array();
global $wpdb;

/*
 *  Enqueue Scripts
*/

function cjpro_load_scripts() {
      
    // load our jquery file that sends the $.post request
    wp_enqueue_script('jquery');
    wp_enqueue_script("jquery-ui-core");
    wp_enqueue_script("jquery-ui-tabs");
    wp_enqueue_script('jquery-ui-tooltip');
    wp_enqueue_script( 'jquery-ui-progressbar');  // the progress bar
    wp_enqueue_script( "cjpro-multizoom", plugin_dir_url( __FILE__ ) . 'js/multizoom.js', array( 'jquery' ) );
    wp_localize_script('cjpro-multizoom', 'cjproMultizoom', array(
    'pluginsUrl' => plugins_url(),
));
    wp_enqueue_script( "cjpro-lister", plugin_dir_url( __FILE__ ) . 'js/cjpro-js.js', array( 'jquery' ) );
   
    wp_localize_script('cjpro-lister', 'cjpro_options_nonce', 
        array(
            'cj_options_nonce'=> wp_create_nonce('options-nonce'),
            'cj_create_nonce'=> wp_create_nonce('create-nonce'),
            'cj_delete_nonce'=> wp_create_nonce('delete-nonce'),
            'cj_update_nonce'=> wp_create_nonce('update-nonce'),
            'cj_template_nonce'=> wp_create_nonce('template-nonce'),
            'cj_get_api_nonce'=> wp_create_nonce('cjpro-api-nonce')
            )
    );
 
 
}
add_action('admin_enqueue_scripts', 'cjpro_load_scripts');
add_action('wp_enqueue_scripts', 'cjpro_load_scripts');





function  cjpro_load_css_styles() {

    wp_register_style( 'cjpro-style-sheet', plugins_url( 'cj-pro/css/cjpro-style.css' ) );
    wp_enqueue_style( 'cjpro-style-sheet' );
    wp_register_style('jquery-custom-style', plugins_url('cj-pro/css/jquery-ui-fresh.css' )); 
    wp_enqueue_style('jquery-custom-style');
    wp_register_style('jquery-multizoom-style', plugins_url('cj-pro/css/multizoom.css' )); 
     wp_enqueue_style('jquery-multizoom-style');
}
add_action('admin_enqueue_scripts', 'cjpro_load_css_styles');
add_action('wp_enqueue_scripts', 'cjpro_load_css_styles');



/*
 *  Include Files
*/ 

include( 'inc/cj-activation.php');
include( 'inc/cj-options.php');
include( 'inc/cj-create.php');
include( 'inc/cj-display.php');
include( 'inc/cj-display-products.php');
include( 'inc/cj-single-display.php');
include( 'inc/cj-results.php');
include( 'inc/cj-template.php');
include( 'inc/cj-woo.php');
include( 'inc/cj-cron.php');


/*************************
*Plugin Setup - Create Admin Dashboard Links
*************************/


function cjpro_admin_pages(){
    $options = get_option( 'cjpro_settings' );
    if(!isset($options['cj_poster_pro_woo_check'])){
    $cjpro_woo_active = 0;
} else {
    $cjpro_woo_active = 1;
}
    
    add_menu_page('CJ PRO','CJ PRO','edit_users','cj-poster-pro','cj_pro_activation','dashicons-dashboard');
    add_submenu_page('cj-poster-pro','CJ Pro Options','Options','edit_users','cj-poster-pro-options','cjpro_options_page');
    if($cjpro_woo_active == 1){
     add_submenu_page('cj-poster-pro','WooCommerce Settings','WooCommerce','edit_users','cj-poster-pro-woo','cjpro_woo_page');
    }
    add_submenu_page('cj-poster-pro','Search API','Search API','edit_users','cj-poster-pro-search','cjpro_search_api');
    add_submenu_page('cj-poster-pro','Searches','Searches','edit_users','cj-poster-pro-display','cjpro_search_display');
    add_submenu_page(null, 'Single Campign Page', 'Single Campign Page', 'edit_users', 'cj-single-display', 'cjpro_single_campaign');
    add_submenu_page(null, 'Test', 'Test', 'edit_users', 'cj-results', 'cjpro_display_products');
    add_submenu_page('cj-poster-pro', 'Post Template', 'Post Template', 'edit_users', 'cj-template', 'cjpro_post_template');

	/*$cjpro_poster_options_page = add_submenu_page('cj-poster-pro','CJ Pro Plugin Options','Plugin Options','edit_users','cjpro-plugin-options','cjpro_option_page');*/
}


add_action('admin_menu', 'cjpro_admin_pages');


function cjpro_database_function(){
 global $wpdb;

        $cjpro_db_version = 2;
        $cjpro_db_version_check =  get_option('cjpro_db_ver');
        $cj_product_table_campaigns = $wpdb->prefix . "cj_pro_campaigns"; 
        $cj_product_table_templates = $wpdb->prefix . "cj_pro_templates";  
        $cj_product_table_errors = $wpdb->prefix . "cj_pro_errors";
        $cj_product_post_data = $wpdb->prefix . "cj_post_data";

            add_option('cj_last_post_time', '', '', 'yes' );

     if($cjpro_db_version_check != $cjpro_db_version) {

         if (!empty($wpdb->charset)) {
            $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
        }

        $sql[] = "CREATE TABLE ".$cj_product_table_campaigns." (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        website_id INT(7),
        advertiser_id INT(7) NOT NULL,
        keyword VARCHAR(255) NOT NULL,
        manufacturer VARCHAR(255) NOT NULL,
        low_price INT(7) NOT NULL,
        max_price INT(7) NOT NULL,
        sort_by VARCHAR(255) NOT NULL,
        sort_order INT(1) NOT NULL,
        post_category INT(4) NOT NULL,
        author_id INT(4) NOT NULL DEFAULT 1,
        max_product_returned INT(3) NOT NULL,
        last_run INT(10) NOT NULL,
        posts_returned INT(7) NOT NULL,
        cj_cron_name VARCHAR(255) NOT NULL,
        expandable4 longtext NOT NULL,
        expandable5 longtext NOT NULL,
        PRIMARY KEY (id)
        ) {$charset_collate};";

  $sql[] = "CREATE TABLE ".$cj_product_table_templates." (
        id BIGINT(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        type VARCHAR(255) NOT NULL,
        typenum INT(4) NOT NULL DEFAULT 1,  
        content longtext NOT NULL,
        img_viewer INT(4) NOT NULL DEFAULT 0, 
        post_ad_code longtext NOT NULL,
        post_ad_code2 longtext NOT NULL,
        post_ad_code3 longtext NOT NULL,
        post_ad_code4 longtext NOT NULL,
        left_sidebar_content longtext NOT NULL,
        right_sidebar_content longtext NOT NULL,
        full_width_content longtext NOT NULL
        ) {$charset_collate};";  
        
    $sql[] = "CREATE TABLE ".$cj_product_table_errors." (
        id BIGINT(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        campaign BIGINT(20) NOT NULL,
        keyword VARCHAR(255) NOT NULL,
        sort_option VARCHAR(255) NOT NULL,   
        reason VARCHAR(255) NOT NULL,           
        message longtext NOT NULL,
        time VARCHAR(255) NOT NULL,
        error_track longtext NOT NULL,
        api_error_array longtext NOT NULL,
        post_result tinyint(1) NOT NULL,
        expanded4 longtext NOT NULL
        ) {$charset_collate};"; 

        $sql[] = "CREATE TABLE ".$cj_product_post_data." (
        id BIGINT(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        cjpro_campaign_id INT(4) NOT NULL,
        cjpro_post_id INT(12) NOT NULL,
        ad_id INT(12) NOT NULL,,
        advertiser_id INT(12) NOT NULL,
        advertiser_name VARCHAR(255) NOT NULL,
        advertiser_cat VARCHAR(255) NOT NULL,
        cj_buy_url TEXT NOT NULL,
        cj_img_url TEXT NOT NULL,
        cj_product_name VARCHAR(255) NOT NULL,
        cj_product_man VARCHAR(255) NOT NULL,
        cj_product_currency VARCHAR(8) NOT NULL,
        cj_product_stock VARCHAR(5) NOT NULL,
        cj_product_retail_price DECIMAL(10,2) NOT NULL,
        cj_product_price DECIMAL(10,2) NOT NULL,
        cj_product_sale_price DECIMAL(10,2) NOT NULL,
        cj_product_sku TEXT NOT NULL,
        cj_duplicate_sku TEXT NOT NULL,
        cj_product_upc VARCHAR(255) NOT NULL,
        cj_last_updated  VARCHAR(255) NOT NULL,
        cj_post_status  VARCHAR(255) NOT NULL,
        cj_post_cat  VARCHAR(255) NOT NULL,
        cj_post_desc TEXT NOT NULL,
        cj_post_created INT(1) NOT NULL DEFAULT 0,
        cj_post_author INT(4) NOT NULL
        ) {$charset_collate};"; 



    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);  
    
    update_option('cjpro_db_ver',$cjpro_db_version);
    
    } 

   
 $id = $wpdb->get_var( 'SELECT id FROM ' . $cj_product_table_templates .' ORDER BY ID DESC LIMIT 0 , 1 ');

     if($id != 1 ) {
        

            $insert_default_template = "<div class='single-item-large-image'><img src='[large-img]' alt='[title]'></div>";
           

    $wpdb->insert( 
        $cj_product_table_templates, 
        array( 
            
            'content' => $insert_default_template
            ) 
        );  
}  else {
   
}



}
register_activation_hook(__FILE__, 'cjpro_database_function');

add_filter('woocommerce_single_product_image_html','cjpro_woo_add_zoom_class', 10);
function cjpro_woo_add_zoom_class(){
    global $post, $product;
    $woo_options = get_option( 'cjpro_woocommerce');
    $image_link    = wp_get_attachment_url( get_post_thumbnail_id() );
   
     $image  = get_the_post_thumbnail( $post->ID, apply_filters( 'single_product_large_thumbnail_size', 'shop_single' ), array(
                'title' => get_the_title( get_post_thumbnail_id() )
            ) );

     $attachment_count = count( $product->get_gallery_attachment_ids() );
      if ( $attachment_count > 0 ) {
                $gallery = '[product-gallery]';
            } else {
                $gallery = '';
            }
            $image_title = get_the_title( 'ID' );
            
if(isset($woo_options['cj_poster_woo_image_zoom'])){
    $image = get_the_post_thumbnail(  $post->ID, apply_filters( 'single_product_large_thumbnail_size', 'shop_single' ), array(
'title' => get_the_title( 'ID' ),
'class' => 'attachment-shop_single size-shop_single wp-post-image hoverzoom' ,
'alt'  => get_the_title( 'ID' )
));

return $image;
    
} elseif(!isset($woo_options['cj_poster_woo_image_zoom'])){
 ?>
  <a href="<?php echo $image_link; ?>" itemprop="image" class="woocommerce-main-image zoom" title="<?php echo $image_title; ?>" data-rel="prettyPhoto <?php echo $gallery; ?>"><?php echo $image; ?></a>
<?php

}
  

}


/**
 * Adds content below featured images
 * Tutorial: http://www.skyverge.com/blog/add-content-woocommerce-featured-images/
**/

function skyverge_add_below_featured_image() {
    $woo_options = get_option( 'cjpro_woocommerce');
    if(isset($woo_options['cj_poster_woo_image_zoom'])){
    echo '<h4 style="text-align:center;margin-top:10px;">Hover To Zoom</h4>';
} elseif(!isset($woo_options['cj_poster_woo_image_zoom'])){
    echo '<h4 style="text-align:center;margin-top:10px;">Click To Enlarge</h4>';
}
}
add_action( 'woocommerce_product_thumbnails' , 'skyverge_add_below_featured_image', 9 );

//If Post Containing a product from the CJ api and it has been marked as "Already Posted"
// this function removes this marking and makes the product available again



add_action( 'admin_init', 'codex_init' );
function codex_init() {
    add_action( 'delete_post', 'cjpro_change_product_availability', 10 );
}


/*************************************************
*************************************************
Create Custom Cron Recurrences Using User Entered Data
*************************************************/

function add_new_intervals($schedules) {

    $options = get_option('cjpro_settings');
    if(isset($options['cj_poster_pro_cron_check'])){
    $cj_cron_check = $options['cj_poster_pro_cron_check'];
    }
    if(isset($options['cj_poster_pro_cron_time'])){
    $cj_cron_time = $options['cj_poster_pro_cron_time'];
} else {
    $cj_cron_time = '';
}
    if(isset($cj_cron_check)){
    $cj_cron_name = "CJ_".$cj_cron_time."_HOURS";
    $cj_cron_interval = $cj_cron_time*3600;
    // add weekly and monthly intervals
    $schedules[$cj_cron_name] = array(
        'interval' => $cj_cron_interval,
        'display' => __($cj_cron_name)
    );
}
    return $schedules;
}
add_filter( 'cron_schedules', 'add_new_intervals');

/*************************************************
*************************************************
Schedule Custom Cron Event Using User Entered Data
*************************************************/


add_action('admin_init', 'cj_cron_activation');


// The action will trigger when someone the ADMIN Section of WordPress
function cj_cron_activation() {
    $options = get_option('cjpro_settings');
    if(isset($options['cj_poster_pro_cron_time'])){
    $cj_cron_time = $options['cj_poster_pro_cron_time'];
    } else {
        $cj_cron_time = '';
    }
   
    $cj_cron_name = "CJ_".$cj_cron_time."_HOURS";
    if ( !wp_next_scheduled( 'cj_custom_cron', $cj_cron_time) ) {
        wp_schedule_event( current_time( 'timestamp' ), $cj_cron_name, 'cj_custom_cron', $cj_cron_time);
    }
}


/*************************************************
*************************************************
    Delete Cron Job When Plugin Is Deactivated
*************************************************/
register_deactivation_hook( __FILE__, 'cjpro_deactivation' );
function cjpro_deactivation() {
    $options = get_option('cjpro_settings');
    $cj_cron_time = $options['cj_poster_pro_cron_time'];
  
    wp_clear_scheduled_hook('cj_custom_cron',$cj_cron_time);
}

/*************************************************
*************************************************
    Delete Cron Job When User Changes Time Frame
*************************************************/
add_action('admin_init', 'cjpro_time_change_deactivation');
function cjpro_time_change_deactivation() {
    $cron_schedule = wp_get_schedules();
    $options = get_option('cjpro_settings');
     if(isset($options['cj_poster_pro_cron_time'])){
    $cj_cron_time = $options['cj_poster_pro_cron_time'];
    } else {
        $cj_cron_time = '';
    }
    $cj_cron_name = "CJ_".$cj_cron_time."_HOURS";
    if(!in_array($cj_cron_name ,$cron_schedule)){
    wp_clear_scheduled_hook('cj_custom_cron',$cj_cron_time);
}
}

/*************************************************
*************************************************
Perform This Function When Sheduled Cron Is Run
*************************************************/


add_action('cj_custom_cron', 'do_cj_custom_cron');
function do_cj_custom_cron() {
    // do something every hour
}