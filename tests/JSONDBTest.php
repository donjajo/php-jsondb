<?php 
declare( strict_types=1 );

use PHPUnit\Framework\TestCase;
use \Jajo\JSONDB;

class InsertTest extends TestCase {
	private $db;

	protected function setUp(): void {
		$this->db = new JSONDB(__DIR__);
	}

	public function tearDown() {
		@unlink( __DIR__ . '/users.sql' );
	}

	public function testInsert() : void {
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

		$user = $this->db->select( '*' )
			->from( 'users' )
			->where( [ 'name' => $name, 'state' => $state, 'age' => $age ], 'AND' )
			->get();

		$this->db->insert( "users", array(
			"name" => "Dummy",
			"state" => "Lagos",
			"age" => 12
		));

		$this->assertEquals( $name, $user[0]['name'] );
	}

	public function testGet() : void {
		printf( "\nCheck exist\n" );
		$users = ( $this->db->select( '*' )
			->from( 'users' )
			->get() );
		$this->assertNotEmpty( $users );
	}

	public function testWhere() : void {
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

	public function testMultiWhere() : void {
		$this->db->insert( "users", array(
			"name" => "Jajo",
			"age" => null,
			"state" => "Lagos"
		));

		$this->db->insert( "users", array(
			"name" => "Johnny",
			"age" => 30,
			"state" => "Ogun"
		));

		$result = $this->db->select( "*" )->from( "users" )->where( array( "age" => null, "name" => "Jajo" ) )->get();
		$this->assertEquals( 'Jajo', $result[ 0 ][ 'name' ] );
	}

	public function testAND() : void {
		$this->db->insert( "users", array(
			"name" => "Jajo",
			"age" => 50,
			"state" => "Lagos"
		));

		$this->db->insert( "users", array(
			"name" => "Johnny",
			"age" => 50,
			"state" => "Ogun"
		));

		$result = $this->db->select( "*" )->from( "users" )->where( array( "age" => 50, "name" => "Jajo" ), JSONDB::AND )->get();

		$this->assertEquals( 1, count($result) );
		$this->assertEquals( "Jajo", $result[0][ 'name' ] );
	}

	public function testRegexAND() : void {
		$this->db->insert( "users", array(
			"name" => "Paulo",
			"age" => 50,
			"state" => "Algeria"
		));

		$this->db->insert( "users", array(
			"name" => "Nina",
			"age" => 50,
			"state" => "Nigeria"
		));

		$this->db->insert( "users", array(
			"name" => "Ogwo",
			"age" => 49,
			"state" => "Nigeria"
		));

		$result = ($this->db->select( "*" )
			->from( "users" )
			->where( array( 
				"state" => JSONDB::regex( "/ria/" ), 
				"age" => JSONDB::regex( "/5[0-9]/" ) 
			), JSONDB::AND )
			->get()
		);
		
		$this->assertEquals( 2, count($result) );
		$this->assertEquals( "Paulo", $result[0][ "name" ] );
		$this->assertEquals( "Nina", $result[1][ "name" ] );
	}

	public function testRegex() : void {
		$this->db->insert( "users", array(
			"name" => "Jajo",
			"age" => 89,
			"state" => "Abia"
		));

		$this->db->insert( "users", array(
			"name" => "Mitchell",
			"age" => 45,
			"state" => "Zamfara"
		));

		$result = ( $this->db->select("*")
			->from( "users")
			->where(array( "state" => JSONDB::regex( "/Zam/" ) ) )
			->get());
		
		$this->assertEquals( 'Mitchell', $result[ 0 ][ 'name' ] );
	}

	public function testUpdate() : void {
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

	public function testSQLExport() : void {
		$this->db->to_mysql( "users", "tests/users.sql" );

		$this->assertTrue(file_exists( "tests/users.sql" ) );
	}

	public function testDelete() : void {
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