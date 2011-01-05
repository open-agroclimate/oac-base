<?php
require_once('array-scoper.php');

/**
 * The class responsible for handling CSV files formatted for WPScoper.
 *
 * CSV Scope Loader expects a CSV file formatted as follows:
 * State_Long, State_Short, County_Name, City_Name
 * Florida, FL, Alachua, Gainesville,
 * Florida, FL, Alachua, Newberry
 * Florida, FL, Marion, Ocala
 * Georgia, GA, Fulton, Atlanta
 * ...
 *
 * Where the scope will create the following meta data:
 * [State = array( long, short )]
 * [County = array(name)]
 * [City  = array(name)]
 * [State => data(County => data( City => name ))]
 */

class CSVScopeLoader {
	public $scope;
	public $file;
	public $error;

	private $headers;
	private $data;
	private $order;	
	
	/**
	 * PHP 4 Style constructor.
	 *
	 * @see CSVScopeLoader::__construct()
	 * 
	 * @since 1.0
	 */
	public function CSVScopeLoader( $csv_file ) {
		__construct( $csv_file );
	}

	/**
	 * Constructs the CSVScopeLoader object
	 *
	 * @param string $csv_file The CSV file describing the scope.
	 *
	 * @since 1.0
	 */
	public function __construct( $csv_file ) {
		$this->error = array();
		
		// Check to see if the file is really there
		if( ! file_exists( $csv_file ) ) {
			$this->error[] = "CSVScopeLoader Error: File ".$csv_file." does not exist.";
		} else {
			$this->file = $csv_file;
			$this->scope = new ArrayScoper();
			$this->headers = array();
			$this->data = array();
			$this->order = array(); 
		}
	}

	/**
	 * Wrapper around the header parsing and data parsing functions
	 *
	 * @see CSVScopeLoader::parse_head()
	 * @see CSVScopeLoader::parse_body()
	 *
	 * @since 1.0
	 */
	public function parse() {
		$retval = true;
		$handle = fopen( $this->file, "r" );
		if( $handle !== false ) {
		} else {
			$this->error[] = "CSVScopeLoader Error: Could not open ".$this->file." for reading.";
			return false;
		}
		if( $this->parse_head( $handle ) ) {
			if( ! $this->parse_body( $handle ) ) {
				$retval = false;
			}
		} else {
			$retval = false;
		}
		fclose( $handle );
		return $retval;
	}


	/**
	 * Splits the header on the last underscore of the string.
	 *
	 * Takes the header string, splits it on the underscore characters,
	 * and pops off the last item. This last item becomes the field name
	 * for that Scope level. The level name itself is everything else,
	 * joined by spaces. Given NO underscores, the field name "name" is
	 * automatically given.
	 *
	 * <code>
	 * <?php
	 * print_r CSVScopeLoader::split_header( "state_of_occupation_long" );
	 * // OUTPUT: array( "state of occupation", "long" );
	 * print_r CSVScopeLoader::split_header( "city" );
	 * // OUTPUT: array( "city", "name" );
	 * ?>
	 * </code>
	 *
	 * @param string $head The column head
	 *
	 * @return array A two-index array [0] => Level Name, [1] => Level Field 
	 *
	 * @since 1.0
	 */
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

	/**
	 * Parses the first line of the CSV file to setup the Scope metadata.
	 * @param resource $fh File handle for the CSV file.
	 *
	 * @since 1.0
	 */
	private function parse_head( $fh ) {
		$meta_data = array();
		$data = fgetcsv( $fh );

		if( $data == null ) {
			$this->error[] = "CSVScopeLoader Error: Invalid file handle supplied in parse_head()";
			return false;
		} elseif( $data === false ) {
			$this->error[] = "CSVScopeLoader Error: Problem parsing data in parse_head()";
			return false;
		} else {
			if( $data[0] == null ){
				$this->error[] = "CSVScopeLoader Error: Blank header supplied";
				return false;
			}
			$req = '';
			$this->headers = $data;
			foreach( $data as $meta ) {
				list( $title, $req ) = CSVScopeLoader::split_header( $meta );
				if( ! array_key_exists( $title, $meta_data ) ) {
					$meta_data[$title] = array();
				}
				if( $req != '' ) {
					$meta_data[$title][] = $req;
				}
			}
			foreach( $meta_data as $name => $required_terms )
				$this->scope->add_depth( $name, $required_terms );
			return true;
		}
	}

	private function parse_body( $fh ) {
		// Load all the CSV information from the file.
		while( ( $data = fgetcsv( $fh ) ) !== false ) {
			$parent = '';
			$parent_header = '';
			$line_data = array();
			
			foreach($data as $index => $value) {
				list($title, $field) = CSVScopeLoader::split_header( $this->headers[$index] );
				if( ! array_key_exists( $title, $this->data ) ) {
					$this->data[$title] = array();
					$line_data[$title] = array();
					if( ! in_array( $title, $this->order ) ) {
						$this->order[] = $title;
					}
				}
				
				$line_data[$title][$field] = $value;
			}
			
			foreach( $line_data as $title => $arr_value ) {
				$depth = array_search( $title, $this->order );
				if( $depth == 0 ) {
					$parr = array('parent'=>'');
				} else {
					$pbuilder = '';
					for( $i = $depth; $i > 0; $i-- ) {
						$parent_title = $this->order[$i-1];
						$parent_counter = count( $this->data[$parent_title] )-1;
						if (($pindex = array_search( $this->data[$parent_title], $line_data[$parent_title])) !== false ) {
							$pbuilder = $pindex.'_'.$pbuilder;
						} else {
							$pbuilder = $parent_counter.'_'.$pbuilder;
						}
					}
					$parr = array('parent'=>rtrim( $pbuilder, '_' ) );
				}
				$arr_value = array_merge( $parr, $arr_value );
				if( count( $this->data[$title] ) > 0 ) {
					if( ! in_array( $arr_value, $this->data[$title] ) ) {
						$this->data[$title][] = $arr_value;
					}
				} else {
					$this->data[$title][] = $arr_value;
				}
			}
		}
		foreach( $this->order as $title ) {
			foreach( $this->data[$title] as $_index => $value ) {
				$parent = $value['parent'];
				unset( $value['parent'] );
				if( ($id = $this->scope->add_entry( $parent, $value, true ) ) === false ) { 
					$this->error = $this->scope->error;
					return false;
				}
			}
		}
		return true;
	} // function parse_body()
} // class CSVScopeLoader
?>
