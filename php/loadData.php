<?php
/*
	Written by Jake Schultz on 23/7/15
	This file checks for inputted DB information.
*/
require 'db.php';

$username = $_POST['username'];

//Select all of the information to check if we should prompt to enter DB info.
$getInfo = $db->prepare("SELECT * FROM accounts WHERE username = :username;");
$getInfo ->execute(array(':username' => $username));
$data = $getInfo->Fetch();

$converter = new Encryption;

echo $converter -> decode($data['ssh_host']);

?>