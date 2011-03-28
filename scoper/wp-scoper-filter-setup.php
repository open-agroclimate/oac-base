<?php
$wp_root = explode('wp-content', __FILE__);
$wp_root = $wp_root[0];


if( file_exists( $wp_root.'wp-load.php' ) ) {
	require_once( $wp_root.'wp-load.php' );
}

require_once( 'scope.php' );

// Temporary until FE completed
require_once( 'wp-scoper.php' );

$filter_name = 'location_national_stats';
$filter_array = array( '0', '1', '2', '4', '5', '6', '7', '9', '11', '12', '13', '14', '15', '16', '17', '18', '19' );

$filter = get_option( 'oac_scope_filters', array() );

//$filter[$filter_name] = $filter_array;

//print_r( $filter );
//update_option( 'oac_scope_filters', $filter );



$wpscope = new WPScoper( 'location', $filter[$filter_name] );
print_r( $wpscope );


