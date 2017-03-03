<?php 

require( 'JSONDB.Class.php' );
$db = new JSONDB;
//$db->insert( 'users.json', array(  'name' => 'Ke', 'age' => 111, 'state' => 'Imo' ) );

/*$db->update( [ 'name' => 'James' ] )
	->from( 'users.json' )
	->where( [ 'age' => 12 ] )
	->trigger();*/
/*$db->delete()
	->from( 'users.json' )
	->where( [ 'state' => 'lagos' ] )
	->trigger();*/
print_r( $db->select( 'age,state,name' )
	->from( 'users.json' )
	->where( [ 'name' => 'Ogee' ] )
	->get() );