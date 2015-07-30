<?php

//Simple file to handle user registration.
require 'db.php';

$username = $_POST['username'];
$email = $_POST['email'];
$password = $_POST['password'];

$hashToStoreInDb = password_hash($password, PASSWORD_BCRYPT);

$statement2 = $db->prepare('INSERT INTO accounts(username,hash,email) VALUES(:user,:hash,:email);');
$test = $statement2->execute(array(':user' => $username,':hash' => $hashToStoreInDb, ':email' => $email));




?>