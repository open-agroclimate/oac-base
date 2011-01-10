<?php
/*
 * Plugin Name: Open AgroClimate Base Plugin
 * Version: 1.0
 * Plugin URI: http://open.agroclimate.org/downloads/
 * Description: A collection of libraries and functions used by most of the OAC tools. Should always be activated if using the OAC tools.
 * Author: The Open AgroClimate Project
 * Author URI: http://open.agroclimate.org/
 * License: BSD Modified
 */

/**
 * @package OpenAgroClimateWP
 */

/**
 * Base class for OAC Tools
 * 
 * This is the base class which contains static functions calling required
 * libraries for other OAC tools.
 *
 * @subpackage OACBase
 * 
 * @since 1.0
 */

if( !class_exists( 'OACBase' ) ) {
	class OACBase {

	 	/**
		 * Initializes pChart and (g)RaphaelJs libraries
		 * 
		 * oac_base_init() requires the needed pChart files for base rendering. This 
		 * does NOT include any specific rendering pChart libraries (such as pPie, 
		 * pBarcode, etc.). Also sets up the registration of the raphaelJS / gRaphael
		 * vector library. To use, choose the TYPE of chart you desire:
		 * <code> 
		 * wp_enqueue_script('grpie'); // Includes raphael-min.js, g.raphael-min.js and g.pie-min.hs files
		 * </code>
		 * 
		 * @since 1.0
		 */
		static public function oac_base_init()
		{
			// Put the requires inside of the init to make sure it's all or nothing on initialization

			// pChart requires (see LICENSE information)
			require_once( 'pchart/class/pData.class');
			require_once( 'pchart/class/pDraw.class');
			require_once( 'pchart/class/pImage.class');
			require_once( 'pchart/class/pCache.class' );
			
			// WP_Scoper
			require_once( 'scoper/wp-scoper.php' );
			
			// raphaeljs/gRapahel script setup.
			wp_register_script( 'raphaeljs', plugins_url( 'js/raphael-min.js', __FILE__ ) );
			wp_register_script( 'graphael',  plugins_url( 'js/graphael/g.raphael-min.js', __FILE__ ) );
			wp_register_script( 'grpie',     plugins_url( 'js/graphael/g.pie-min.js', __FILE__ ), array('raphaeljs', 'graphael') );
		}

		/**
		 * Sets the oac_base_info option in WordPress to true.
		 *
		 * The oac_base_info option is used internally by other OAC tools. This
		 * method is fired on plugin activation.
		 * 
		 * @since 1.0
		 */
		static public function oac_base_activate()
		{
			update_option( 'oac_base_info', array('active'=> true, 'base_url'=> plugins_url('', __FILE__), 'base_path' => plugin_dir_path(__FILE__) ) );
		}

		/**
		 * Sets the oac_base_info option in WordPress to false.
		 *
		 * The oac_base_info option is used interally by other OAC tools. This
		 * method is fired on plugin deactivation.
		 * 
		 * @since 1.0
		 */
		static public function oac_base_deactivate()
		{
			update_option( 'oac_base_info', false );
		}

		/***
		 * Sets up the Open AgroClimate menu in WordPress
		 *
		 * @since 1.0
		 */
		static public function oac_base_admin_menu()
		{
			add_menu_page( 'Open AgroClimate', 'Open AgroClimate', 'manage_options', 'oac_menu', 'OACBase::oac_base_menu_page' );
		}

		static public function oac_base_admin_page()
		{
			echo "Under Development";
		}
	}
}

// WordPress Hooks
add_action( 'plugins_loaded', array( 'OACBase', 'oac_base_init' ), 9 );
register_activation_hook( __FILE__, array( 'OACBase', 'oac_base_activate' ) );
register_deactivation_hook( __FILE__, array( 'OACBase', 'oac_base_deactivate' ) );
if( is_admin() ) require_once( 'scoper/wp-scoper-admin.php' );
add_action( 'admin_menu', array( 'OACBase', 'oac_base_admin_menu' ) );
add_action( 'admin_menu', 'wp_scoper_admin_menu' );
add_action( 'admin_init', 'wp_scoper_admin_init' );
?>
