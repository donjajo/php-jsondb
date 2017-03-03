<?php 

require( 'JSONDB.Class.php' );
$db = new JSONDB;
//$db->insert( 'users.json', array(  'name' => 'Ke', 'age' => 111, 'state' => 'Im' ) );

/*$db->update( [ 'name' => 'James', 'age' => 22, 'state' => 'Abia' ] )
	->from( 'users.json' )
	
	->trigger()*/;
$db->delete()
	->from( 'users.json' )
	
	->trigger();
print_r( $db->select( 'age,state,name' )
	->from( 'users.json' )
	
	->get() );