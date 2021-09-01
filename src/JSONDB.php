<?php 
declare( strict_types = 1 );
namespace Jajo;

class JSONDB {
	public $file, $content = [];
	private $fp;
	private $load;
	private $where, $select, $merge, $update;
	private $delete = false;
	private $last_indexes = [];
	private $order_by = [];
	protected $dir;
	private $json_opts = [];

	const ASC = 1;
	const DESC = 0;
	const AND = "AND";
	const OR = "OR";

	public function __construct( $dir, $json_encode_opt = JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT ) {
		$this->dir = $dir;
		$this->json_opts[ 'encode' ] = $json_encode_opt;
	}

	public function check_fp_size() {
		$size = 0;
		$cur_size = 0;

		if ( $this->fp ) {
			$cur_size = ftell( $this->fp );
			fseek( $this->fp, 0, SEEK_END );
			$size = ftell( $this->fp );
			fseek( $this->fp, $cur_size, SEEK_SET );
		}

		return $size;
	}

	private function check_file() {
		/**
		 * Checks and validates if JSON file exists
		 *
		 * @return bool
		*/

		// Checks if JSON file exists, if not create
		if( !file_exists( $this->file ) ) {
			touch( $this->file );
			// $this->commit();
		}

		if ( 'partial' == $this->load ) {
			$this->fp = fopen( $this->file, 'r+' );
			if ( ! $this->fp ) {
				throw new \Exception( 'Unable to open json file' );
			}

			if ( ( $size = $this->check_fp_size() ) ) {
				$content = get_json_chunk( $this->fp );

				// We could not get the first chunk of JSON. Lets try to load everything then
				if ( ! $content ) {
					$content = fread( $this->fp, $size );
				} else {
					// We got the first chunk, we still need to put it into an array
					$content = sprintf( '[%s]', $content );
				}

				$content = json_decode( $content, true );
			} else {
				// Empty file. File was just created
				$content = array();
			}
		} else {
			// Read content of JSON file
			$content = file_get_contents( $this->file );
			$content = json_decode( $content, true );
		}

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
		else {
			$this->content = $content;
			return true;
		}
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

	public function from( $file, $load = 'full' ) {
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
		$this->load = $load;

		// Reset order by
		$this->order_by = [];

		if( $this->check_file() ) {
			//$this->content = ( array ) json_decode( file_get_contents( $this->file ) );
		}
		return $this;
	}

	public function where( array $columns, $merge = 'OR' ) {
		$this->where = $columns;
		$this->merge = $merge;
		return $this;
	}

	/**
	 * Implements regex search on where statement 
	 * 
	 * @param	string	$pattern			Regex pattern
	 * @param	int		$preg_grep_flags	Flags for preg_grep(). See - https://www.php.net/manual/en/function.preg-match.php
	 */
	public static function regex( string $pattern, int $preg_match_flags = 0 ) : object {
		$c = new \stdClass();
		$c->is_regex = true;
		$c->value = $pattern;
		$c->options = $preg_match_flags;

		return $c;
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
	public function insert( $file, array $values ) {
		$this->from( $file, 'partial' );

		$first_row =  current( $this->content );
		$this->content = array();

		if( ! empty( $first_row ) ) {
			$unmatched_columns = 0;

			foreach ( $first_row as $column => $value ) {
				if ( ! isset( $values[ $column ] ) ) {
					$values[ $column ] = null;
				}
			}

			foreach ( $values as $col => $val ) {
				if ( ! isset( $first_row[ $col ] ) ) {
					$unmatched_columns = 1;
					break;
				}
			}

			if ( $unmatched_columns ) {
				throw new \Exception( 'Columns must match as of the first row' );
			}
		}

		$this->content[] = $values;
		// $this->last_indexes = [ ( count( $this->content ) - 1 ) ];
		$this->commit();

		// return $this->last_indexes;
	}

	public function commit() {
		if ( $this->fp && is_resource( $this->fp ) ) {
			$f = $this->fp;
		} else {
			$f = fopen( $this->file, 'w+' );
		}

		if ( 'full' === $this->load ) {
			// Write everything back into the file
			fwrite( $f, ( !$this->content ? '[]' : json_encode( $this->content, $this->json_opts[ 'encode' ] ) ) );
		} else if ( 'partial' === $this->load ) {
			// Append it
			$this->append();
		} else {
			// Unknown load type
			fclose( $f );
			throw new \Exception( 'Write fail: Unkown load type provided', 'write_error' );
		}

		fclose( $f );
	}

	private function append() {
		$size = $this->check_fp_size();
		$per_read = $size > 64 ? 64 : $size;
		$read_size = -$per_read;
		$lstblkbrkt = false;
		$lastinput = false;
		$i = $size;
		$data = json_encode( $this->content, $this->json_opts['encode'] );

		if ( $size ) {
			fseek( $this->fp, $read_size, SEEK_END );

			while ( ( $read = fread( $this->fp, $per_read ) ) ) {
				$per_read = $i - $per_read < 0 ? $i : $per_read;
				if ( false === $lstblkbrkt ) {
					$lstblkbrkt = strrpos( $read, ']', 0 );
					if ( false !== $lstblkbrkt ) {
						$lstblkbrkt = ( $i - $per_read ) + $lstblkbrkt;
					}
				}

				if ( false !== $lstblkbrkt ) {
					$lastinput = strrpos( $read, '}' );
					if ( false !== $lastinput ) {
						$lastinput = ( $i - $per_read ) + $lastinput;
						break;
					}
				}

				$i -= $per_read; 
				$read_size += -$per_read;
				if ( abs( $read_size ) >= $size ) {
					break;
				}
				fseek( $this->fp, $read_size, SEEK_END );
			}
		}

		if ( false !== $lstblkbrkt ) {
			// We found existing json data, don't write extra [
			$data = substr( $data, 1 );
			if ( false !== $lastinput ) {
				$data = sprintf( ',%s', $data );
			}
		} else {
			if ( $size > 0 ) {
				throw new \Exception( 'Append error: JSON file looks malformed' );
			}

			$lstblkbrkt = 0;
		}

		fseek( $this->fp, $lstblkbrkt, SEEK_SET );
		fwrite( $this->fp, $data );
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

		if ( $this->fp && is_resource( $this->fp ) ) {
			fclose( $this->fp );
		}
	}

	private function intersect_value_check($a, $b) {
		if( $b instanceof \stdClass ) {
			if( $b->is_regex ) {
				return !preg_match( $b->value, (string)$a, $_, $b->options );
			}

			return -1;
		}

		if( $a instanceof \stdClass ) {
			if( $a->is_regex ) {
				return !preg_match( $a->value, (string)$b, $_, $a->options );
			}

			return -1;
		}
		
		return strcasecmp((string)$a, (string)$b);
	}

	/**
	 * Validates and fetch out the data for manipulation
	 * 
	 * @return array $r Array of rows matching WHERE
	 */
	private function where_result() {
		$this->flush_indexes();

		if( $this->merge == "AND" ) {
			return $this->where_and_result();
		}
		else {
			// Filter array
			$r = array_filter($this->content, function( $row, $index ) {
				$row = (array) $row; // Convert first stage to array if object
				
				// Check for rows intersecting with the where values.
				if( array_uintersect_uassoc( $row, $this->where, array($this, "intersect_value_check" ), "strcasecmp" ) /*array_intersect_assoc( $row, $this->where )*/ ) {
					$this->last_indexes[] =  $index;
					return true;
				}
				
				return false;
			}, ARRAY_FILTER_USE_BOTH );
			
			// Make sure every  object is turned to array here.
			return array_values( obj_to_array( $r ) );
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
			if(!array_udiff_uassoc($this->where,$row, array($this, "intersect_value_check" ), "strcasecmp" ) ) {
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
	
	/**
	 * Generates SQL from JSON
	 * 
	 * @param	string	$from			JSON file to get data from
	 * @param	string	$to				Filename to write SQL into
	 * @param	bool	$create_table	If to include create table in this export
	 * 
	 * @return	bool	Returns true if file was created, else false
	 */
	public function to_mysql( string $from, string $to, bool $create_table = true ) : bool {
		$this->from( $from ); // Reads the JSON file
		if( $this->content ) {
			$table = pathinfo( $to, PATHINFO_FILENAME ); // Get filename to use as table

			$sql = "-- PHP-JSONDB JSON to MySQL Dump\n--\n\n";
			if( $create_table ) {
				// Should create table, generate a CREATE TABLE statement using the column of the first row
				$first_row = ( array ) $this->content[ 0 ];
				$columns = array_map( function( $column ) use ( $first_row ) {
					return sprintf( "\t`%s` %s", $column, $this->_to_mysql_type( gettype( $first_row[ $column ] ) ) );
				}, array_keys($first_row));
				
				$sql = sprintf( "%s-- Table Structure for `%s`\n--\n\nCREATE TABLE `%s` \n(\n%s\n);\n", $sql, $table, $table, implode( ",\n", $columns ) );
			}

			foreach( $this->content as $row ) {
				$row = ( array ) $row;
				$values = array_map( function( $vv ) {
					$vv = ( is_array( $vv ) || is_object( $vv ) ? serialize( $vv ) : $vv );
					return sprintf( "'%s'", addslashes( (string)$vv ) );
				}, array_values( $row ) );

				$cols = array_map( function( $col ) {
					return sprintf( "`%s`", $col );
				}, array_keys( $row ) );
				$sql .= sprintf( "INSERT INTO `%s` ( %s ) VALUES ( %s );\n", $table, implode( ', ', $cols ), implode( ', ', $values ) );
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
