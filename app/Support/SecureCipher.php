<?php

namespace App\Support;

use Illuminate\Encryption\Encrypter;
use RuntimeException;

/**
 * Enkripsi data sensitif dengan AES-256-GCM memakai kunci dedikasi
 * (SECURE_KEY) yang terpisah dari APP_KEY.
 */
class SecureCipher
{
    private Encrypter $encrypter;

    public function __construct()
    {
        $key = config('secure.key');

        if (blank($key)) {
            throw new RuntimeException(
                'SECURE_KEY belum diset. Tambahkan ke .env dengan: '.
                'php -r "echo base64_encode(random_bytes(32));"'
            );
        }

        $key = base64_decode($key, true);

        if ($key === false || strlen($key) !== 32) {
            throw new RuntimeException(
                'SECURE_KEY tidak valid — harus base64 dari 32 bytes.'
            );
        }

        $this->encrypter = new Encrypter($key, 'AES-256-GCM');
    }

    public function encrypt(?string $value): ?string
    {
        return $value === null ? null : $this->encrypter->encryptString($value);
    }

    public function decrypt(?string $payload): ?string
    {
        return $payload === null || $payload === '' ? null : $this->encrypter->decryptString($payload);
    }
}
