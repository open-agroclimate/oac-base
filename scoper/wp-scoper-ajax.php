<?php
// Load WP necessities
$wp_root = explode('wp-content', __FILE__);
$wp_root = $wp_root[0];


if( file_exists( $wp_root.'wp-load.php' ) ) {
	require_once( $wp_root.'wp-load.php' );
}
require_once( '../scoper/wp-scoper.php' );

if( isset( $_GET['action'] ) ) {
	switch( $_GET['action'] ) {
	case 'get_ddl_children':
		if( ( isset( $_GET['pp'] ) ) && ( isset( $_GET['scope'] ) ) ) {
			wp_scoper_js_get_ddl_children( $_GET['pp'], $_GET['scope'] );
		}
		break;
	default: break;
	}
}

function wp_scoper_js_get_ddl_children( $parent_path, $scope ) {
	$scope = new WPScoper( $scope );
	$children = array();
	if( count( $scope->scope->meta ) != 0 ) {
		if( isset( $scope->scope->meta[ $scope->scope->get_depth( $parent_path ) ] ) ) {
			$children[] = array('replace' => $scope->scope->meta[ $scope->scope->get_depth( $parent_path )]['name'], 'ddl' => $scope->populateDDL( $parent_path ) );
			$kids = $scope->scope->get_children( $parent_path );
			while( count( $kids ) != 0 ) {
				$first_child_path = array_shift( array_keys( $kids ) );
				if( isset( $scope->scope->meta[ $scope->scope->get_depth( $first_child_path )] ) ) {
					$children[] = array( 'replace' => $scope->scope->meta[ $scope->scope->get_depth( $first_child_path )]['name'], 'ddl' => $scope->populateDDL( $first_child_path ) );
					$kids = $scope->scope->get_children( $first_child_path );
				} else {
					$kids = array();
				}
			}
			echo json_encode( $children );
		}
	}
	echo '';
}


?>
