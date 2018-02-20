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

foreach ($rows as $row) {
	print_r($row);
	echo "<br>";
}