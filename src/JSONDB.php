<?php 
declare( strict_types = 1 );
namespace Jajo;

class JSONDB {
	public $file, $content = [];
	private $where, $select, $merge, $update;
	private $delete = false;
	private $last_indexes = [];
	private $order_by = [];
	protected $dir;
	private $json_opts = [];
	const ASC = 1;
	const DESC = 0;

	public function __construct( $dir, $json_encode_opt = JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT ) {
		$this->dir = $dir;
		$this->json_opts[ 'encode' ] = $json_encode_opt;
	}

	private function check_file() {
		/**
		 * Checks and validates if JSON file exists
		 *
		 * @return bool
		*/

		// Checks if JSON file exists, if not create
		if( !file_exists( $this->file ) ) {
			$this->commit();
		}

		// Read content of JSON file
		$content = file_get_contents( $this->file );
		$content = json_decode( $content );

		// Check if its arrays of jSON
		if( !is_array( $content ) && is_object( $content ) ) {
			throw new \Exception( 'An array of json is required: Json data enclosed with []' );
			return false;
		}
		// An invalid jSON file
		elseif( !is_array( $content ) && !is_object( $content ) ) {
			throw new \Exception( 'json is invalid' );
			return false;
		}
		else
			return true;
	}

	public function select( $args = '*' ) {
		/**
		 * Explodes the selected columns into array
		 *
		 * @param type $args Optional. Default *
		 * @return type object
		*/

		// Explode to array
		$this->select = explode( ',', $args );
		// Remove whitespaces
		$this->select = array_map( 'trim', $this->select );
		// Remove empty values
		$this->select = array_filter( $this->select );

		return $this;
	}

	public function from( $file ) {
		/**
		 * Loads the jSON file
		 *
		 * @param type $file. Accepts file path to jSON file
		 * @return type object
		*/

		$this->file = sprintf( '%s/%s.json', $this->dir, str_replace( '.json', '', $file ) ); // Adding .json extension is no longer necessary

		// Reset where
		$this->where( [] );
		$this->content = '';

		// Reset order by
		$this->order_by = [];

		if( $this->check_file() ) {
			$this->content = ( array ) json_decode( file_get_contents( $this->file ) );
		}
		return $this;
	}

	public function where( array $columns, $merge = 'OR' ) {
		$this->where = $columns;
		$this->merge = $merge;
		return $this;
	}

	public function delete() {
		$this->delete = true;
		return $this;
	}

	public function update( array $columns ) {
		$this->update = $columns;
		return $this;
	}

	/**
	 * Inserts data into json file
	 * 
	 * @param string $file json filename without extension
	 * @param array $values Array of columns as keys and values
	 * 
	 * @return array $last_indexes Array of last index inserted
	 */
	public function insert( $file, array $values ) : array {
		$this->from( $file );

		if( !empty( $this->content[ 0 ] ) ) {
			$nulls = array_diff_key( ( array ) $this->content[ 0 ], $values );
			if( $nulls ) {
				$nulls = array_map( function() {
					return '';
				}, $nulls );
				$values = array_merge( $values, $nulls );
			}
		}

		if( !empty( $this->content ) && array_diff_key( $values, (array ) $this->content[ 0 ] ) ) {
			throw new Exception( 'Columns must match as of the first row' );
		}
		else {
			$this->content[] = ( object ) $values;
			$this->last_indexes = [ ( count( $this->content ) - 1 ) ];
			$this->commit();
		}
		return $this->last_indexes;
	}

	public function commit() {
		$f = fopen( $this->file, 'w+' );
		fwrite( $f, ( !$this->content ? '[]' : json_encode( $this->content, $this->json_opts[ 'encode' ] ) ) );
		fclose( $f );
	}

	private function _update() {
		if( !empty( $this->last_indexes ) && !empty( $this->where ) ) {
			foreach( $this->content as $i => $v ) {
				if( in_array( $i, $this->last_indexes ) ) {
					$content = ( array ) $this->content[ $i ];
					if( !array_diff_key( $this->update, $content ) ) {
						$this->content[ $i ] = ( object ) array_merge( $content, $this->update );
					}
					else 
						throw new Exception( 'Update method has an off key' );
				}
				else 
					continue;
			}
		}
		elseif( !empty( $this->where ) && empty( $this->last_indexes ) ) {
			null;
		}
		else {
			foreach( $this->content as $i => $v ) {
				$content = ( array ) $this->content[ $i ];
				if( !array_diff_key( $this->update, $content ) ) 
					$this->content[ $i ] = ( object ) array_merge( $content, $this->update );
				else 
					throw new Exception( 'Update method has an off key ' );
			}
		}
	}

	/**
	 * Prepares data and written to file
	 * 
	 * @return object $this 
	 */
	public function trigger() {
		$content = ( !empty( $this->where ) ? $this->where_result() : $this->content );
		$return = false;
		if( $this->delete ) {
			if( !empty( $this->last_indexes ) && !empty( $this->where ) ) {
				$this->content = array_filter($this->content, function( $index ) {
					return !in_array( $index, $this->last_indexes );
				}, ARRAY_FILTER_USE_KEY );
	
				$this->content = array_values( $this->content );
			}
			elseif( empty( $this->where ) && empty( $this->last_indexes ) ) {
				$this->content = array();
			}
			
			$return = true;
			$this->delete = false;
		}
		elseif( !empty( $this->update ) ) {
			$this->_update();
			$this->update = [];
		}
		else 
			$return = false;
		$this->commit();
		return $this;
	}

	/**
	 * Flushes indexes they won't be reused on next action
	 * 
	 * @return object $this 
	 */
	private function flush_indexes( $flush_where = false ) {
		$this->last_indexes = array();
		if( $flush_where )
			$this->where = array();
	}

	/**
	 * Validates and fetch out the data for manipulation
	 * 
	 * @return array $r Array of rows matching WHERE
	 */
	private function where_result() {
		$this->flush_indexes();

		if( $this->merge == 'AND' ) {
			return $this->where_and_result();
		}
		else {
			$r = [];

			// Loop through the existing values. Ge the index and row
			foreach( $this->content as $index => $row ) {

				// Make sure its array data type
				$row = ( array ) $row;

				// Loop again through each row,  get columns and values
				foreach( $row as $column => $value ) {
					// If each of the column is provided in the where statement
					if( in_array( $column, array_keys( $this->where ) ) ) {
						// To be sure the where column value and existing row column value matches
						if( $this->where[ $column ] == $row[ $column ] ) {
							// Append all to be modified row into a array variable
							$r[] = $row;

							// Append also each row array key
							$this->last_indexes[] = $index;
						}
						else 
							continue;
					}
				}
			}
			return $r;
		}
	}

	/**
	 * Validates and fetch out the data for manipulation for AND
	 * 
	 * @return array $r Array of fetched WHERE statement
	 */
	private function where_and_result() {
		/*
			Validates the where statement values
		*/
		$r = [];

		// Loop through the db rows. Ge the index and row
		foreach( $this->content as $index => $row ) {

			// Make sure its array data type
			$row = ( array ) $row;

			
			//check if the row = where['col'=>'val', 'col2'=>'val2']
			if(!array_diff($this->where,$row)) {
				$r[] = $row;
				// Append also each row array key
				$this->last_indexes[] = $index;			
				
			}
			else continue ;
			

		}
		return $r;
	}	

	public function to_xml( $from, $to ) {
		$this->from( $from );
		if( $this->content ) {
			$element = pathinfo( $from, PATHINFO_FILENAME );
			$xml = '
			<?xml version="1.0"?>
				<' . $element . '>
';
			
			foreach( $this->content as $index => $value ) {
				$xml .= '
				<DATA>';
				foreach( $value as $col => $val ) {
					$xml .= sprintf( '
					<%s>%s</%s>', $col, $val, $col );
				}
				$xml .= '
				</DATA>
				';
			}
			$xml .= '</' . $element . '>';

			$xml = trim( $xml );
			file_put_contents( $to, $xml );
			return true;
		}
		return false;
	}
	
	public function to_mysql( $from, $to, $create_table = true ) {
		$this->from( $from );
		if( $this->content ) {
			$table = pathinfo( $to, PATHINFO_FILENAME );

			$sql = "-- PHP-JSONDB JSON to MySQL Dump
--\r\n\r\n";
			if( $create_table ) {
				$sql .= "
-- Table Structure for `" . $table . "`
--

CREATE TABLE `" . $table . "`
	(
					";
				$first_row = ( array ) $this->content[ 0 ];
				foreach( array_keys( $first_row ) as $column ) {
					$s = '`' . $column . '` ' . $this->_to_mysql_type( gettype( $first_row[ $column ] ) ) ;
					$s .= ( next( $first_row ) ? ',' : '' );
					$sql .= $s;
				}
				$sql .= "
	);\r\n";
			}

			foreach( $this->content as $values ) {
				$values = ( array ) $values;
				$v = array_map( function( $vv ) {
					$vv = ( is_array( $vv ) || is_object( $vv ) ? serialize( $vv ) : $vv );
					return "'" . addslashes( $vv ) . "'";
				}, array_values( $values ) );

				$c = array_map( function( $vv ) {
					return "`" . $vv . "`";
				}, array_keys( $values ) );
				$sql .= sprintf( "INSERT INTO `%s` ( %s ) VALUES ( %s );\n", $table, implode( ', ', $c ), implode( ', ', $v ) );
			}
			file_put_contents( $to, $sql );
			return true;
		}
		else 
			return false;
	}

	private function _to_mysql_type( $type ) {
		if( $type == 'bool' ) 
			$return = 'BOOLEAN';
		elseif( $type == 'integer' ) 
			$return = 'INT';
		elseif( $type == 'double' ) 
			$return = strtoupper( $type );
		else
			$return = 'VARCHAR( 255 )';
		return $return;
	}

	public function order_by( $column, $order = self::ASC ) {
		$this->order_by = [ $column, $order ];
		return $this;
	}

	private function _process_order_by( $content ) {
		if( $this->order_by && $content && in_array( $this->order_by[ 0 ], array_keys( ( array ) $content[ 0 ] ) ) ) {
			/*
				* Check if order by was specified
				* Check if there's actually a result of the query
				* Makes sure the column  actually exists in the list of columns
			*/

			list( $sort_column, $order_by ) = $this->order_by;
			$sort_keys = [];
			$sorted = [];

			foreach( $content as $index => $value ) {
				$value = ( array ) $value;
				// Save the index and value so we can use them to sort
				$sort_keys[ $index ] = $value[ $sort_column ];
			}
			
			// Let's sort!
			if( $order_by == self::ASC ) {
				asort( $sort_keys );
			}
			elseif( $order_by == self::DESC ) {
				arsort( $sort_keys );
			}

			// We are done with sorting, lets use the sorted array indexes to pull back the original content and return new content
			foreach( $sort_keys as $index => $value ) {
				$sorted[ $index ] = ( array ) $content[ $index ];
			}

			$content = $sorted;
		}

		return $content;
	}

	public function get() {
		if($this->where != null) {
			$content = $this->where_result();
		}
		else 
			$content = $this->content; 
		
		if( $this->select && !in_array( '*', $this->select ) ) {
			$r = [];
			foreach( $content as $id => $row ) {
				$row = ( array ) $row;
				foreach( $row as $key => $val ) {
					if( in_array( $key, $this->select ) ) {
						$r[ $id ][ $key ] = $val;
					} 
					else 
						continue;
				}
			}
			$content = $r;
		}

		// Finally, lets do sorting :)
		$content = $this->_process_order_by( $content );
		
		$this->flush_indexes( true );
		return $content;
	}
} 
