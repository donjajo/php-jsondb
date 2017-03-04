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
This WHERE only works as AND Operator at the moment
```php
	<?php 
	$users = $json_db->select( 'name, state'  )
		->from( 'users.json' )
		->where( [ 'name' => 'Thomas' ] )
		->get();
	print_r( $users );
	
	$users = $json_db->select( 'name, state'  )
		->from( 'users.json' )
		->where( [ 'name' => 'Thomas', 'state' => 'Nigeria' ] )
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
		->from( 'users.json' )
		->where( [ 'name' => 'Thomas' ] )
		->trigger();

```
*Without the **where()** method, it will deletes all rows*

#### Exporting to MySQL
You can export the JSON back to SQL file by using this method and providing an output
```php
        <?php 
        $json_db->to_mysql( 'users.json', 'users.sql' );
```
Disable CREATE TABLE
```php
        <?php 
        $json_db->to_mysql( 'users.json', 'users.sql', false );
```

