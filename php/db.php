<?php
//DB Setup Information.
$dsn = 'mysql:dbname=jssbackuputil;host=localhost;port=3306';
$Dusername = 'root';
$password = '';
try {
    $db = new PDO($dsn, $Dusername, $password); // also allows an extra parameter of configuration
} catch(PDOException $e) {
    die('Could not connect to the database:<br/>' . $e);
}

//A class for encoding information into the DB
//CHANGE THE KEY!!!
class Encryption {
    //**** CHANGE THIS KEY *****
    var $key = "superawesomekey";

    public function encode($string){
        $iv = mcrypt_create_iv(
            mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC),
            MCRYPT_DEV_URANDOM
        );

        $encrypted = base64_encode(
            $iv .
            mcrypt_encrypt(
                MCRYPT_RIJNDAEL_256,
                hash('sha256', $this->key, true),
                $string,
                MCRYPT_MODE_CBC,
                $iv
            )
        );
        return $encrypted;
    }
    public function decode($string){
        $data = base64_decode($string);
        $iv = substr($data, 0, mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC));

        $decrypted = rtrim(
            mcrypt_decrypt(
                MCRYPT_RIJNDAEL_256,
                hash('sha256', $this->key, true),
                substr($data, mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC)),
                MCRYPT_MODE_CBC,
                $iv
            ),
            "\0"
        );
        return $decrypted;
    }
}
?>