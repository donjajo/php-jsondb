## php-jsondb
A PHP Class that reads JSON file as a database. Use for sample DBs

### Usage
Include the file `<?php include( 'JSONDB.Class.php' );?>`
#### Initialize
```php
	<?php 
	$json_db = new JSONDB();
```

#### Inserting
Insert into your new JSON file. Using *users.json* as example here

**NB:** *Columns inserted first will be the only allowed column on other inserts*

```php
	<?php
		$json_db->insert( 'users.json', 
		[ 
			'name' => 'Thomas', 
			'state' => 'Nigeria', 
			'age' => 22 
		]);
```

#### Get 
Get back data, just like MySQL in PHP

##### All columns:
```php
	<?php
	$users = $json_db->select( '*' )
		->from( 'users.json' )
		->get();
	print_r( $users );
```

##### Custom Columns:
```php
	<?php 
	$users = $json_db->select( 'name, state'  )
		->from( 'users.json' )
		->get();
	print_r( $users );
	
```

##### Where Statement:
Only an array key and value is supported at the moment
```php
	<?php 
	$users = $json_db->select( 'name, state'  )
		->from( 'users.json' )
		->where( [ 'name' => 'Thomas' ] )
		->get();
	print_r( $users );
	
```

#### Updating Row
You can also update same JSON file with these methods
```php
	<?php 
	$json_db->update( [ 'name' => 'Oji', 'age' => 10 ] )
		->from( 'users.json' )
		->where( [ 'name' => 'Thomas' ] )
		->get();
	
```
*Without the **where()** method, it will update all rows*

#### Deleting Row
```php
	<?php
	$json_db->delete()
		->from( 'user.json' )
		->where( [ 'name' => 'Thomas' ] )
		->trigger();

```
*Without the **where()** method, it will deletes all rows*

**PS:** Do not use this code on production server
