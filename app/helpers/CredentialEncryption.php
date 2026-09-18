<?php

class CredentialEncryption
{
    private string $key;
    private string $cipher = 'aes-256-gcm';

    public function __construct(string $key)
    {
        if (empty($key)) {
            throw new \InvalidArgumentException('Encryption key cannot be empty.');
        }
        $this->key = hash('sha256', $key, true);
    }

    public function encrypt(string $plaintext): string
    {
        $iv = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt($plaintext, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
        if ($ciphertext === false) {
            throw new \RuntimeException('Encryption failed: ' . openssl_error_string());
        }
        return base64_encode($iv . $tag . $ciphertext);
    }

    public function decrypt(string $encrypted): string
    {
        $data = base64_decode($encrypted);
        if ($data === false || strlen($data) < 28) {
            throw new \RuntimeException('Invalid encrypted data.');
        }
        $iv = substr($data, 0, 12);
        $tag = substr($data, 12, 16);
        $ciphertext = substr($data, 28);
        $plaintext = openssl_decrypt($ciphertext, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($plaintext === false) {
            throw new \RuntimeException('Decryption failed: ' . openssl_error_string());
        }
        return $plaintext;
    }
}
