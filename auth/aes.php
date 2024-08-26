<?php
// handles encryption and decryption of data using aes-256-cbc

// https://www.php.net/manual/en/function.openssl-encrypt.php
// Example #2 AES Authenticated Encryption example prior to PHP 7.1

require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// function for encrypting data useing aes-256-cbc
function aesEncrypt($data)
{
    $cipher = $_ENV['AES_CIPHER_MODE'];
    $key = $_ENV['AES_KEY'];
    $iv = openssl_random_pseudo_bytes(16);
    $encryptedText = openssl_encrypt($data, $cipher, $key, OPENSSL_RAW_DATA, $iv);
    $hmac = hash_hmac('sha256', $encryptedText, $key, true);
    return base64_encode($iv . $hmac . $encryptedText);
}

// function for decrypting data useing aes-256-cbc
function aesDecrypt($data)
{
    $cipher = $_ENV['AES_CIPHER_MODE'];
    $key = $_ENV['AES_KEY'];
    $encryptedText = base64_decode($data);
    if ($encryptedText === false) {
        return 'Invalid base64 data';
    }
    // destructure encrypted text into iv, hmac, and encrypted
    $iv = substr($encryptedText, 0, 16);
    $hmac = substr($encryptedText, 16, 32);
    $encrypted = substr($encryptedText, 16 + 32);
    $plaintext = openssl_decrypt($encrypted, $cipher, $key, OPENSSL_RAW_DATA, $iv);
    if ($plaintext === false) {
        return 'Decryption failed';
    }
    $calcmac = hash_hmac('sha256', $encrypted, $key, true);
    if (hash_equals($hmac, $calcmac)) {
        return $plaintext;
    }
    return 'Data modified';
}
