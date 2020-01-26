<?php 
declare( strict_types=1 );

use PHPUnit\Framework\TestCase;
use \Jajo\JSONDB;

class WhereTest extends TestCase {
    private $db;

	public function load_db() {
		$this->db = new JSONDB( __DIR__ );
	}
	
	public function setUp() {
	    $this->load_db();
	    
	    $names = ['hamster', 'chinchilla', 'dog', 'cat', 'rat', 'chamaeleon', 'turtle'];
	    $kinds = ['rodentia', 'rodentia', 'canivora', 'carnivora', 'rodentia', 'squamata', 'testudines'];
	    
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
				    'age' => 1
				])->get()
			);
		var_dump($result);
		$this->assertCount(4, $result);
	}
}
?>