<?php 

include_once('JSONDB.Class.php');

$json_db = new JSONDB();

// $json_db->to_xml( 'users.json', 'bla.xml' );
$json_db->from( 'users.json' );

/* $json_db->insert( 'users.json', 
		[ 
			'name' => 'Steve', 
			'state' => 'Nigeria', 
			'age' => 25 
		]);   */ 
		

$rows = $json_db->order_by( 'name', JSONDB::ASC )->get();
?>
<pre>
	<?php print_r( $rows );?>
</pre>
