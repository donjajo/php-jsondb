<?php 

require( 'JSONDB.Class.php' );
$db = new JSONDB;
$db->insert( 'users.json', array(  'age' => '11', 'name' => 'philip' ) );

/*$db->update( [ 'name' => 'James', 'age' => 22, 'state' => 'Abia' ] )
	->from( 'users.json' )
	
	->trigger()*/
/*$db->delete()
	->from( 'users.json' )
	
	->trigger();*/
/*print_r( $db->select( '*' )
	->from( 'schools.json' )
	->where( [ 'category' => 2 ] )
	->get() );*/

$db->to_mysql( 'users.json', 'users.sql' );