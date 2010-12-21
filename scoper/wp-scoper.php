<?php

require_once( 'array-scoper.php' );

function wp_scoper_admin_init() {
	$plugin_dir = basename( dirname( __FILE__ ) );
	load_plugin_textdomain( 'wp_scoper', null, $plugin_dir . '/languages' );
	if( !current_user_can( 'manage_options' ) ) {
		wp_die( __( 'You do not have sufficient permission to access this page.' ) );
	}
	
	// Anything else needed to be run (like POST or GET redirection)
}

function wp_scoper_admin_menu() {
	add_management_page( 'WP Scoper', 'WP Scoper', 'manage_options', 'wp_scoper_listing', 'wp_scoper_admin_page' );
}

function wp_scoper_admin_page() {
	$oac_scopes = get_option('oac_scopes', false );
?>
	<div class="wrap">
		<?php screen_icon(); ?>
		<h2><?php _e( 'Scopes', 'wp_scoper' ); ?></h2>
		<table class="widefat" cellspacing="0" id="wp_keyring-directory-table">
			<thead>
				<tr>
					<th scope="col" class="manage-column"><?php _e( 'Name', 'wp_scoper' ); ?></th>
					<th scope="col" class="manage-column"><?php _e( 'Used For', 'wp_scoper' ); ?></th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th scope="col" class="manage-column"><?php _e( 'Name', 'wp_scoper' ); ?></th>
					<th scope="col" class="manage-column"><?php _e( 'Used For', 'wp_scoper' ); ?></th>
				</tr>
			</tfoot>
			<tbody class="plugins">
			<?php
				if( ! $oac_scopes ) {
			?>
				<tr>
					<td colspan="2"><?php _e( 'No scopes installed', 'wp_scoper' ); ?></td>
				</tr>
			<?php
				} else {
					foreach( $oac_scopes as $scope => $plugins ) {
			?>
						<tr>
							<td><?php echo $scope; ?></td>
							<td>
							<?php
							 	if( count( $plugins ) > 0 ) {
									sort( $plugins );
									echo implode( ', ', $plugins );
								} else {
									_e( 'Not used', 'wp_scoper' );
								}
							?>
							</td>
						</tr>
			<?php
					}
				}
			?>
			</tbody>
		</table>
	</div>
<?php
}

// WordPress Option Descriptions:
// option: oac_scopes => array('region'=>array( 'TestPlugin' ), 'crops'=>array('TestPlugin'));
// option: oac_scope_region => ArrayScope(...);
// option: oac_scope_crops  => ArrayScope(...);

function wp_scoper_admin_setup_scopes( $scopes=null, $plugin_file = '' ) {
	if( ( function_exists( 'get_plugin_data' ) ) && ( $plugin_file != '' ) ) {
		$plugin_info = get_plugin_data( $plugin_file );
		$oac_scopes = get_option('oac_scopes', array());
		if( ( ! is_array( $scopes ) ) && ( $scopes !== null ) )
			$scopes = array( $scopes );
		foreach( $scopes as $item ) {
			if( ! array_key_exists( $item, $oac_scopes ) )
				$oac_scopes[$item] = array();
			if( ! in_array( $plugin_info['Name'], $oac_scopes[$item] ) )
				$oac_scopes[$item][] = $plugin_info['Name'];
		}
		update_option('oac_scopes', $oac_scopes );
	} else {
		return new WP_Error( 'no_admin', __( 'You do not have permissions to perform this action or have called it improperly.' ) );
	}
} // function wp_scoper_admin_setup_scopes()

/**
 * The uninstall hook for external plugins to call.
 * <code>
 * function deactivate_plugin() {
 *   wp_scoper_admin_cleanup_scopes( __FILE__ );
 * }
 * register_deactivation_hook('deactivate_plugin');
 * </code>
 *
 * TODO: Implement deletion ability or remove any reference to it.
 */

function wp_scoper_admin_cleanup_scopes( $plugin_file='', $delete = false ) {
	if( ( function_exists( 'get_plugin_data' ) ) && ( $plugin_file != '' ) ) {
		$plugin_info = get_plugin_data( $plugin_file );
		$oac_scopes = get_option( 'oac_scopes', array() );
		foreach($oac_scopes as $scope=>$plugins) {
			$index = array_search($plugin_info['Name'], $plugins);
			if( $index !== false ) {
				unset( $oac_scopes[$scope][$index] );
			}
		}
		update_option( 'oac_scopes', $oac_scopes );
	}
}

class WPScoper {
	var $name;  // Scope name. This should be unique within the WP deployment
	var $data; // Scope container
	
	function __construct__( $scope_name, $autoload = false ) {
		$this->name  = str_replace('-', '_', sanitize_title_with_dashes( $scope_name ) );
		$this->data = array();
		if ( $autoload ) {
			$this->load();
		}
	}
	
	function WPScoper( $scope_name ) {
		__construct__( $scope_name );
	}
		
	/**
	 * Loads the named scope into this instance.
	 */
	public function load() {
		$data = get_option( $this->name, array() );
		$this->data = ArrayScoper( $data );
	} //function load()
	
	/**
	 * Saves this instance of the scope to the appropriate WordPress option
	 */
	public function save() {
		update_option( $this->name, $this->data );
	} //function save()
	
	/**
	 * A wrapper for ArrayScoper->to_json()
	 */
	public function to_json( $target=null, $with_meta = true ) {
		return $this->data->to_json( $target, $with_meta );
	}
	
}
?>
