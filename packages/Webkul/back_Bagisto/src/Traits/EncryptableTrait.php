<?php

namespace Webkul\Bagisto\Traits;

trait EncryptableTrait
{
    private const ENCRYPTION_METHOD = 'AES-128-CBC';

    /**
     * Encrypt a value securely.
     *
     * @param string $value The value to be encrypted.
     * @return string The encrypted value.
     */
    private function encryptValue(string $value): string
    {
        $key = config('app.key'); // Use the application key securely
        $iv = random_bytes(openssl_cipher_iv_length(self::ENCRYPTION_METHOD));
        $encrypted = openssl_encrypt($value, self::ENCRYPTION_METHOD, $key, 0, $iv);

        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt a value securely.
     *
     * @param string $encryptedValue The encrypted value to be decrypted.
     * @return string The decrypted value.
     */
    private function decryptValue(string $encryptedValue): string
    {
        $key = config('app.key'); // Use the application key securely
        $data = base64_decode($encryptedValue);
        $ivLength = openssl_cipher_iv_length(self::ENCRYPTION_METHOD);
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);

        return openssl_decrypt($encrypted, self::ENCRYPTION_METHOD, $key, 0, $iv);
    }
}
