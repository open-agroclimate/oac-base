<?php

//require_once( 'csv-scope-loader.php' );
function wp_scoper_admin_init() {
	$plugin_dir = basename( dirname( __FILE__ ) );
	load_plugin_textdomain( 'wp_scoper', null, $plugin_dir . '/languages' );
	if( !current_user_can( 'manage_options' ) ) {
		wp_die( __( 'You do not have sufficient permission to access this page.' ) );
	}
	// Anything else needed to be run (like POST or GET redirection)
	wp_scoper_admin_action_handler();
}


function wp_scoper_admin_valid_scope( $scope_id ) {
	$available_scopes = get_option( 'oac_scopes', array() );
	return array_key_exists( $scope_id, $available_scopes );
}

function wp_scoper_admin_action_handler() {
	if( isset( $_REQUEST['action'] ) ) {
		switch( $_REQUEST['action'] ) {
			case "wps_update":
				if( isset( $_REQUEST['id'] ) ) {
					check_admin_referer( 'update-'. $_REQUEST['id'] . '-scope' );
					if( ! wp_scoper_admin_valid_scope( $_REQUEST['id'] ) ) {
						wp_die( __( 'The requested scope does not exists.' ) );
					}
					$tmpfile = $_FILES['csvfile']['tmp_name'];
					$append = isset( $_REQUEST['appendmode'] ) ? true : false;
					if( is_uploaded_file( $tmpfile ) ) {
						wp_scoper_admin_csv_update_scope( $_REQUEST['id'], $tmpfile, $append );
					} else {
						wp_die( __(' Invalid request' ) );
					}

				} else {
					wp_die( __( 'You are improperly attempting to edit a scope' ) );
				}		
				break;
		}
	}
}

function wp_scoper_admin_menu() {
	add_management_page( 'WP Scoper', 'WP Scoper', 'manage_options', 'wp_scoper_listing', 'wp_scoper_admin_page' );
}

function wp_scoper_admin_page() {
	$oac_scopes = get_option('oac_scopes', false );
	$wps_page_uri = "tools.php?page=wp_scoper_listing";
?>
	<div class="wrap">
		<?php screen_icon(); ?>
		<h2><?php _e( 'Scopes', 'wp_scoper' ); ?></h2>
		<?php 
			if( ( isset( $_REQUEST['action'] ) ) && ( $_REQUEST['action'] == 'wps_edit' ) ) {
				if( ! isset( $_REQUEST['id'] ) ) {
					wp_die( __( 'You are improperly attempting to edit a scope' ) );	
				} else {
					check_admin_referer( 'edit-' . $_REQUEST['id'] );
					// Check to see if this is a valid id
					$available_scopes = get_option( 'oac_scopes', array() );
					if( ! array_key_exists( $_REQUEST['id'], $available_scopes ) ) {
						wp_die( __( 'The requested scope does not exist.' ) );
					}
					$current_scope = get_option( 'oac_scope_'.$_REQUEST['id'], array() );
					if( count( $current_scope ) != 0 ) {
						// Ugly display right now
						print_r( $current_scope->data );
					}
				// Load the form for this id	
				?>
				<h3><?php _e( 'Load Scope From CSV File' ); ?></h3>
				<form action="<?php echo $wps_page_uri; ?>" method="POST" enctype="multipart/form-data">
				<?php wp_nonce_field( 'update-'.$_REQUEST['id'].'-scope' ); ?>
				<input type="hidden" name="action" value="wps_update" />
				<input type="hidden" name="id" value="<?php echo $_REQUEST['id']; ?>" />
				<p>CSV File: <input type="file" name="csvfile" /></p>
				<?php if( count( $current_scope ) != 0 ) { ?>
				<p>Append to the current scope: <input type="checkbox" name="appendmode" checked /></p>
				<?php } ?> 
				<p><input type="submit" class="button" value="Upload CSV File" /></p>
				</form>
				<?php
				}
			} else {
		?>	
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
							<td rowspan="2">
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
						<tr class="second">
							<td>
							<a href="<?php echo wp_nonce_url( $wps_page_uri . '&action=wps_edit&id='. utf8_uri_encode( $scope), 'edit-'.$scope); ?>" title="<?php _e( 'Edit this scope'); ?>"><?php _e( 'Edit' ); ?></a> 
							<?php if( count( $plugins ) == 0 ) { ?>
								| <a href="<?php echo wp_nonce_url( $wps_page_uri . '&action=wps_remove&id=' . utf8_uri_encode( $scope ), 'remove-'.$scope ); ?>" title="<?php _e( 'Remove this scope' ); ?>"><?php _e( 'Remove' ); ?></a></td>
							<?php }	?>
							<td></td>
						</tr>
			<?php
					}
				}
			?>
			</tbody>
		</table>
		<?php } // Edit form handler ?>
	</div>
<?php
}

function wp_scoper_admin_compare_meta( $a, $b ) {
	// Quick check first, they should have the same amount of meta data
	if( ($metasize = count( $a ) ) != count( $b ) ) return false;
	// Now check the values of the meta data
	for( $i = 0; $i < $metasize; $i++ ) {
		foreach( $a[$i] as $mvalue => $mdata ) {
			if( $mvalue != 'next_index' ) {
				if( $a[$i][$mvalue] != $b[$i][$mvalue] ) return false;
			}
		}
	}
	return true;
}

function wp_scoper_admin_csv_update_scope( $scope_id, $file, $append ) {
	$builder = null;
	$current_scope = get_option( 'oac_scope_'.$scope_id, array() );
	if( count( $current_scope ) == 0 ) $append = false;
	$builder = new CSVScopeLoader( $file );
	if( count( $builder->error ) != 0 ) {
			// The file isn't there
		echo '<div id="message" class="error"><p><strong>' . __( 'The CSV file could not be found. Please contact the system administrator.' ) . '</strong></p></div>';
	} else {
		if( $append == false ) {
			if( $builder->parse() === false ) {
				echo '<div id="message" class="error"><p><strong>'.__( 'The following error(s) have occurred while processing the file:' ) . '</strong><br />'.implode( '<br />', $builder->error ).'</p></div>';
			} else {
				update_option( 'oac_scope_' . $scope_id, $builder->scope );
				echo '<div id="message" class="updated"><p>' . __( 'Scope updated successfully' ) . '</p></div>';
			}
		} else {	
			if( $builder->parse( true ) === false )  {
				echo '<div id="message" class="error"><p><strong>'.__( 'The following error(s) have occurred while processing the file:' ) . '</strong><br />'.implode( '<br />', $builder->error ).'</p></div>';
			} else {
				// Validate new meta with old meta (should be the same)
				if( wp_scoper_admin_compare_meta( $current_scope->get_meta(), $builder->scope->get_meta() ) ) {
					// Merge the two arrays (recursively)
					if( $builder->import_scope( $current_scope ) ) {
						$builder->parse();
						echo '<div id="message" class="updated"><p>' . __( 'Scope updated successfully' ). '</p></div>';
					} else {
						echo '<div id="message" class="error"><p><strong>' . __( 'There was a problem updating the scope. Please try overwriting the scope by unchecking Append' ). '</strong></p></div>';
					}
				} else {
					echo '<div id="message" class="error"><p><strong>'.__( 'The new scope is incompatable with the old scope. Please verify the columns match' ).'</strong></p></div>';
				}
			}
		}
	}
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
?>
