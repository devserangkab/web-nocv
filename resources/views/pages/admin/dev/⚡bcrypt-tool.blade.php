<?php

use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Layout('layouts::admin')]
    #[Title('Bcrypt Hash')]

    #[Validate('required', message: 'Plain text wajib diisi.')]
    public string $plain = '';

    #[Validate('required|integer|min:4|max:19', message: 'Cost harus angka antara 4 dan 19.')]
    public ?int $cost = 12;

    public string $hash = '';

    public ?float $generateDurationMs = null;

    public bool $generated = false;

    #[Validate('required', message: 'Hash wajib diisi.')]
    public string $verifyHash = '';

    #[Validate('required', message: 'Plain text wajib diisi.')]
    public string $verifyPlain = '';

    public ?bool $verifyMatch = null;

    /** Opsi dropdown cost factor bcrypt (4–19). */
    public function costOptions(): array
    {
        return collect(range(4, 19))
            ->map(fn ($n) => ['value' => (string) $n, 'label' => (string) $n])
            ->all();
    }

    /**
     * Hasilkan hash bcrypt dari plain text dengan cost tertentu.
     * Nilai plain tidak pernah dicatat ke log.
     */
    public function generate(): void
    {
        $this->validateOnly('plain');
        $this->validateOnly('cost');

        $start = hrtime(true);

        $this->hash = Hash::make($this->plain, ['cost' => $this->cost]);

        $this->generateDurationMs = round((hrtime(true) - $start) / 1e6, 2);
        $this->generated = true;
    }

    /**
     * Cocokkan hash dengan plain text via Hash::check —
     * jangan menggenerate ulang, agar kompatibel dengan cost/aplikasi mana pun.
     */
    public function verify(): void
    {
        $this->verifyMatch = null;

        $this->validateOnly('verifyHash');
        $this->validateOnly('verifyPlain');

        $hash = trim($this->verifyHash);

        if (preg_match('/^(\$2[aby]\$)(\d{2})\$/', $hash) !== 1) {
            $this->addError('verifyHash', "Format hash bcrypt tidak valid (mulai dengan \$2a\$, \$2b\$, atau \$2y\$ serta 60 karakter).");

            return;
        }

        $this->verifyMatch = Hash::check($this->verifyPlain, $hash);
    }
};
?>

<div>
    {{-- Header --}}
    <div>
        <h1 class="text-xl font-semibold text-content-primary">Bcrypt Hash</h1>
        <p class="mt-1 text-sm text-content-secondary">Buat dan verifikasi hash bcrypt untuk kebutuhan akun &amp; token teknis.</p>
    </div>

    <div class="mt-4 flex flex-col gap-4">
        {{-- Card: Generator — overflow-visible agar panel dropdown cost tidak terpotong batas card --}}
        <div class="ina-card ina-card--stack p-6 overflow-visible!">
            <div class="flex flex-col">
                <h2 class="text-base font-semibold text-content-primary">Generator</h2>
                <p class="mt-0.5 text-sm text-content-secondary">Masukkan plain text dan cost factor, lalu hasilkan hash.</p>
            </div>

            <form class="mt-5 flex flex-col gap-4" wire:submit="generate">
                <div class="ina-text-field">
                    <label for="bcrypt-plain" class="ina-text-field__label">
                        Plain text
                        <span class="ina-text-field__required">*</span>
                    </label>
                    <div class="ina-text-field__wrapper ina-text-field__wrapper--size-lg">
                        <input
                            type="text"
                            id="bcrypt-plain"
                            wire:model="plain"
                            class="ina-text-field__input"
                            autocomplete="off"
                            placeholder="masukkan teks yang akan di-hash"
                        >
                    </div>
                    @error('plain')
                        <p class="mt-1.5 text-sm text-content-negative">{{ $message }}</p>
                    @enderror
                </div>

                <div class="ina-text-field">
                    <label for="bcrypt-cost" class="ina-text-field__label">
                        Cost factor
                        <span class="ina-text-field__required">*</span>
                    </label>
                    <div class="mt-1.5 w-32">
                        <x-idds.select
                            wire-prop="cost"
                            :value="(string) $cost"
                            :options="$this->costOptions()"
                            placeholder="Cost"
                            aria-label="Cost factor bcrypt"
                        />
                    </div>
                    @error('cost')
                        <p class="mt-1.5 text-sm text-content-negative">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex">
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="generate"
                        class="ina-button ina-button--primary ina-button--md"
                    >
                        <i class="ti ti-hash text-base" wire:loading.remove wire:target="generate"></i>
                        <i class="ti ti-loader animate-spin text-base" wire:loading wire:target="generate"></i>
                        Generate hash
                    </button>
                </div>
            </form>

            @if ($generated)
                <div class="mt-2 flex flex-col gap-3">
                    <div
                        class="rounded-md border border-stroke-primary bg-background-secondary px-4 py-3 font-mono text-sm leading-relaxed break-all select-all text-content-primary"
                        x-ref="generatedHash"
                    >{{ $hash }}</div>

                    <div>
                        <button
                            type="button"
                            class="ina-button ina-button--secondary ina-button--sm"
                            x-data="{ hash: @js($hash) }"
                            x-on:click="navigator.clipboard.writeText(hash)"
                            :aria-label="'Salin hash ke clipboard'"
                        >
                            <i class="ti ti-copy text-base"></i>
                            Salin hash
                        </button>
                    </div>
                </div>
            @endif
        </div>

        {{-- Card: Verifier --}}
        <div class="ina-card ina-card--stack p-6">
            <div class="flex flex-col">
                <h2 class="text-base font-semibold text-content-primary">Verifier</h2>
                <p class="mt-0.5 text-sm text-content-secondary">Cocokkan hash dengan plain textnya menggunakan Hash::check.</p>
            </div>

            <form class="mt-5 flex flex-col gap-4" wire:submit="verify">
                <div class="ina-text-field">
                    <label for="bcrypt-verify-plain" class="ina-text-field__label">
                        Plain text
                        <span class="ina-text-field__required">*</span>
                    </label>
                    <div class="ina-text-field__wrapper ina-text-field__wrapper--size-lg">
                        <input
                            type="text"
                            id="bcrypt-verify-plain"
                            wire:model="verifyPlain"
                            class="ina-text-field__input"
                            autocomplete="off"
                            placeholder="teks pembanding"
                        >
                    </div>
                    @error('verifyPlain')
                        <p class="mt-1.5 text-sm text-content-negative">{{ $message }}</p>
                    @enderror
                </div>

                <div class="ina-text-area">
                    <label for="bcrypt-verify-hash" class="ina-text-area__label">
                        Hash bcrypt
                        <span class="ina-text-field__required">*</span>
                    </label>
                    <div class="ina-text-area__wrapper ina-text-area__wrapper--size-sm">
                        <textarea
                            id="bcrypt-verify-hash"
                            wire:model="verifyHash"
                            rows="3"
                            class="ina-text-area__input font-mono text-sm"
                            autocomplete="off"
                            placeholder="$2y$12$..."
                            spellcheck="false"
                        ></textarea>
                    </div>
                    @error('verifyHash')
                        <p class="mt-1.5 text-sm text-content-negative">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex">
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="verify"
                        class="ina-button ina-button--primary ina-button--md"
                    >
                        <i class="ti ti-circle-check text-base" wire:loading.remove wire:target="verify"></i>
                        <i class="ti ti-loader animate-spin text-base" wire:loading wire:target="verify"></i>
                        Verify
                    </button>
                </div>
            </form>

            @if ($verifyMatch !== null)
                <div class="mt-2 flex flex-wrap items-center gap-3">
                    @if ($verifyMatch)
                        <span class="ina-badge ina-badge--soft ina-badge--success ina-badge--md">
                            <i class="ti ti-check mr-1" aria-hidden="true"></i>
                            Match — hash cocok dengan plain text
                        </span>
                    @else
                        <span class="ina-badge ina-badge--soft ina-badge--error ina-badge--md">
                            <i class="ti ti-x mr-1" aria-hidden="true"></i>
                            Tidak cocok
                        </span>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
