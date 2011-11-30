<?php
$wp_root = explode('wp-content', __FILE__);
$wp_root = $wp_root[0];


if( file_exists( $wp_root.'wp-load.php' ) ) {
	require_once( $wp_root.'wp-load.php' );
}
require_once('wpdb-scope-loader.php');


//function listTables( $with_wp=false ) {
//	global $wpdb;
//	$query = "SELECT `TABLE_NAME` FROM `information_schema`.`tables` WHERE `TABLE_SCHEMA`='oac_wp'";
//	if( !$with_wp ) $query .= "  and table_name NOT LIKE 'wp_%'";
//	$tables = $wpdb->get_col( $query, 0 );
//	return $tables;
//}
//
//
//
//echo '<select id="source_table" name="source_table">'."\n";
//foreach( listTables() as $table ) {
//	echo "\t".'<option value="'.$table.'">'.$table.'</option>'."\n";
//}
//echo '</select>'."\n";


// In essense, this select causes a new Scope to be started (but not saved)
//Load the scope
$scope = new WPScoper( 'location' );

// Find unused items
$unused = array_diff( $scope->scope->extra['source_cols'], $scope->scope->extra['column_map'] );

//What is the best way to display this thing.
print_r( $scope );
$l = count( $scope->scope->meta );
for( $i=0; $i < $l; $i++ ) {
    $level = $scope->scope->meta[$i]['name'];
    echo "Level Name: {$level}\n";
    $ll = count( $scope->scope->meta[$i]['fields'] );
    for( $ii=0; $ii < $ll; $ii++ ) {
        $field = $scope->scope->meta[$i]['fields'][$ii];
        $mapping = (isset( $scope->scope->extra['column_map'][$level.'|'.$field] ) ? $scope->scope->extra['column_map'][$level.'|'.$field] : 'undefined' );
        echo "Field name ".chr(27)."[1m{$field}".chr(27)."[0m".chr(27)."  is mapped to ".chr(27)."[1m{$mapping}".chr(27)."[0m".chr(27)."\n";
    }
}
?>