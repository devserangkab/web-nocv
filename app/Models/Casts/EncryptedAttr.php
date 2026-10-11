<?php

namespace App\Models\Casts;

use App\Support\SecureCipher;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Kolom sensitif terenkripsi AES-256-GCM: transparan decrypt saat dibaca,
 * encrypt saat disimpan. Baca-tulis selalu plaintext di aplikasi.
 */
class EncryptedAttr implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (blank($value)) {
            return null;
        }

        return app(SecureCipher::class)->decrypt($value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return blank($value) ? null : app(SecureCipher::class)->encrypt((string) $value);
    }
}
