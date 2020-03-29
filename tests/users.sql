-- PHP-JSONDB JSON to MySQL Dump
--

-- Table Structure for `users`
--

CREATE TABLE `users` 
(
	`name` VARCHAR( 255 ),
	`state` VARCHAR( 255 ),
	`age` INT
);
INSERT INTO `users` ( `name`, `state`, `age` ) VALUES ( 'James£', 'Imo', '48' );
INSERT INTO `users` ( `name`, `state`, `age` ) VALUES ( 'Dummy', 'Rivers', '12' );
INSERT INTO `users` ( `name`, `age`, `state` ) VALUES ( 'Jammy', '21', 'Sokoto' );
INSERT INTO `users` ( `name`, `age`, `state` ) VALUES ( 'Jajo', '', 'Lagos' );
INSERT INTO `users` ( `name`, `age`, `state` ) VALUES ( 'Johnny', '30', 'Ogun' );
INSERT INTO `users` ( `name`, `age`, `state` ) VALUES ( 'Jajo', '50', 'Lagos' );
INSERT INTO `users` ( `name`, `age`, `state` ) VALUES ( 'Johnny', '50', 'Ogun' );
INSERT INTO `users` ( `name`, `age`, `state` ) VALUES ( 'Paulo', '50', 'Algeria' );
INSERT INTO `users` ( `name`, `age`, `state` ) VALUES ( 'Nina', '50', 'Nigeria' );
INSERT INTO `users` ( `name`, `age`, `state` ) VALUES ( 'Ogwo', '49', 'Nigeria' );
INSERT INTO `users` ( `name`, `age`, `state` ) VALUES ( 'Jajo', '89', 'Abia' );
INSERT INTO `users` ( `name`, `age`, `state` ) VALUES ( 'Mitchell', '45', 'Zamfara' );
