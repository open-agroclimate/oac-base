<?php

require_once( 'scope.php' );
require_once( 'csv-scope-loader.php' );

class WPScoper {
	public $name;  // Scope name. This should be unique within the WP deployment
	public $scope; // Scope container
	public $filtered; // Marker to show this is a filtered scope
	
	public function __construct( $scope_name, $autoload = true, $filter = null ) {
		if( substr( $scope_name, 0, 10 ) != 'oac_scope_' )
			$scope_name = 'oac_scope_'.$scope_name;
		$this->name  = str_replace('-', '_', sanitize_title_with_dashes( $scope_name ) );
		$this->scope = new Scope();
		if ( $autoload ) {
			$this->load( $filter );
		}
	}
	
	

	/**
	 * Filter the data in a scope. SHOULD ONLY BE USED BY PLUGINS.
	 * Do not save the filtered data over the scope as it will destructively
	 * erase your data.
	 */
	private function filter_scope( $filter=array() ) {
		for( $i = 0; $i < count( $filter ); $i++ ) {
			$lineage = '';
			$heratage = explode('_', $filter[$i]);
			if( count( $heratage ) == 1 ) {
				continue;
			} else {
				array_pop($heratage);
				$lineage = array_shift( $heratage );
				$filter[] = $lineage;
				while( count( $heratage != 0 ) ) {
					$lineage .= '_'.array_shift( $heratage );
					$filter[] = $lineage;
				}
			}
		}
		$remove = array_diff_key( $this->scope->data, $filter );
		if( count($remove) == count( $this->scope->data ) ) {
			return false;
		}
		$remove = array_keys( $remove );
		for( $i = 0; $i < count($remove); $i++ ) {
			$this->scope->remove_node( $remove[$i] );
		}
		return true;
	}
	
	/**
	 * Loads the named scope into this instance.
	 */
	public function load( $filter = null) {
		$data = get_option( $this->name, new Scope() );
		$this->scope = $data;
		if( is_array( $filter ) ) {
			$this->filtered = $this->filter_scope( $filter );
		}
		
	} //function load()
	
	/**
	 * Saves this instance of the scope to the appropriate WordPress option
	 */
	public function save() {
		if( ! $this->filtered )
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

	public function populateDDL( $parent_path ='' ) {
		$options = '';
		foreach( $this->scope->get_children( $parent_path ) as $child_path => $child_data ) {
			$options .= '<option value="'.$child_path.'">';
			if( isset( $child_date['name'] ) ) {
				$options .= $child_data['name'];
			} else {
				$child_data = array_values( $child_data );
				$options .= $child_data[0];
			}
			$options .= "</option>\n";
		}
		return $options;
	}

	public function generateDDL( $path='', $display_label=false, $hook='' ) {
		// Time constrained - JS only
		$dropdown = '';
		if( isset( $this->scope->meta[ $this->scope->get_depth( $path ) ] ) ) {
			$label     = $this->scope->meta[ $this->scope->get_depth( $path ) ]['name'];
			if( $display_label )
				$dropdown  .= '<label for="scope_select_'.$label.'">'.$label."</label>\n";

			// Are there any descendents of a SELECTED item?

			$dropdown .= '<select id="'.$this->name.'-'.$label.'" class="oac-input oac-select wp-scope wp-scope-';
			$dropdown .= ( count( $this->scope->meta ) == $this->scope->get_depth( $path )+1 ) ? 'final' : 'linked'; 
			$dropdown .= '" name="scope_select_'.$label."\">\n";
			$dropdown .= $this->populateDDL( $path );
			$dropdown .= "</select>\n";
		}
		return $dropdown;
	}

	public function generateNestedDDL( $starting_path='', $display_label=false ) {
		$nested = '<div id="'.$this->name.'">';
		$children = $this->scope->get_children( $starting_path );
		if( count( $children != 0 ) ) {
			$nested .= $this->generateDDL( $starting_path, $display_label );
		}
		while( count( $children ) != 0 ) {
			$first_child_path = array_shift( array_keys( $children ) );
			$nested .= $this->generateDDL( $first_child_path, $display_label );
			$children = $this->scope->get_children( $first_child_path );
		}
		$nested .= "</div>\n";
		return $nested;
	}		
		
}

?>
