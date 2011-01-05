<?php

class ArrayScoper {
	public $error = array();
	public $data;
	public $raw_data;
	
	function ArrayScoper( $arr = null ) {
		__construct( $arr );
	}

	function __construct( $arr = null ) {
		$this->error = array();
		if ( is_null( $arr ) || ( ! is_array( $arr ) ) ) {
			$this->raw_scope = array( 'meta' => array(), 'data'=>array('__path'=>'') );
			$this->data = &$this->raw_scope[ 'data' ];
		} else {
			$this->raw_scope = $arr;
		}
		
		if ( $this->is_valid() )
			$this->data = &$this->raw_scope[ 'data' ];
		else
			$this->raw_scope = array( 'meta'=>array(), 'data'=>array() );
	} // function __construct

	private static function add_data_recursively( &$array ) {
		foreach ( $array as $key => &$value ) {
			if ( is_array( $value ) ) {
				if ( array_key_exists( 'data', $value ) ) {
					if ( count( $value[ 'data' ] == 0 ) ) {
						return;
					} else {
						add_data_recursively( $value[ 'data' ] );
					}
				} else {
					$value[ 'data' ] = array();
				} // if ( array_key_exists( ... ) )
			} // if ( is_array( ... ) ) 
		} // foreach ( ...  )
		return;
	} //function add_data_recursively

	private static function get_depth( $arr ) {
		if ( is_array( $arr ) ) {
			if ( !array_key_exists( '__path', $arr ) ) {
				return -1;
			}
			$path = $arr[ '__path' ];
			if ( $path == '' ) {
				return 0;
			}
			$path_arr = explode( '_', $path );
			return count( $path_arr );
		} // if ( is_array( ... ) )
		return -1;
	} //function get_depth

	function lookup_by_path( $path=null ) {
		$f = false;
		if ( ! is_null( $path ) ) {
			if ($path == '') {
				return $this->data;
			}
			$arr = $this->data;
			foreach ( explode('_', $path ) as $path_item ) {
				if ( array_key_exists( $path_item, $arr ) ) {
					$arr = $arr[ $path_item ];
				} elseif ( array_key_exists( $path_item, $arr[ 'data' ] ) ) {
					$arr = $arr[ 'data' ][ $path_item ];
				} else {
					return $f;
				}
			} // foreach ( ... )
			return $arr;
		} // if ( ! is_null ( $path ) )
		return $f;
	}

	function &reference_by_path( $path=null ) {
		$f = false;
		if ( ! is_null( $path ) ) {
			if ($path == '') {
				return $this->data;
			}
			$arr = &$this->data;
			foreach ( explode('_', $path ) as $path_item ) {
				if ( array_key_exists( $path_item, $arr ) ) {
					$arr = &$arr[ $path_item ];
				} elseif ( array_key_exists( $path_item, $arr[ 'data' ] ) ) {
					$arr = &$arr[ 'data' ][ $path_item ];
				} else {
					return $f;
				}
			} // foreach ( ... )
			return $arr;
		} // if ( ! is_null ( $path ) )
		return $f;
	} //function reference_by_path

	function depth() {
		$max_indentation = 1;
		$array_str = print_r( $this->data, true );
		$lines = explode( PHP_EOL, $array_str );
		foreach ( $lines as $line ) {
			$indentation = ( strlen( $line ) - strlen( ltrim( $line ) ) ) / 4;

			if ( $indentation > $max_indentation ) {
				$max_indentation = $indentation;
			}
		}
		return ceil( ( $max_indentation - 1 ) / 4 );
	} //function array_depth

	function get_meta( $depth = null) {
		if ( array_key_exists( 'meta', $this->raw_scope ) ) {
			if (is_array( $this->raw_scope[ 'meta' ] ) ) {
				if ( is_null( $depth ) ) {
					return $this->raw_scope[ 'meta' ];
				} else {
					$max_depth = $this->depth();
					if ( $depth <= $max_depth )
						return $this->raw_scope[ 'meta' ][ $depth ];
					else
						return array();
				}
			}
		}
		return array();
	} //function get_meta

	function is_meta_valid() {
		$required_meta = array('next_index', 'name');
		$depth_verifier = array();
		// Verify that there is a meta entry for each level of depth
		if ( ( ! array_key_exists( 'meta', $this->raw_scope ) ) || ( count( $this->get_meta() ) != $this->depth() ) )
			$this->error[] = "Missing one or more meta entries.";

		// Verify the required meta information is in the [ 'meta' ]
		foreach ( $this->get_meta() as $key => $entry ) {
			if ( ! is_array( $entry ) )
				$this->error[] = $entry." is not a valid meta entry. Should be an array.";
			foreach ( $required_meta as $check ) {
				if ( ! array_key_exists( $check, $entry ) )
					$this->error[] = $entry." is missing the meta information ".$check.'.';
			}
			$depth_verifier[] = $key;
		}

		// Verify that no two meta entries have the same depth
		if ( count( array_unique( $depth_verifier ) ) != count( $this->get_meta() ) )
			$this->error[] = "Two or more meta entries have the same depth information.";

		if ( count( $this->error ) == 0 )
			return true;
		else
			return false;
	} //function is_meta_valid

	function is_valid() {
		return $this->is_meta_valid();
	} //function is_valid

	function add_depth( $name, $terms_array=array( 'name' ), $add_data = false ) {
		if ( $name == '' ) {
			$this->error[] = "A name needs to be supplied to add_depth().";
			return false;
		}

		foreach ( $this->get_meta() as $meta_entry ) {
			if ( $meta_entry[ 'name' ] == $name ) {
				$this->error[] = "This field is already in use.";
				return false;
			}
		}
		if ( ! is_array( $terms_array ) ) {
			$terms_array = array( $terms_array );
		}

		// First add the scope to the meta information
		$default_depth = count( $this->get_meta() );
		$default_index = 0;
		$this->raw_scope[ 'meta' ][  $default_depth  ] = array('name'=>$name, 'next_index'=>$default_index, 'required'=>$terms_array);

		//Now add the data for the new scope level
		if ( $add_data == true )
			ArrayScoper::add_data_recursively( $this->raw_scope[ 'data' ] );
		return true;
	} //function add_depth

	function is_duplicate( $parent, $data ) {
		if ( ! is_array( $data ) ) {
			$this->error[] = "The data needs to be an array.";
			return false;
		}
		// For the root element! (BAAD LITTLE LANGUAGE)
		if( array_key_exists( '__path', $parent ) ) {
			if ( is_array( $parent['__path'] ) ) {
				$parent = $parent['__path'];
			}
		}
		// For normal entries
		if ( array_key_exists ('data', $parent ) ) {
			if (is_array( $parent['data'] ) ) {
				$parent = $parent['data'];
			}
		}
		
		foreach( $parent as $p ) {
			if( is_array($p) ) {
				if( array_key_exists( '__path', $p ) )
					$path = $p['__path'];
					unset( $p['__path'] );
					if ($p == $data ) {
						return  $path;
					}
			}
		}
		return false; 	
	}

	function add_entry( $parent_path, $data, $fail_on_dup = false ) {
		if ( ! is_array( $data ) ) {
			$this->error[] = "The data needs to be an array.";
			return false;
		}

		if ( ( $parent = &$this->reference_by_path( $parent_path ) ) == false ) {
			$this->error[] = "Invalid parent path";
			return false;
		} else {
			//print_r( $parent );
			if ( ($dup_path = $this->is_duplicate( $parent, $data )) !== false ) {
				if( $fail_on_dup ) {
					$this->error[] = "Invalid data: Duplicate data";
					return false;
				} else {
					return $dup_path;
				}
			}
			//print_r( $parent );
			if ( $parent_path == '' )
				$target_depth = 0;
			else
				$target_depth = count( explode( '_', $parent_path ) );

			$target_meta = &$this->get_meta( $target_depth );

			// Validate the data to the child_meta required field.
			foreach ( $target_meta[ 'required' ] as $check ) {
				if ( ! array_key_exists( $check, $data ) )
					$this->error[] = $check." is missing from your data.";
			}
			if ( count( $this->error ) != 0 )
				return false;

			$new_index = $target_meta[ 'next_index' ];
			$new_path  = ( $parent_path=='' ? $new_index : $parent_path.'_'.$new_index );
			if ( $parent_path == '' )
				$parent[ $new_index ] = array_merge( $data, array( '__path'=>$new_path ) );
			else
				$parent[ 'data' ][ $new_index ] = array_merge( $data, array( '__path'=>$new_path ) );
			$this->raw_scope[ 'meta' ][ $target_depth ][ 'next_index' ]++;

			return $new_path;
		}
	} //function add_entry

	function remove_entry( $path ) {
		// First we need to get the parent path (and the path cannot be '')
		if ( $path == '') {
			$this->error[] = "You cannot explictly remove all data.";
			return false;
		}
		$path = explode( '_', $path );
		if ( count( $path ) > 1) {
			$child = array_pop( $path );
			$parent_path = implode( '_', $path );
		}
		else {
			$child = $path[ 0 ];
			$parent_path = '';
		}
		if ( $parent = &$this->reference_by_path( $parent_path ) == false ) {
			$this->error[] = "Invalid path";
			return false;
		}
		// Simple way to remove array information. Let's hope this works.
		if ( $parent_path == '' )
			unset( $parent[ $child ] );
		else
			unset( $parent[ 'data' ][ $child ] );
		return true;
	} // function remove_entry

	function update_entry( $path, $data ) {
		// You cannot use this function on the root path.
		if ( ! is_array( $data ) ) {
			$this->error[] = "update_entry() requires the data to be an array";
			return false;
		}

		if ( $path == '') {
			$this->error[] = "You cannot update the root path using update_entry()";
			return false;
		}

		$arr_path = explode( '_', $path );
		if ( count( $arr_path ) > 1) {
			$child = array_pop( $arr_path );
			$parent_path = implode( '_', $arr_path );
		} else {
			$child = $arr_path[ 0 ];
			$parent_path = '';
		}
		if ( $parent = &$this->reference_by_path( $parent_path ) == false ) {
			$this->error[] = "update_entry(): Invalid path";
			return false;
		}
		// Need to validate the data at this point.
		if ( $parent_path == '' )
			$target_depth = 0;
		else
			$target_depth = count( explode( '_', $parent_path ) );

		$target_meta = &$this->get_meta( $target_depth );

		// Validate the data to the child_meta required field.
		foreach ( $target_meta[ 'required' ] as $check ) {
			if ( !array_key_exists( $check, $data ) )
				$this->error[] = $check." is missing from your data.";
		}
		if ( count( $this->error ) != 0 )
			return false;

		if ( $parent_path == '' )
			$parent[ $child ] = array_merge( $data, array( '__path' => $path ) );
		else
			$parent[ 'data' ][ $child ] = array_merge( $data, array( '__path' => $path ) );
		return true;
	} //function update_meta

	function to_json( $path = null, $with_meta = true ) {
		if ( is_null( $path ) ) {
			if ( $with_meta )
				return json_encode( $this->raw_scope );
			else
				return json_encode( $this->data );
		} else {
			$data = $this->reference_by_path( $path );
			if( ! $data ) {
				$this->error[] = "Invalid path selected";
				return false;
			}
			if ( $with_meta )
				return json_encode( array( 'meta'=>$this->get_meta(), 'data'=>$data ) );
			else
				return json_encode( $data );
		}
	} // function to_json
} //class ArrayScoper


/*
* Data Format:
*  Array(
*	   'meta' => Array( Meta Information ),
*	   'data' => Array( All Scope Information )
*
* eg.
* Array(
'meta' => Array
(
[ 0 ] => Array
(
'name' => 'State'
'next_index' => 2
'required' => array('long', 'short')
)

[ 1 ] => Array
(=
'name' => 'County'
'next_index' => 6
'required' => array('name')
)
)
'data' => Array
(
'__path'=>''
[ '0' ] => Array
(
'long' => 'Florida'
'short' => 'FL'
__path => '0'
'data' => Array
(
[ '0' ]=>array('__path' => '0_0', 'name' => 'Alachua')
[ '1' ]=>array('__path' => '0_1', 'name' => 'Marion' )
[ '2' ]=>array('__path' => '0_2', 'name' => 'Gilchrist' )
[ '5' ]=>array('__path' => '0_5', name' => 'Manatee' )
)
)
[ '1' ] => Array
(
'__path' => '1';
'long' => 'Georgia'
'short' => 'GA'
'data' => Array
(
[ '3' ] => 'data'=>array('__path' => '1_3', 'name' => 'Appling' )
[ '4' ] => 'data'=>array('__path' => '1_4', name' => 'Athens-Clarke' )
)
)

$ex_scope = new ArrayScoper();
$ex_scope->add_depth('State',  array( 'long', 'short' ) );
$ex_scope->add_depth('County', array( 'name' ) );
$ex_scope->add_entry('',  array( 'long'=>'Florida', 'short'=>'FL' ) );
$ex_scope->add_entry('',  array( 'long'=>'Georgia', 'short'=>'GA' ) );

$ex_scope->add_entry( '0', array( 'name'=>'Alachua' ) );
$ex_scope->add_entry( '0', array( 'name'=>'Marion' ) );
$ex_scope->add_entry( '0', array( 'name'=>'Gilchrist' ) );
$ex_scope->add_entry( '1', array( 'name'=>'Appling' ) );
$ex_scope->add_entry( '1', array( 'name'=>'Athens-Clarke' ) );
$ex_scope->add_entry( '0', array( 'name'=>'Manatee' ) );

echo $ex_scope->to_json()."\n\n";
*/
?>