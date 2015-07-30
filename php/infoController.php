<?php
/*
	Written by Jake Schultz on 23/7/15
	This file handles inputting the user information into the database. (SSH, MySQL info)
*/
require 'db.php';

//@param, Post Action which handles which field to update
$action = $_POST['action'];
//@param, The Value to be input from the action
$value = $_POST['value'];
//@param, The username we should add to form the DB
$username = $_POST['username'];

//Encryption class lives in db.php. We should convert all values before storing in the DB.
$converter = new Encryption;

//The following conditions check the action value, prepare an update statement, encode it, then execute the command with the new value.
if ($action == "ssh_username"){
	$statement2 = $db->prepare('UPDATE accounts SET ssh_username = :user WHERE username = :username;');
	$encoded = $converter->encode($value);
	$statement2->execute(array(':user' => $encoded, ':username' => $username));

} else if ($action == "ssh_host"){
	$statement2 = $db->prepare('UPDATE accounts SET ssh_host = :ssh_host WHERE username = :username;');
	$encoded = $converter->encode($value);
	$statement2->execute(array(':ssh_host' => $encoded, ':username' => $username));

} else if ($action == "ssh_port"){
	$statement2 = $db->prepare('UPDATE accounts SET ssh_port = :ssh_port WHERE username = :username;');
	$encoded = $converter->encode($value);
	$statement2->execute(array(':ssh_port' => $encoded, ':username' => $username));

} else if ($action == "mysql_username"){
	$statement2 = $db->prepare('UPDATE accounts SET mysql_username = :mysql_username WHERE username = :username;');
	$encoded = $converter->encode($value);
	$statement2->execute(array(':mysql_username' => $encoded, ':username' => $username));

} else if ($action == "mysql_database_name"){
	$statement2 = $db->prepare('UPDATE accounts SET mysql_database_name = :mysql_database_name WHERE username = :username;');
	$encoded = $converter->encode($value);
	$statement2->execute(array(':mysql_database_name' => $encoded, ':username' => $username));

} else if ($action == "mysql_port"){
	$statement2 = $db->prepare('UPDATE accounts SET mysql_port = :mysql_port WHERE username = :username;');
	$encoded = $converter->encode($value);
	$statement2->execute(array(':mysql_port' => $encoded, ':username' => $username));
} else if ($action == "Type"){
	$statement2 = $db->prepare('UPDATE accounts SET type = :type WHERE username = :username;');
	$statement2->execute(array(':type' => $value, ':username' => $username));
}

?>