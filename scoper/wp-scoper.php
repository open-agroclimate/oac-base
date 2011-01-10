<?php

require_once( 'scope.php' );
require_once( 'csv-scope-loader.php' );

class WPScoper {
	var $name;  // Scope name. This should be unique within the WP deployment
	var $scope; // Scope container
	
	function __construct( $scope_name, $autoload = true ) {
		$this->name  = str_replace('-', '_', sanitize_title_with_dashes( $scope_name ) );
		$this->scope = new Scope();
		if ( $autoload ) {
			$this->load();
		}
	}
	
	function WPScoper( $scope_name, $autoload = true ) {
		__construct( $scope_name );
	}
		
	/**
	 * Loads the named scope into this instance.
	 */
	public function load() {
		$data = get_option( $this->name, new Scope() );
		$this->scope = $data; 
	} //function load()
	
	/**
	 * Saves this instance of the scope to the appropriate WordPress option
	 */
	public function save() {
		update_option( $this->name, $this->scope );
	} //function save()
	
	/**
	 * Give the complete scope as a JSON object. Otherwise, use the scopes
	 * builtin functions with the $json = true 
	 */
	public function to_json() {
		return json_encode( $this->scope );
	}

	public function buildFromCSV( $file, $append = false ) {
		$builder = new CSVScopeLoader( $file );
		if( count( $builder->error ) != 0 ) {
			// The file isn't there
			$this->scope->errors[] =  __( 'The CSV file could not be found. Please contact the system administrator.' );
			return false;
		} else {
			if( $append == false ) {
				if( $builder->parse() === false ) {
					$this->scope->errors = $builder->error;
					array_unshift( $this->scope->errors, __( 'The following error(s) have occured during processing:' ) );
					return false;
				} else {
					$this->scope = $builder->scope;
					return true;
				}
			} else {	
				if( $builder->parse( true, false ) === false )  {
					$this->scope->errors = $builder->error;
					array_unshift( $this->scope->errors, __( 'The following error(s) have occured during processing:' ) );
					return false;

				} else {
					// Merge the two arrays (recursively)
					if( $builder->import_scope( $this->scope ) ) {
						if ( $builder->merge_scope() ) {
							return true;
						}
					}
					$this->scope->errors = $builder->error;
					array_unshift( $this->scope->errors,  __( 'There was a problem updating the scope. Please try overwriting the scope by unchecking Append' ) );
					return false;
				}
			}
		}

	}
}
?>
