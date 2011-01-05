<?php

require_once( 'array-scoper.php' );
require_once( 'csv-scope-loader.php' );

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
	
	public function buildFromCSV( $csv_file )
	{
		$builder = new CSVScopeLoader( $csv_file );
			
	}
	
}
?>
