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

class OACBase {
	static public $units = null;

	/**
	 * Initializes pChart and (g)RaphaelJs libraries
	 * 
	 * init() Sets up the registration of the raphaelJS / gRaphael
	 * vector library. To use, choose the TYPE of chart you desire:
	 * <code> 
	 * wp_enqueue_script('grpie'); // Includes raphael-min.js, g.raphael-min.js and g.pie-min.hs files
	 * </code>
	 * 
	 * @since 1.0
	 */
	static public function init() {
		// Put the requires inside of the init to make sure it's all or nothing on initialization
		
		// WP_Scoper
		require_once( 'scoper/wp-scoper.php' );
		// mootools
		wp_register_script( 'mootools-core',         plugins_url( 'js/mootools/mootools-core.js',       __FILE__ ) );
		wp_register_script( 'mootools',              plugins_url( 'js/mootools/mootools-more.js',       __FILE__ ), array( 'mootools-core' ) );
		wp_register_script( 'mootools-array-math',   plugins_url( 'js/mootools/Array.Math.js',          __FILE__ ), array( 'mootools-core' ) );
		wp_register_script( 'mootools-table-colsel', plugins_url( 'js/mootools/HtmlTable.ColSelect.js', __FILE__ ), array( 'mootools' ) );
		
		// raphaeljs/gRapahel script setup.
		wp_register_script( 'raphaeljs', plugins_url( 'js/raphael.js', __FILE__ ) );
		wp_register_script( 'graphael',  plugins_url( 'js/graphael/g.raphael.js', __FILE__ ), array( 'raphaeljs' ) );
		wp_register_script( 'grpie',     plugins_url( 'js/graphael/g.pie.js', __FILE__ ),     array( 'graphael') );
		wp_register_script( 'grbar',     plugins_url( 'js/graphael/g.bar.js', __FILE__),      array( 'graphael' ) );
		wp_register_script( 'grline',    plugins_url( 'js/graphael/g.line.js', __FILE__),     array( 'graphael' ) );
		//wp_register_script( 'oaclib',    plugins_url( 'js/oaclib.js', __FILE__ ), array( 'jquery','graphael' ) );
		wp_register_script( 'oac-base',      plugins_url( 'js/oac-base.js',      __FILE__ ),  array( 'mootools' ) );
		wp_register_script( 'oac-barchart',  plugins_url( 'js/oac-barchart.js',  __FILE__ ),  array( 'oac-base', 'grbar' ) );
		wp_register_script( 'oac-linechart', plugins_url( 'js/oac-linechart.js', __FILE__ ),  array( 'oac-base', 'grline' ) );
		// CSS styles registration
		wp_register_style( 'oacbase',   plugins_url( 'css/oac-base.css', __FILE__ ) );
		
		$plugin_dir = basename( dirname( __FILE__ ) );
		load_plugin_textdomain( 'oacbase', null, $plugin_dir . '/languages' );

		
		self::$units = array('Metric'=>array(
				'smalllen'=>array(
					'abbr' => __( 'mm', 'oacbase' ),
					'name' => __( 'Millimeter', 'oacbase' )
				),
				'len' => array(
					'abbr' => __('m', 'oacbase' ),
					'name' => __('Meter', 'oacbase' ),
				),
				'largelen' => array(
					'abbr' => __('km', 'oacbase' ),
					'name' => __('Kilometer', 'oacbase' )
				),
				'temp'=> array(
					'abbr' => __('&#176;C', 'oacbase' ),
					'name' => __('&#176;Celsius', 'oacbase' )
				)
			),
			'US' => array(
				'smalllen'=>array(
					'abbr'   => __('in', 'oacbase' ),
					'name'   => __('Inch', 'oacbase' ),
					'plural' => __('Inches', 'oacbase' )
				),
				'len' => array(
					'abbr'   => __('ft', 'oacbase' ),
					'name'   => __('Foot', 'oacbase' ),
					'plural' => __('Feet', 'oacbase' )
				),
				'largelen' => array(
					'abbr' => __('mi', 'oacbase' ),
					'name' => __('Mile', 'oacbase' )
				),
				'temp' => array(
					'abbr' => __('&#176;F', 'oacbase' ),
					'name' => __('&$176;Fahrenheit', 'oacbase' )
				)
			)
		);
	}
	
	static public function get_unit( $callee, $var ) {
		$type = get_option( $callee.'_units', get_option( 'oac_base_units', 'Metric' ) );
		return ( isset( self::$units[$type][$var] ) ) ? self::$units[$type][$var] : '';
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
					$enso_array['current_phase'] = __( 'La Ni&#241;a', 'oacbase'  );
					break;
				case 'n': // Neutral Phase
					$enso_array['current_phase'] = __( 'Neutral', 'oacbase'  );
					break;
				case 'e': // El Nino Phase
					$enso_array['current_phase'] = __( 'El Ni&#241;o', 'oacbase'  );
					break;
				default:
					$enso_array['current_phase'] = __( 'Unknown', 'oacbase'  );
					break;
			}
		
			// Find the current prediciton period ( localized )
			$month_list    = 'JFMAMJJASONDJF';
			$pred_month_index  = stripos( $month_list, substr( $parsed_data['prediction_date'][$mon_index], 0, 3 ) ) + 1;
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
		return $enso_array['current_phase'];
	}

	static public function display_enso_selector( $phases_only = false ) {
		$current_phase_id = substr( self::get_current_enso_phase(), 0, 1 );
		$output = '<ul id="enso-select">';
		$output .= '<li class="neutral oac-enso-1"><input type="radio" class="oac-input oac-radio" name="ensophase" value="1"'.(($current_phase_id == 'N') ? ' checked' : '').'>'.__( 'Neutral', 'oacbase'  ).'</li>';
		$output .= '<li class="elnino oac-enso-2"> <input type="radio" class="oac-input oac-radio" name="ensophase" value="2"'.(($current_phase_id == 'E') ? ' checked' : '').'>'.__( 'El Ni&#241;o', 'oacbase'  ).'</li>';
		$output .= '<li class="lanina oac-enso-3"> <input type="radio" class="oac-input oac-radio" name="ensophase" value="3"'.(($current_phase_id == 'L') ? ' checked' : '').'>'.__( 'La Ni&#241;a', 'oacbase'  ).'</li>';
		if ( ! $phases_only )
			$output .= '<li class="allYears oac-enso-4"><input type="radio" class="oac-input oac-radio" name="ensophase" value="4">'.__( 'All Years', 'oacbase'  ).'</li>';
		$output .= '</ul>';
		return $output;
	}

	static public function ie_conditionals() {
	}
	
	static public function knsort( $array ) {
		$keys = array_keys($array);
		
	    natsort($keys);
		
		// Fix the -number gotcha
		$i = 0;
		$resort = array();
		while(substr($keys[$i], 0, 1) == '-' ) {
		    array_unshift($resort, array_shift($keys));
		}
		$keys = array_merge($resort, $keys);
		
		$new_array = array();
	    foreach ($keys as $k)
	    {
		$new_array[$k] = $array[$k];
	    }
	
		return $new_array;
	}
	
	static public function knrsort($array) {
	    return array_reverse( self::knsort($array), true );
	}
}
// For once, we are putting the handling code for the admin at the bottom. This is a special
// exception to the norm.
class OACBaseAdmin {
	static public function init() {
		OACBase::init();
		$plugin_dir = basename( dirname( __FILE__ ) );
		load_plugin_textdomain( 'oacbase', null, $plugin_dir . '/languages' );
		self::admin_handler();
	}
	/**
	 * Sets the oac_base_info option in WordPress to true.
	 *
	 * The oac_base_info option is used internally by other OAC tools. This
	 * method is fired on plugin activation.
	 * 
	 * @since 1.0
	 */
	static public function activate() {
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
	static public function deactivate() {
		update_option( 'oac_base_info', false );
	}

	/***
	 * Sets up the Open AgroClimate menu in WordPress
	 *
	 * @since 1.0
	 */
	static public function admin_menu() {
		add_menu_page( 'Open AgroClimate', 'Open AgroClimate', 'manage_options', 'oac_menu', array( 'OACBaseAdmin', 'admin_page' ) );
	}

	static public function admin_handler() {
		// First we need to check the user permissions
		//if( !current_user_can( 'manage_options' ) ) {
		//	wp_die( __( 'You do not have sufficient permission to access this page.' ) );
		//}
		if( isset( $_REQUEST['action'] ) ) {
			switch( $_REQUEST['action'] ) {
				case 'oacb_update_options':
					check_admin_referer( 'update-base-options' );
					update_option( 'oac_base_units', $_REQUEST['base_units'] );
                    update_option( 'oac_display_enso', $_REQUEST['ensophase'] );
                    update_option( 'oac_display_enso_text', $_REQUEST['ensotext'] );
					echo '<div id="message" class="updated"><p><strong>'.__('Global Settings updated', 'oacbase' ).'</strong></p></div>';
					break;
			}
		}
	}

	static public function admin_page() {
        $base_units = get_option( 'oac_base_units', 'Metric' );
        $ensophase  = get_option( 'oac_display_enso', 'N' );
        $ensotext   = get_option( 'oac_display_enso_text', '' );
	?>
		<div class="wrap">
			<?php screen_icon( 'tools' ); ?>
        <h2><?php _e( 'Open AgroClimate: Global Settings', 'oacbase' ); ?></h2>
        <form action="<?php echo esc_attr( $_SERVER['REQUEST_URI'] ); ?>" method="POST">
            <h3><?php _e( 'Units', 'oacbase' ); ?></h3>
            <p><?php _e( 'Please choose the standard unit of measurement used by your data.', 'oacbase' );?><br>
            <?php wp_nonce_field( 'update-base-options' ); ?>
            <input type="hidden" name="action" value="oacb_update_options">
            <label for="base_units"><?php _e( 'Units in:', 'oacbase' ); ?> </label>
            <select id="base_units" name="base_units">
            <?php 
                foreach( array_keys( OACBase::$units ) as $key ) {
                    echo "<option value=\"{$key}\"".($base_units == $key ? " selected" : "").">{$key}</option>";
                }
            ?>
            </select>
            </p>
            <hr>
            <h3><?php _e( 'Current ENSO Phase', 'oacbase' ); ?></h3>
            <p><?php _e( 'Please select the current ENSO phase to display, along with any text', 'oacbase' );?><br>
            <label for="ensophase"><?php _e( 'ENSO Phase', 'oacbase' ); ?>: </label><br>
            <select id="ensophase" name="ensophase">
                <option value="N" <?php echo( ( substr( $ensophase, 0, 1 ) == 'N' ) ? "selected" : ""); ?>><?php _e( 'Neutral', 'oacbase' ); ?></option>
                <option value="E" <?php echo( ( substr( $ensophase, 0, 1 ) == 'E' ) ? "selected" : ""); ?>><?php _e( 'El Ni&#241;o', 'oacbase' ); ?></option>
                <option value="L" <?php echo( ( substr( $ensophase, 0, 1 ) == 'L' ) ? "selected" : ""); ?>><?php _e( 'La Ni&#241;a', 'oacbase' ); ?></option>
            </select></div></p>
            <div><label for="ensotext"><?php _e( 'ENSO Phase Description', 'oacbase' ); ?>: </label><br>
            <input type="text" id="ensotext" name="ensotext" size=60 value="<?php echo $ensotext; ?>"></div>
            <p><input type="submit" class="button" value="Update Settings" /></p>
        </form>
		</div>
	<?php
	}

	
}

// WordPress Hooks
// Using a order number of 9 for OACBase to prevent
// race conditions
add_action( 'plugins_loaded', array( 'OACBase', 'init' ), 9 );
register_activation_hook( __FILE__, array( 'OACBaseAdmin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'OACBaseAdmin', 'deactivate' ) );
add_action( 'admin_init', array('OACBaseAdmin', 'init' ), 9 );
add_action( 'admin_menu', array( 'OACBaseAdmin', 'admin_menu' ), 9 );
add_action( 'admin_menu', 'wp_scoper_admin_menu' );
add_action( 'admin_init', 'wp_scoper_admin_init' );
?>
