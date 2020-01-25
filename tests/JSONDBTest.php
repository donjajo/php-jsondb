<?php 
declare( strict_types=1 );

use PHPUnit\Framework\TestCase;
use \Jajo\JSONDB;

class InsertTest extends TestCase {
	private $db;

	public function load_db() {
		$this->db = new JSONDB( __DIR__ );
	}

	public function testInsert() : void {
		$this->load_db();
		$names = [ 'James£', 'John£', 'Oji£', 'Okeke', 'Bola', 'Thomas', 'Ibrahim', 'Smile' ];
		$states = [ 'Abia', 'Lagos', 'Benue', 'Kano', 'Kastina', 'Abuja', 'Imo', 'Ogun' ];
		shuffle( $names );
		shuffle( $states );

		$state = current( $states );
		$name = current( $names );
		$age = mt_rand( 20, 100 );
		printf( "Inserting: \n\nName\tAge\tState\n%s\t%d\t%s", $name, $age, $state );

		$indexes = $this->db->insert( 'users', [
			'name' => $name,
			'state' => $state,
			'age' => $age
		]);

		$this->assertArrayHasKey( 0, $indexes );

		$this->db->insert( "users", array(
			"name" => "Dummy",
			"state" => "Lagos",
			"age" => 12
		));
	}

	public function testGet() : void {
		$this->load_db();
		printf( "\nCheck exist\n" );
		$users = ( $this->db->select( '*' )
			->from( 'users' )
			->get() );
		$this->assertNotEmpty( $users );
	}

	public function testWhere() : void {
		$this->load_db();
		$result = ( $this->db->select( '*' )
				->from( 'users' )
				->where([ 'name' => 'Okeke' ])
				->get()
			);

		// Probably has not inserted. Lets do it then
		if( !$result ) {
			$this->db->insert( 'users', [
				'name' => 'Okeke',
				'age' => 21,
				'state' => 'Enugu'
			]);

			$result = ( $this->db->select( '*' )
				->from( 'users' )
				->where([ 'name' => 'Okeke' ])
				->get()
			);
		}
	
		$this->assertEquals( 'Okeke', $result[ 0 ][ 'name' ] );
	}
	
	public function testWhereLike() : void {
		$this->load_db();

		$result = ( $this->db->select( '*' )
				->from( 'users' )
				->where([ 'name' => JSONDB::like("J") ])
				->get()
			);
			
		if( !$result ) {
			$this->db->insert( 'users', [
				'name' => 'John',
				'age' => 28,
				'state' => 'Ibadan'
			]);

			$result = ( $this->db->select( '*' )
				->from( 'users' )
				->where([ 'name' => JSONDB::like("J") ], 'AND')
				->get()
			);
		}
		
		$this->assertCount(1, $result);
	}

	public function testUpdate() : void {
		$this->load_db();

		$this->db->update([ 'name' => 'Jammy', 'state' => 'Sokoto' ])
			->from( 'users' )
			->where([ 'name' => 'Okeke' ])
			->trigger();
		
		$this->db->update([ "state" => "Rivers"])
			->from( "users" )
			->where([ "name" => "Dummy" ])
			->trigger();

		$result = $this->db->select( '*' )
			->from( 'users' )
			->where([ 'name' => 'Jammy' ])
			->get();

		$this->assertTrue( $result[ 0 ][ 'state' ] == 'Sokoto' && $result[ 0 ][ 'name' ] == 'Jammy' );

	}

	public function testDelete() : void {
		$this->load_db();

		$this->db->delete()
			->from( 'users' )
			->where([ 'name' => 'Jammy' ])
			->trigger();

		$result = $this->db->select( '*' )
			->from( 'users' )
			->where([ 'name' => 'Jammy' ])
			->get();

		$this->assertEmpty( $result );
	}

	public function testDeleteAll() : void {
		$this->load_db();
		
		/* I add a select action with where statement */
		$result_before = $this->db->select( '*' )
			->from( 'users' )
			->where([ 'state' => 'Rivers' ])
			->get();
		
		/* Select action works fine */
		printf("\nCount of select action's result : %d", count($result_before) );
		$this->assertTrue( $result_before[ 0 ][ 'name' ] == 'Dummy');

		/* Original test code by donjajo */
		$this->db->delete()
			->from( 'users' )
			->trigger();

		$result = $this->db->select( '*' )
			->from( 'users' )
			->get();

		/* But delete all action not working and assertion fail*/
		$this->assertEmpty( $result );
	}
}