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

if( is_admin() ) require_once( 'scoper/wp-scoper-admin.php' );

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
			wp_register_script( 'wp-scoper', plugins_url( 'js/wp-scoper-js.php', __FILE__ ), array( 'jquery' ) );
			wp_register_style ( 'jquery-ui', plugins_url( 'js/jquery-ui/themes/base/jquery-ui.css', __FILE__ ) );
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
			add_menu_page( 'Open AgroClimate', 'Open AgroClimate', 'manage_options', 'oac_menu', array( 'OACBase', 'oac_base_admin_page' ) );
		}

		static public function oac_base_admin_page()
		{
		?>
			<div class="wrap">
				<?php screen_icon( 'tools' ); ?>
				<h2>Open AgroClimate: Global Settings</h2>
				<p>Section Description</p>
			</div>
		<?php
		}

		static private function lookup_enso() {
		$enso_array = array();
		
		// Retrieve the data from IRI	
		$enso_uri = 'http://iri.columbia.edu/climate/ENSO/currentinfo/figure3.html';
		$raw_html = wp_remote_fopen( $enso_uri );
		
		// Parse the table with the prediction data
		$result = preg_match_all("/<tr><td>(?P<prediction_date>[JFMASOND]{3}\ [0-9]{4})<\/td><td>(?P<lanina>[0-9\. ]{1,4})%<\/td><td>(?P<neutral>[0-9\. ]{1,4})%<\/td><td>(?P<elnino>[0-9\. ]{1,4})%<\/td><\/tr>/", $raw_html, $parsed_data);
		
		// Return FALSE if any errors, otherwise return the $enso_array
		if( $result ):
			$mon_lookup = array();
			$mon_lookup[] = substr(date( "M", strtotime('now')), 0, 1);
			$mon_lookup[] = substr(date( "M", strtotime('+1 month')), 0, 1);
			$mon_lookup[] = substr(date( "M", strtotime('+2 months')), 0, 1).' '.date("Y", strtotime('+2 months'));

			$true_period = implode('', $mon_lookup);
			$mon_index = array_search($true_period, $parsed_data['prediction_date']);
			if( $mon_index === false ) return false;
			// If we get a match back, then store this information in the database as well as update the current timestamp.
			$enso_array['la_nina_prediction'] = floatval( $parsed_data['lanina'][$mon_index] );
			$enso_array['neutral_prediction'] = floatval( $parsed_data['neutral'][$mon_index] );
			$enso_array['el_nino_prediction'] = floatval( $parsed_data['elnino'][$mon_index] );
			
			// Guess the current phase based on the maximum value of the above
			$current_phase = array_search( max( $enso_array ), $enso_array );
			switch( substr( $current_phase, 0, 1 ) ) {
				case 'l': // La Nina Phase
					$enso_array['current_phase'] = __( 'La Ni&#241;a' );
					break;
				case 'n': // Neutral Phase
					$enso_array['current_phase'] = __( 'Neutral' );
					break;
				case 'e': // El Nino Phase
					$enso_array['current_phase'] = __( 'El Ni&#241;o' );
					break;
				default:
					$enso_array['current_phase'] = __( 'Unknown' );
					break;
			}
		
			// Find the current prediciton period ( localized )
			$month_list    = 'JFMAMJJASONDJF';
			$pred_month_index  = stripos( $month_list, substr( $parsed_data['prediction_date'], 0, 3 ) ) + 1;
			$current_period = array();
			for( $i=0; $i < 3; $i++ ) {
				$current_period[] = date_i18n( 'M', strtotime( ( ( $pred_month_index + $i ) % 12 ).'/1/'.date( 'Y' ) ) );
			}
			$enso_array['current_period'] = implode( '-', $current_period );
			
			// Set the last_updated to the current time	
			$enso_array['last_updated'] = strtotime( 'now' );
			return $enso_array;
		else:
			// Email the site administrator(s) to let them know there is a problem
			return false;
		endif;
	}

	static public function get_current_enso_data() {
		$new_data   = false; // Set to true if new data is retrieved
		
		// Should I do my checks here or not?
		if( $enso_array = get_option( 'oac_current_enso_data', false ) ) {
			// Check the timestamp (if there is one) for freshness (2 weeks)
			if( strtotime( '+1 day', $enso_array['last_updated'] ) < strtotime( 'now' ) ) {
				// If there isn't an error getting the ENSO data, save it otherwise, keep our old data (the administrator got an email anyways)
				$new_enso_array = self::lookup_enso();
				if( $new_enso_array != false ) {
					$enso_array = $new_enso_array;
					$new_data = true;
				}
			}
		} else {
			// Lookup the data, save it and move on
			$enso_array = self::lookup_enso();
			if( $enso_array != false ) {
				$new_data = true;
			} else {
				$enso_array = array();
			}
		} //if ( get_option ... )
		if( $new_data )
			update_option( 'oac_current_enso_data', $enso_array );
		
		return $enso_array;

	}

	static public function get_current_enso_phase() {
		$enso_array = self::get_current_enso_data();
		return $enso_array['current_period'];
	}

	

}

// WordPress Hooks
add_action( 'plugins_loaded', array( 'OACBase', 'oac_base_init' ), 9 );
register_activation_hook( __FILE__, array( 'OACBase', 'oac_base_activate' ) );
register_deactivation_hook( __FILE__, array( 'OACBase', 'oac_base_deactivate' ) );
add_action( 'admin_menu', array( 'OACBase', 'oac_base_admin_menu' ) );
add_action( 'admin_menu', 'wp_scoper_admin_menu' );
add_action( 'admin_init', 'wp_scoper_admin_init' );
?>
