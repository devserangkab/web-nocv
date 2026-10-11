<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Kunci Enkripsi Data Sensitif (AES-256-GCM)
    |--------------------------------------------------------------------------
    |
    | Kunci dedikasi (base64, 32 bytes) yang terpisah dari APP_KEY, dipakai
    | SecureCipher untuk mengenkripsi kolom sensitif. Generate ulang:
    |   php -r "echo base64_encode(random_bytes(32));"
    |
    */

    'key' => env('SECURE_KEY'),
];
