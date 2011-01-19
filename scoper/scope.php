<?php

/**
 * Data Definition
 * $meta = Contains metadata about the different layer
 * $index = Contains an index of the values, orgainzed by the layers
 * $data  = Contains the flat data as an array (heirarchal data store in they keys)
 */

class Scope {
	public $errors;
	public $meta;
	public $index;
	public $data;

	public function Scope( $scope=null ) {
		$this->__construct();		
	}

	public function __construct( $scope=null ) {
		if( is_object( $scope ) ) {
			if ( get_class( $scope ) == 'Scope' ) {
				$this->error = array();
				$this->meta = $scope->meta;
				$this->index = $scope->index;
				$this->data = $scope->data;
				return;
			}
		}
		$this->errors = $this->meta = $this->index = $this->data = array();
	}

	public function is_duplicate( $data, $parent = '', $unique_in_scope = false ) {
		if( $unique_in_scope ) {
			if ( ( $dup = array_search( $data, $this->data ) ) !== false )
				return $dup;
			else
				return false;
		} else {
			if ( ( $dup = array_search( $data, $this->get_children($parent) ) ) !== false )
				return $dup;
			else
				return false;
		}
	}

	public function add_layer( $name='', $fields=array( 'name' ) ) {
		$name = trim( $name );
		if ( $name == '' ) {
			$this->errors[] = __METHOD__.': A name must be supplied';
			return false;
		}

		foreach( $this->meta as $meta ) {
			if ( ! is_array( $meta ) ) {
				$this->errors[] = __METHOD__.': The meta information for this scope has been improperly modified';
				return false;
			}
			if ( $meta['name'] == $name ) {
				$this->errors[] = __METHOD__.': A layer with the name "'.$name.'" already exists';
				return false;
			}
		}

		$this->meta[] = array( 'name'=>$name, 'fields'=>$fields );
		return true;
	}
	
	public function get_depth( $id ) {
		$id = str_replace( '_', '', $id );
		return strlen( $id );
	}

	private function get_next_index( $parent_id ) {
		if( ! isset( $this->index[$parent_id] ) ) {
			return '0';
		} else {
			$lastkey = array_pop( array_keys( $this->index[$parent_id] ) );
			return ( string ) ( ( int ) array_pop( array_values( explode('_', $lastkey ) ) ) + 1 );

			//return (string) count( $this->index[$parent_id] );
		}
	}

	public function is_valid_node( $parent_id, $data, $strict = false ) {
		// Validate meta data
		$pdepth = $this->get_depth( $parent_id );
		if ( $pdepth > count( $this->meta ) ) {
			$this->errors[] = __METHOD__.": The following data was not added because the layer for it doesn't exist\n".print_r( $data, true );
			return false;
		}

		if( $strict ) {
			if( count( $data ) != count( $this->meta[$pdepth]['fields'] ) ) {
				$this->errors[] = __METHOD__.": The number of fields do not match for this layer\n".print_r( $data, true );	
				return false;
			}
		}

		$checkret = true;
		foreach( $this->meta[$pdepth]['fields'] as $field ) {
			if( ! isset( $data[$field] ) ) {
				$this->errors[] = __METHOD__.": The field \"".$field."\" is not in the data\n".print_r( $data, true );
				$checkret = false;
			}
		}
		return $checkret;
	}

	public function add_node( $parent='', $data=null, $fail_on_dup = true ) {
		$parent = trim( $parent );
		// Check to see if this data is already present
		if ( ( $dup = $this->is_duplicate( $data, $parent ) ) !== false ) {
			if( $fail_on_dup ) {
				$this->errors[] = __METHOD__.": The following data already exists\n". print_r( $data, true );
				return false;
			} else {
				return $dup;
			}
		}
		
		// Check to see if the parent exists. The root parent ALWAYS exists.
		if( $parent != '' ) {
			if ( ! isset( $this->data[$parent] ) ) {
				$this->errors[] = __METHOD__.': The parent node "'.$parent.'" does not exist';
				return false;
			}
		}

		if( ! $this->is_valid_node( $parent, $data ) ) {
			return false;
		}
		
		// Now generate the new index
		$index = $this->get_next_index( $parent );	
		if( $parent != '' ) {
			$index = $parent.'_'.$index;
		}
		// Insert the data
		$this->data[$index] = $data;

		$this->index[$parent][] = $index; 
		return $index;
	}

	public function get_parent( $node_id ) {
		echo "DEBUG: Looking for ".$node_id;
		if( strlen( $node_id ) <= 1 ) {
			echo "\nDEBUG: Parent is root\n";
			return '';
		} else {
			echo "\DEBUG: Parent is ". substr( $node_id, 0, -2 )."\n";
			return substr( $node_id, 0, -2 );
		}
	}

	public function get_node( $node_id, $json = false ) {
		if ( isset( $this->data[$node_id] ) ) {
			if ( $json )
				return json_encode( $this->data[$node_id] );
			else
				return $this->data[$node_id];
		} else {
			if ( $json )
				return '[]';
			else
				return array();
		}
	}

	public function has_children( $parent_id ) {
		return isset( $this->index[$parent_id] );
	}
	
	public function get_children( $parent_id, $json = false ) {
		if ( $this->has_children( $parent_id ) ) {
			$children = array_intersect_key( $this->data, array_flip( $this->index[$parent_id] ) ); 
			if ( $json ) {
				return json_encode( $children );
			} else {
				return $children;
			}
		} else {
			if( $json) {
				return '[]';
			} else {
				return array();
			}
		}
	}

	public function get_siblings( $node_id, $include_self = false, $json = false ) {
		$all_sibs = $this->get_children( $this->get_parent( $node_id ) );	

		if( ! $include_self ) {
			unset( $all_sibs[ $node_id ] );
		}

		if ( $json )
			return json_encode( $all_sibs );
		else
			return $all_sibs;
	}

	public function get_generation( $node_id, $include_self = false, $json = false ) {
		if ( $this->get_parent( $node_id ) == '' ) {
			$gen_keys = array_keys( $this->index[''] );
		} else {
			$psib_keys = array_keys( $this->get_siblings( $this->get_parent( $node_id ), true ) );
			$gen_keys = array();
			foreach( $psib_keys as $curr_parent ) {
				if( isset( $this->index[$curr_parent] ) ) {
					foreach( $this->index[$curr_parent] as $key ) {
						$gen_keys[] = $key;
					}
				}
			}

		}
		$generation = array_intersect_key( $this->data, array_flip( $gen_keys ) );
		if ( $json )
			return json_encode( $generation );
		else
			return $generation;
	}

	public function search_scope( $needle ) {
		if ( is_array( $needle ) ) {
			if ( ( $exact = array_search( $needle, $this->data ) ) !== false ) {
				return $exact;
			} else {
				return false;
			}
		} elseif ( is_string( $needle ) ) {
			foreach( $this->data as $key => $value ) {
				if( in_array( $needle, $value ) ) return $key;
			}
		}
		return false;
	}

	public function update_node( $node_id, $data ) {
		$parent_id = $this->get_parent( $node_id );
		if ( ( $this->is_valid_node( $parent_id, $data ) ) && ( ! $this->is_duplicate( $data, $parent_id ) ) ) {
			$this->data[$node_id] = $data;
			return true;
		}
		return false;
	}

	public function remove_node( $node_id, $return_deleted = false ) {
		$temp = array();
		if ( isset( $this->data[$node_id] ) ) {
			$temp[$node_id] = $this->data[$node_id];
			$parent_id = $this->get_parent( $node_id );
			$node_index  = array_search( $node_id, $this->index[$parent_id] );
			if( isset( $this->index[$node_id] ) ) {
				$children_indices = array_keys( $this->get_children( $node_id ) );
				foreach( $children_indices as $child_id ) {
					$children = $this->remove_node( $child_id, $return_deleted );
					if ( is_array( $children ) ) {
						$temp = array_merge( $temp, $children );
					}
				}
				unset( $this->index[$node_id] );
			}
			unset( $this->index[$parent_id][$node_index] );
			unset( $this->data[$node_id] );
			return $return_deleted ? $temp : true;
		}
		return $return_deleted ? $temp : false;
	}

	public function flush_errors( $fun=null ) {
		if( ( is_null( $fun ) ) || ( ! is_callable( $fun ) ) ) {
			echo implode( "\n", $this->errors )."\n";
		} else {
			$fun( $this->errors );
		}
		$this->errors = array();
	}
}

/**
 * Built-in tests
 */
/*
$ex_scope = new Scope();
$ex_scope->add_layer( 'State', array('long', 'short') );
$ex_scope->add_layer( 'County' );
$ex_scope->add_layer( 'City' );
if( count( $ex_scope->errors ) > 0 ) $ex_scope->flush_errors();
$ex_scope->add_node( '', array( 'long'=>'Florida', 'short'=>'FL' ) );
$ex_scope->add_node( '', array( 'long'=>'Georgia', 'short'=>'GA' ) );
$ex_scope->add_node( '', array( 'long'=>'Washington', 'short'=>'WA', 'capital'=>'Olympia' ) );
$ex_scope->add_node( '0', array( 'name' => 'Lake' ) );
$ex_scope->add_node( '0', array( 'name' => 'Alachua' ) );
$ex_scope->add_node( '0_0', array( 'name' => 'Gainesville' ) );
$ex_scope->add_node( '0', array( 'name' => 'Marion' ) );
$ex_scope->add_node( '1', array( 'name' => 'Dekalb' ) );
$ex_scope->add_node( '', array( 'long'=>'Alabama', 'short'=>'AL' ) );
$ex_scope->add_node( '1', array( 'name' => 'Franklin' ) ) ;
$ex_scope->update_node( '1_0', array( 'name' => 'Fulton', 'county_seat' => true ) );
$ex_scope->remove_node( $ex_scope->search_scope( 'Washington' ) );
print_r( $ex_scope );
 */
?>
