<?php

namespace App\Actions\Auth;

class ValidateMathCaptcha
{
    private const SESSION_KEY = 'auth.math_captcha';

    public function __invoke(
        string $answer,
        string $challengeId
    ): bool {
        $challenge = session()->get(self::SESSION_KEY);

        if (! is_array($challenge)) {
            return false;
        }

        // Tolak form dari soal lama tanpa mengganggu soal aktif.
        if (
            ! isset($challenge['id']) ||
            ! hash_equals($challenge['id'], $challengeId)
        ) {
            return false;
        }

        // Soal aktif hanya dapat digunakan sekali.
        session()->forget(self::SESSION_KEY);

        if (
            ! isset($challenge['answer'], $challenge['expires_at']) ||
            now()->timestamp > $challenge['expires_at']
        ) {
            return false;
        }

        return hash_equals(
            (string) $challenge['answer'],
            trim($answer)
        );
    }
}
