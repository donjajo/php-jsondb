<?php 
declare( strict_types=1 );

use PHPUnit\Framework\TestCase;
use \Jajo\JSONDB;

class WhereTest extends TestCase {
    private $db;

	public function load_db() {
		$this->db = new JSONDB( __DIR__ );
	}
	
	// Both 'setUp' and 'tearDown' function is called by phpunit per every test function before test function is called
	public function setUp() {
	    $this->load_db();
	    
	    $names = ['hamster', 'chinchilla', 'dog', 'cat', 'rat', 'chamaeleon', 'turtle', 'chupacabra', 'catoblepas', 'catoblepas'];
	    $kinds = ['rodentia', 'rodentia', 'canivora', 'carnivora', 'rodentia', 'squamata', 'testudines', null, null, 'game-character'];
	    
	    for ($i = 0; $i < count($names); $i++) {
	        $this->db->insert( 'pets', [
    			'name' => $names[$i],
    			'kind' => $kinds[$i],
    			'age' => $i % 3
    		]);
	    }
	}
	
	public function tearDown() {
	    $this->db->delete()
			->from( 'pets' )
			->trigger();
	}
	
	public function testWhereOr() {
	    $result = ( $this->db->select( '*' )
				->from( 'pets' )
				->where([ 
				    'kind' => 'rodentia',
				    'age' => 0
				])->get()
			);
		$this->assertCount(6, $result);
		
		$result = ( $this->db->select( '*' )
				->from( 'pets' )
				->where([ 
				    'kind' => 'squamata',
				    'age' => 2
				])->get()
			);
		$this->assertCount(3, $result);
	}
	
	public function testWhereNullOr() {
		$result = ( $this->db->select( '*' )
				->from( 'pets' )
				->where([ 'kind' => null ])->get()
			);
		$this->assertCount(2, $result);
	}
	
	public function testWhereNullAnd() {
		$result = ( $this->db->select( '*' )
				->from( 'pets' )
				->where([ 
					'name' => 'catoblepas',
					'kind' => null
				], 'AND')->get()
			);
		$this->assertCount(1, $result);
		$this->assertEquals('catoblepas', $result[ 0 ][ 'name' ] );
		$this->assertSame( null, $result[ 0 ][ 'kind' ] );
	}
	
}
?>