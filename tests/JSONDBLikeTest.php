<?php 
declare( strict_types=1 );

use PHPUnit\Framework\TestCase;
use \Jajo\JSONDB;

class LikeTest extends TestCase {
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
	
	public function testLike() : void {
		$result = ( $this->db->select( '*' )
				->from( 'pets' )
				->where([ 'name' => JSONDB::like("at") ])
				->get()
			);
		$this->assertCount(2, $result);
		
		$result = ( $this->db->select( '*' )
				->from( 'pets' )
				->where([ 'kind' => JSONDB::like("tia") ])
				->get()
			);
		$this->assertCount(3, $result);
	}
	
	public function testLikeOr() : void {
	    $result = ( $this->db->select( '*' )
				->from( 'pets' )
				->where([ 
				    'name' => JSONDB::like("at"),
				    'kind' => JSONDB::like("tia")
				])
				->get()
			);
		$this->assertCount(1, $result);;
	}
	
	
}