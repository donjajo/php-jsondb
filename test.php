<?php 

include_once('JsonDB.class.php');

$json_db = new JSONDB();

$json_db->from( 'users.json' );

/* $json_db->insert( 'users.json', 
		[ 
			'name' => 'Steve', 
			'state' => 'Nigeria', 
			'age' => 25 
		]);   */ 
		

$rows = $json_db->where(['name'=>'Steve','age'=>'24'],'AND')->get();

foreach ($rows as $row) {
	print_r($row);
	echo "<br>";
}