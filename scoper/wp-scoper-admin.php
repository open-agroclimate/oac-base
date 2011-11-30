<?php

require_once( 'wpdb-scope-loader.php' );

function wp_scoper_admin_list_tables( $with_wp=false ) {
    global $wpdb;
    $query = "SELECT `TABLE_NAME` FROM `information_schema`.`tables` WHERE `TABLE_SCHEMA`='oac_wp'";
    if( !$with_wp ) $query .= "  and table_name NOT LIKE 'wp_%'";
    $tables = $wpdb->get_col( $query, 0 );
    return $tables;
}

function wp_scoper_admin_init() {
    $plugin_dir = basename( dirname( __FILE__ ) );
    load_plugin_textdomain( 'wp_scoper', null, $plugin_dir . '/languages' );
    if( !current_user_can( 'manage_options' ) ) {
        wp_die( __( 'You do not have sufficient permission to access this page.' ) );
    }
    wp_admin_css( 'nav-menu' );
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
            case "wps_remove":
            if( isset( $_REQUEST['id'] ) ) {
                check_admin_referer( 'remove-'.$_REQUEST['id'] );
                if( ! wp_scoper_admin_valid_scope( $_REQUEST['id'] ) ) {
                    wp_die( __( 'The requested scope does not exist.', 'wp_scoper' ) );
                }
                $scope_index = get_option( 'oac_scopes', array() );
                $current_scope = get_option( 'oac_scope_'.$_REQUEST['id'], null );
                if( in_array( $_REQUEST['id'], $scope_index ) ) {
                    // Verify yet again that it is blank
                    if( count( $scope_index[$_REQUEST['id']] ) != 0 ) {
                        echo '<div id="message" class="error"><p><strong>'.__( 'This scope is still in use.' ).'</strong></p></div>';
                    } else {
                        delete_option( 'oac_scope_'.$_REQUEST['id'] );
                        unset( $scope_index[$_REQUEST['id']] );
                        update_option( 'oac_scopes', $scope_index );
                        echo '<div id="message" class="updated"><p><strong>'.__('Scope has been successfully removed').'</strong></p></div>';
                    }
                } else {
                    echo '<div id="message" class="error"><p><strong>'.__( 'The requested scope does not exist' ).'</strong></p></div>';
                }
            } else {
                wp_die( __( 'You are improperly attempting to remove a scope' ) );
            }
            break;
        }
    }
}

function wp_scoper_admin_menu() {
    add_submenu_page( 'oac_menu', 'WP Scoper', 'WP Scoper', 'manage_options', 'wp_scoper_listing', 'wp_scoper_admin_page' );
}

function wp_scoper_admin_page() {
    global $wpdb;
    $oac_scopes = get_option('oac_scopes', false );
    $wps_page_uri = "admin.php?page=wp_scoper_listing";
    ?>
        <div class="wrap">
        <?php screen_icon( 'tools' ); ?>
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
            $current_scope = new WPScoper( 'oac_scope_'.$_REQUEST['id'], true );
            //Need to check for $scope->scope->extra information
            $available_tables = wp_scoper_admin_list_tables();
            $current_source_table = '';
            $unused = null;
            if( property_exists($current_scope->scope, 'extra') ) {
                // This is the proper version of the scope
                $current_source_table = $current_scope->scope->extra['table'];
                $unused = array_diff( $current_scope->scope->extra['source_cols'], $current_scope->scope->extra['column_map'] );
            } else {
                if( count( $available_tables ) == 0 ) {
                    wp_die(__( 'There are no tables available, please add data' ) );
                }
                $current_source_table = $available_tables[0];
                $tmp_scope = new WPDBScopeLoader( $current_scope_table );
                $unused = $tmp_scope->scope->extra['source_cols'];
            }
            
            // Drop down list of available tables
            $ddl = '<select name="source_table" id="source_table">'."\n";
            foreach( wp_scoper_admin_list_tables() as $table ) {
            	$ddl.= "\t".'<option value="'.$table.'"'.($current_source_table == $table ? ' selected' : '').'>'.$table.'</option>'."\n";
            }
            $ddl.= '</select>'."\n";
            
            // Find unused columns if the scope is already defined.
            
            
            // Load the form for this id
            ?>
            <h3><?php _e( 'Build Scope From Database Table' ); ?></h3>
            <div id="nav-menus-frame">
                <div id="menu-settings-column" class="metabox-holder">
                    <form id="nav-menu-meta" class="nav-menu-meta">
                        <div id="side-sortables" class="meta-box-sortables">
                            <div id="source-tables" class="postbox">
                                <h3><span>Select Source Table</span></h3>
                                <div class="inside">
                                    <div>
                                        <p class="instructions">Choose a source table below.</p>
                                        <p><?php echo $ddl; ?><input type="button" class="button" id="choose-source" value="Set Table"></p>
                                    </div>
                                </div>
                            </div>
                            <div id="soure-cols" class="postbox">
                                <h3><span>Available Inputs</span></h3>
                                <div class="inside">
                                    <div>
                                        <p id="instructions">Drag and drop a field below to a location on right</p>
                                        <div id="widget-list">
                                            <?php
                                                if( count( $unused ) == 0 ) {
                                                    echo '<p>There are no columns available</p>';
                                                } else {
                                                    foreach( $unused as $col ) {
                                                        echo '<div class="widget-top"><div class="widget-title"><h4>'.$col.'</h4></div></div>';
                                                    }
                                                }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div id="menu-management-liquid">
                    <div id="menu-management">
                        <div class="nav-tabs-nav">
                            <div class="nav-tabs-wrapper"><!-- This is for alignment purposes --></div>
                        </div>
                        <div class="menu-edit">
                            <form id="update-nav-menu">
                            <div id="nav-menu-header">
                                <div id="submitpost" class="submitpost">
                                    <div class="major-publishing-actions">
                                        <h3>Current Scope: Location&nbsp;<input type="button" class="button-primary" id="regen-scope" name="regen_scope" value="Regenerate This Scope"></h3>
                                        <label class="clear open-label howto menu-name-label">
                                            <p><span>Level Name</span><input id="menu-name" type="text" name="level_name" class="regular-text menu-text menu-item-textbox"><input type="button" name="add_level" id="add-level" class="button" value="Add Level"></p>
                                        </label>                                        
                                    </div>
                                </div>
                            </div>
                            <div id="post-body">
                                <div id="post-body-content">
                                    <ul class="menu" id="menu-to-edit">
                                        <li class="menu-item">
                                            <dl class="menu-item-bar">
                                                <dt class="menu-item-handle">
                                                    <span class="item-title">State</span>
                                                </dt>
                                            </dl>
                                            <div class="menu-item-settings">
                                                <p class="description description-thin">
                                                <label>
                                                    Add Field<br>
                                                    <input type="text" class="widefat" name="add_field" id="add-field" value="Name">
                                                </label>
                                                </p>
                                                <p class="description description-thin">
                                                <label>
                                                    Map Input<br>
                                                    <input type="text" class="widefat" name="map_field" id="map-field" value="" disabled="disabled">
                                                </label>
                                                </p>
                                                <p class="description description-thin"><br><input type="button" class="button" value="Add Field"></p>
                                            </div>
                                        </li>
                                        <li class="menu-item">
                                            <dl class="menu-item-bar">
                                                <dt class="menu-item-handle">
                                                    <span class="item-title">County</span>
                                                </dt>
                                            </dl>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div id="nav-menu-footer">
                                <div class="major-publishing-actions">
                                    <div class="publishing-action">
                                        <input type="submit" name="save_scope" id="save_menu" class="button-primary menu-save" value="Save Scope">
                                    </div>
                                    <div class="delete-action">
                                        <a class="submitdelete deletion menu-delete" href="#">Clear Scope</a>
                                    </div>
                                </div>
                            </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
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
                    | <a href="<?php echo wp_nonce_url( $wps_page_uri . '&action=wps_remove&id=' . utf8_uri_encode( $scope ), 'remove-'.$scope ); ?>" title="<?php _e( 'Remove this scope' ); ?>" class="delete"><?php _e( 'Remove' ); ?></a></td>
                    <?php } ?>
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

function wp_scoper_admin_display_errors( $errors ) {
    $first_error = array_shift( $errors );
    echo '<div class="error" id="message"><p><strong>'.$first_error.'</strong></p>';
    if( count( $errors ) == 0 ) {
        echo '</div>';
    } else {
        echo '<p>'.implode( '<br />', $errors ).'</p></div>';
    }
}

function wp_scoper_admin_csv_update_scope( $scope_id, $file, $append ) {
    $current_scope = new WPScoper( 'oac_scope_'.$scope_id );
    if( count( $current_scope->scope->data ) == 0 ) {
        $append = false;
    }
    if( $current_scope->buildFromCSV( $file, $append ) ) {
        echo '<div class="updated" id="message"><p><strong>Scope updated successfully</strong></p></div>';
        $current_scope->save();
    } else {
        $current_scope->scope->flush_errors( 'wp_scoper_admin_display_errors' ); 
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
