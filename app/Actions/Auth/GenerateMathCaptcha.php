<?php

namespace App\Actions\Auth;

use Illuminate\Support\Str;

class GenerateMathCaptcha
{
    private const SESSION_KEY = 'auth.math_captcha';

    public function __invoke(): array
    {
        [$question, $answer, $questionHtml] = $this->makeQuestion();

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
            'questionHtml' => $questionHtml,
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

        $html = $this->notation([
            '<span class="italic">tr</span>',
            $this->matrix($a, $b, $c, $d),
            '<span>= ?</span>',
        ]);

        return [
            "tr([[{$a}, {$b}], [{$c}, {$d}]]) = ?",
            $a + $d,
            $html,
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

        $html = $this->notation([
            '<span class="italic">det</span>',
            $this->matrix($a, $b, $c, $d),
            '<span>= ?</span>',
        ]);

        return [
            "det([[{$a}, {$b}], [{$c}, {$d}]]) = ?",
            $answer,
            $html,
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

            $html = $this->notation([
                $this->power($base, $a),
                '<span>×</span>',
                $this->power($base, $b),
                '<span>= ?</span>',
            ]);

            return [
                "{$base}^{$a} × {$base}^{$b} = ?",
                $answer,
                $html,
            ];
        }

        $a = random_int(0, 3);
        $b = random_int(0, $a);

        $answer = $base ** ($a - $b);

        $html = $this->notation([
            $this->power($base, $a),
            '<span>÷</span>',
            $this->power($base, $b),
            '<span>= ?</span>',
        ]);

        return [
            "{$base}^{$a} ÷ {$base}^{$b} = ?",
            $answer,
            $html,
        ];
    }

    private function sigma(): array
    {
        $end = random_int(2, 4);
        $double = (bool) random_int(0, 1);

        if ($double) {
            $answer = $end * ($end + 1);

            $html = $this->notation([
                $this->sigmaSum('k=1', (string) $end),
                '<span>2k</span>',
                '<span>= ?</span>',
            ]);

            return [
                'Σ(k=1..'.$end.') 2k = ?',
                $answer,
                $html,
            ];
        }

        $answer = ($end * ($end + 1)) / 2;

        $html = $this->notation([
            $this->sigmaSum('k=1', (string) $end),
            '<span>k</span>',
            '<span>= ?</span>',
        ]);

        return [
            'Σ(k=1..'.$end.') k = ?',
            (int) $answer,
            $html,
        ];
    }

    private function zeroPower(): array
    {
        $base = [2, 3, 5, 7][random_int(0, 3)];

        $html = $this->notation([
            $this->power($base, 0),
            '<span>= ?</span>',
        ]);

        return [
            "{$base}^0 = ?",
            1,
            $html,
        ];
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

    /**
     * Masukkan semua token notasi ke dalam satu wrapper agar dapat
     * disusun inline oleh view — nilai hanya angka dari server.
     */
    private function notation(array $tokens): string
    {
        return '<span class="inline-flex items-center gap-1.5 leading-tight">'
            .implode('', $tokens)
            .'</span>';
    }

    /** Eksponen: angka kecil melengkung di atas baseline (sup). */
    private function power(int $base, int $exp): string
    {
        return '<span>'.$base
            .'<sup class="text-[0.6em] text-content-tertiary">'.$exp.'</sup>'
            .'</span>';
    }

    /** Sigma dengan batas atas dan bawah ditumpuk di sekitar Σ. */
    private function sigmaSum(string $lower, string $upper): string
    {
        return '<span class="inline-flex flex-col items-center leading-none">'
            .'<span class="text-[0.6em] text-content-secondary">'.$lower.'</span>'
            .'<span class="text-2xl font-light">Σ</span>'
            .'<span class="text-[0.6em] text-content-secondary">'.$upper.'</span>'
            .'</span>';
    }

    /** Matriks 2×2 gaya bmatrix: kurung siku memanjang mengapit grid angka. */
    private function matrix(int $a, int $b, int $c, int $d): string
    {
        return '<span class="inline-flex items-center gap-0.5 leading-none">'
            .'<span class="text-3xl font-thin text-content-tertiary scale-y-150">[</span>'
            .'<span class="grid grid-cols-2 gap-x-2 gap-y-0.5 text-center">'
            .'<span>'.$a.'</span><span>'.$b.'</span>'
            .'<span>'.$c.'</span><span>'.$d.'</span>'
            .'</span>'
            .'<span class="text-3xl font-thin text-content-tertiary scale-y-150">]</span>'
            .'</span>';
    }
}
