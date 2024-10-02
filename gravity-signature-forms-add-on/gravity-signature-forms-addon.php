<?php
/**
 * @package   	      WP E-Signature - Gravity Form
 * @contributors      Kevin Michael Gray (Approve Me), Abu Shoaib (Approve Me)
 * @wordpress-plugin
 * Plugin Name:       Signature Add-On for Gravity Forms by ApproveMe.com
 * Plugin URI:        http://aprv.me/2lfrDYG
 * Description:       This add-on makes it possible to automatically email a WP E-Signature document (or redirect a user to a document) after the user has succesfully submitted a Gravity Form. You can also insert data from the submitted Gravity Form into the WP E-Signature document.
 * Version:           1.8.4
 * Author:            ApproveMe.com
 * Author URI:        https://www.approveme.com/
 * Text Domain:       esig-gf
 * Domain Path:       /languages
 */

 // If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
	

// define constant 
if(!defined("ESIG_GRAVITY_ADDON_PATH"))
{
	define('ESIG_GRAVITY_ADDON_PATH', dirname(__FILE__));
}

/*----------------------------------------------------------------------------*
 * Public-Facing Functionality
 *----------------------------------------------------------------------------*/
require_once( plugin_dir_path( __FILE__ ) . 'includes/esig-gf-functions.php' );    
require_once( plugin_dir_path( __FILE__ ) . 'includes/esig-gravity-form.php' );


/*
 * Register hooks that are fired when the plugin is activated or deactivated.
 * When the plugin is deleted, the uninstall.php file is loaded.
 */
 
register_activation_hook( __FILE__, array( 'ESIG_GRAVITY', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'ESIG_GRAVITY', 'deactivate' ) );


//if (is_admin()) {
require_once( plugin_dir_path( __FILE__ ) . 'admin/about/autoload.php' );

require_once( plugin_dir_path( __FILE__ ) . 'includes/esig-gf-generate-value.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/esig-gravity-settings.php' );    
require_once( plugin_dir_path( __FILE__ ) . 'admin/esig-gravity-form-admin.php' );
require_once( plugin_dir_path( __FILE__ ) . 'admin/esig-gravity-filters.php' );





require_once( plugin_dir_path( __FILE__ ) . 'admin/includes/esig-gravityform-document-view.php' );

add_action( 'plugins_loaded', array( 'ESIG_GRAVITY_Admin', 'get_instance' ) );
add_action( 'plugins_loaded', array( 'esigGravityFilters', 'instance' ) );

require_once( plugin_dir_path( __FILE__ ) . 'admin/rating-widget/esign-rating-widget.php' );
add_action( 'plugins_loaded', array( 'esignRatingWidgetGravity', 'get_instance' ) );

//}

/**
 * Load plugin textdomain.
 *
 * @since 1.1.3
 */
function esig_gravity_load_textdomain() {
    
  load_plugin_textdomain('esig-gf', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' ); 
}
add_action( 'plugins_loaded', 'esig_gravity_load_textdomain');



function esig_gravity_row_meta( $links, $file ) {

	if ( strpos( $file, 'esig-gravity-form.php' ) !== false ) {
		$new_links = array(
					'<a href="index.php?page=esign-gravity-about">'. __('Get Started','esign').'</a>'
				);
		
		$links = array_merge( $links, $new_links );
	}
	
	return $links;
}
add_filter( 'plugin_row_meta', 'esig_gravity_row_meta', 10, 2 );

add_action( 'gform_loaded', array( 'GF_GFEsignAddOn_Bootstrap', 'load' ), -5 );

class GF_GFEsignAddOn_Bootstrap {

	public static function load(){
                
		/*if ( ! method_exists( 'GFForms', 'include_feed_addon_framework' ) ) {
			return;
		}*/

		require_once( 'admin/esig-gravity-addon.php' );

		GFAddOn::register( 'GFEsignAddOn' );
              // new GFEsignAddOn();
	}

}


//require_once( plugin_dir_path( __FILE__ ) . 'admin/esig-gravity-addon.php' );
