<?php

//Simple file to handle login
require 'db.php';

$username = $_POST['username'];
$password = $_POST['password'];

$getHash = $db->prepare("SELECT hash FROM accounts WHERE username = :username;");
$getHash ->execute(array(':username' => $username));

$data = $getHash->Fetch();

$isPasswordCorrect = password_verify($password, $data['hash']);

echo $isPasswordCorrect;


?>