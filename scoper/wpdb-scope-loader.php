<?php
$wp_root = explode('wp-content', __FILE__);
$wp_root = $wp_root[0];


if( file_exists( $wp_root.'wp-load.php' ) ) {
	require_once( $wp_root.'wp-load.php' );
}

require_once( 'scope.php' );

// Temporary until FE completed
require_once( 'wp-scoper.php' );

class WPDBScopeLoader {
	public $scope;
	private $db;
	private $source_table = null;
	private $source_columns = array();
	private $field_listing = array();
	private $mapped_fields = array();

	public function __construct( $source_table ) {
		global $wpdb;
		$this->db = &$wpdb;
		$this->scope = new Scope();
		$tables = $this->db->get_results( 'SHOW TABLES', ARRAY_N );
		// Quick test that the table exists and is selectable by the current WPDB user
		if( is_array( $tables[0] ) ) {
			// MySQL 5.0.2+
			foreach( $tables as $table_info ) {
				if( in_array( $source_table, $table_info ) ) {
					$this->source_table = $source_table;
				}
			}
		} else {
			// MySQL < 5.0.2
			if( in_array( $source_table, $tables ) ) {
				$this->source_table = $source_table;
			}
		}
		$this->scope->extra['loader']        = __CLASS__;
		$this->scope->extra['table']         = $this->source_table;
		$this->set_source_columns();
		$this->scope->extra['source_cols']   = $this->get_source_columns();
	}

	private static function column_filter( $var ) {
		return ( substr( $var, 0, 9) == 'oac_scope' ) ? false : true;
	}
	
	private function set_source_columns() {
		if ( is_null( $this->source_table ) ) return false;
		$this->db->query( "SELECT * FROM {$this->source_table} LIMIT 1" );
		//$this->source_columns = $this->db->get_col_info();
		$this->source_columns = array_filter( $this->db->get_col_info(), 'WPDBScopeLoader::column_filter' );
	}

	public function get_source_columns() {
		return $this->source_columns;
	}

	static private function split_header( $head ) {
		$mdata = explode('_', $head);
		if ( count( $mdata ) == 1 ) {
			$title = $mdata[0];
			$req = 'name';
		} else {
			$req = array_pop( $mdata );
			$title = implode( ' ', $mdata );
		}
		return array( $title, $req );
	}

	public function addField( $field_name, $column_name ) {
		// Check to see if the column_name exists
		if( ! in_array( $column_name, $this->source_columns ) ) {
			return false;
		}
		list( $title, $field) = self::split_header( $field_name );
		if( ! isset( $this->field_listing[$title] ) ) {
			$this->field_listing[$title] = array();
		}
		if( in_array( $field, $this->field_listing ) ) {
			return false;
		}
		$this->field_listing[$title][] = $field;
		$this->mapped_fields[$field] = $column_name;
		$this->scope->extra['column_map'][$title.'|'.$field] = $column_name;
		print_r( $this );
	}


	public function import_scope( $scope ) {
		// First check to make sure it's the right type
		if( is_object( $scope ) ) {
			if( get_class( $scope ) != "Scope" ) {
				return false;
			}
		} else {
			return false;
		}
		$this->scope = $scope;
		return true;
	}

	public function generateScope( $scope_name = '') {
		if( ! in_array( "oac_scope_{$scope_name}_id", $this->source_columns ) ) {
			echo "Altering table...";
			$this->db->query( "ALTER TABLE {$this->source_table} ADD COLUMN oac_scope_{$scope_name}_id varchar(25)" );
		}
		// First we build the meta data
		if( count( $this->mapped_fields ) == 0 ) return false;
		foreach( $this->field_listing as $title => $field ) {
			$this->scope->add_layer( $title, $field );
		}

		$finder = 'SELECT DISTINCT ';
		foreach( $this->mapped_fields as $column ) {
			$finder .= $column.', ';
		}
		$finder = rtrim( $finder, ', ' );
		$finder .= " FROM {$this->source_table}";
		$data = $this->db->get_results( $finder, ARRAY_A );
		foreach( $data as $item ) {
			$current_title = null;
			$update_row = array();
			$parent = '';
			foreach( $this->field_listing as $title => $fields ) {
				$current_title = array();
				foreach( $fields as $field ) {
					$update_row[$this->mapped_fields[$field]] = $item[$this->mapped_fields[$field]]; 
					$current_title[$field] = $item[$this->mapped_fields[$field]];
				}
				//print_r( $update_row );
				$parent = $this->scope->add_node( $parent, $current_title, false ); 
			}
			$this->db->update($this->source_table, array( "oac_scope_{$scope_name}_id" => $parent ), $update_row, array( '%s' ) );
		}
	}
}


$test = new WPDBScopeLoader( 'cropvariety_py' );
$test->addField( 'Cultivo_cnombre', 'crop' );
$test->addField( 'Variedad_vnombre', 'variety' );
$test->generateScope( 'cropvariety' );
print_r( $test->scope );
$wpscope = new WPScoper( 'cropvariety' );
$wpscope->scope = $test->scope;
$wpscope->save();
// 
// // $test = new WPDBScopeLoader('location_py');
// // $test->addField( 'Ubicación_nombre', 'location' );
// // $test->generateScope( 'location' );
// // //print_r( $test->scope );
// // $wpscope = new WPScoper( 'location' );
// // $wpscope->scope = $test->scope;
// // $wpscope->save();
// 
// //// EVIL HARDCODE
// print_r( $wpscope );
$wpscope = new WPScoper( 'cropvariety' );
echo $wpscope->generateNestedDDL( '', true );


?>
