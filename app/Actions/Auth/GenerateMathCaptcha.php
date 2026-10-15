<?php

namespace App\Actions\Auth;

use Illuminate\Support\Str;

class GenerateMathCaptcha
{
    private const SESSION_KEY = 'auth.math_captcha';

    public function __invoke(): array
    {
        [$question, $answer] = $this->makeQuestion();

        $options = $this->makeOptions($answer);

        $challengeId = Str::random(40);

        session()->put(self::SESSION_KEY, [
            'id' => $challengeId,
            'answer' => (string) $answer,
            'expires_at' => now()->addMinutes(5)->timestamp,
        ]);

        return [
            'id' => $challengeId,
            'question' => $question,
            'options' => $options,
        ];
    }

    private function makeQuestion(): array
    {
        return match (random_int(1, 5)) {
            1 => $this->trace(),
            2 => $this->determinant(),
            3 => $this->exponent(),
            4 => $this->sigma(),
            5 => $this->zeroPower(),
        };
    }

    private function trace(): array
    {
        $a = random_int(0, 7);
        $b = random_int(0, 7);
        $c = random_int(0, 7);
        $d = random_int(0, 7);

        return [
            "tr([[{$a}, {$b}], [{$c}, {$d}]]) = ?",
            $a + $d,
        ];
    }

    private function determinant(): array
    {
        do {
            $a = random_int(0, 4);
            $b = random_int(0, 4);
            $c = random_int(0, 4);
            $d = random_int(0, 4);

            $answer = ($a * $d) - ($b * $c);
        } while (abs($answer) > 7);

        return [
            "det([[{$a}, {$b}], [{$c}, {$d}]]) = ?",
            $answer,
        ];
    }

    private function exponent(): array
    {
        $base = random_int(2, 3);
        $multiply = (bool) random_int(0, 1);

        if ($multiply) {
            $a = random_int(0, 3);
            $b = random_int(0, 3 - $a);

            $answer = $base ** ($a + $b);

            return [
                "{$base}^{$a} × {$base}^{$b} = ?",
                $answer,
            ];
        }

        $a = random_int(0, 3);
        $b = random_int(0, $a);

        $answer = $base ** ($a - $b);

        return [
            "{$base}^{$a} ÷ {$base}^{$b} = ?",
            $answer,
        ];
    }

    private function sigma(): array
    {
        $end = random_int(2, 4);
        $double = (bool) random_int(0, 1);

        if ($double) {
            $answer = $end * ($end + 1);

            return [
                "Σ(k=1..{$end}) 2k = ?",
                $answer,
            ];
        }

        $answer = ($end * ($end + 1)) / 2;

        return [
            "Σ(k=1..{$end}) k = ?",
            (int) $answer,
        ];
    }

    private function zeroPower(): array
    {
        $base = [2, 3, 5, 7][random_int(0, 3)];

        return ["{$base}^0 = ?", 1];
    }

    private function makeOptions(int $answer): array
    {
        $options = [(string) $answer];

        $min = min(0, $answer - 5);
        $max = max(0, $answer + 5);

        while (count($options) < 4) {
            $candidate = (string) random_int($min, $max);

            if (! in_array($candidate, $options, true)) {
                $options[] = $candidate;
            }
        }

        shuffle($options);

        return $options;
    }
}
