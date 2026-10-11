<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

new class extends Component
{
    use WithFileUploads;

    #[Layout('layouts::admin')]
    #[Title('Settings')]

    #[Validate('required', message: 'Name wajib diisi.')]
    #[Validate('max:255', message: 'Name maksimal 255 karakter.')]
    public string $name = '';

    #[Validate('required', message: 'Email wajib diisi.')]
    #[Validate('email', message: 'Email harus format email yang valid.')]
    public string $email = '';

    #[Validate('nullable', message: 'Password minimal 8 karakter.')]
    #[Validate('min:8', message: 'Password minimal 8 karakter.')]
    #[Validate('same:passwordConfirmation', message: 'Konfirmasi password tidak cocok.')]
    public string $password = '';

    public string $passwordConfirmation = '';

    public string $current_password = '';

    #[Validate('nullable', message: 'Avatar harus berupa gambar (jpg, png, svg, webp).')]
    #[Validate('image', message: 'Avatar harus berupa gambar (jpg, png, svg, webp).')]
    #[Validate('max:2048', message: 'Avatar maksimal 2 MB.')]
    public $avatar = null; // @var TemporaryUploadedFile|null

    public string $avatar_path = '';

    public bool $saved = false;

    public function mount(): void
    {
        $this->fillFromUser(auth()->user());
    }

    public function updated($property): void
    {
        $this->saved = false;
    }

    public function removeAvatar(): void
    {
        $this->avatar = null;
        $this->avatar_path = '';
        $this->saved = false;
    }

    public function save(): void
    {
        $this->validate();

        /** @var User $user */
        $user = auth()->user();

        $emailTaken = User::query()
            ->where('email', $this->email)
            ->where('id', '!=', $user->id)
            ->exists();

        if ($emailTaken) {
            $this->addError('email', 'Email sudah dipakai.');

            return;
        }

        if ($this->password !== '') {
            if ($this->current_password === '') {
                $this->addError('current_password', 'Password saat ini wajib diisi bila mengganti password.');

                return;
            }

            if (! Hash::check($this->current_password, $user->password)) {
                $this->addError('current_password', 'Password saat ini tidak sesuai.');

                return;
            }
        }

        $data = [
            'name' => $this->name,
            'email' => $this->email,
        ];

        if ($this->password !== '') {
            $data['password'] = $this->password; // cast 'hashed' langsung menghash
        }

        if ($this->avatar instanceof TemporaryUploadedFile) {
            $data['avatar'] = $this->avatar->store('avatars', 'public');
        } else {
            $data['avatar'] = $this->avatar_path === '' ? null : $this->avatar_path;
        }

        // Bersihkan file avatar lama bila diganti/dihapus (avatar_path bisa dikosongkan si hapus)
        if (! blank($user->avatar) && $user->avatar !== $data['avatar']) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->update($data);

        // Sinkron ulang properti tampilan
        $this->fillFromUser($user->refresh());
        $this->saved = true;
    }

    private function fillFromUser(User $user): void
    {
        $this->name = (string) $user->name;
        $this->email = (string) $user->email;
        $this->current_password = '';
        $this->password = '';
        $this->passwordConfirmation = '';
        $this->avatar = null;
        $this->avatar_path = (string) $user->avatar;
    }
};
?>

<div>
    {{-- Header --}}
    <div>
        <h1 class="text-xl font-semibold text-content-primary">Settings</h1>
        <p class="mt-1 text-sm text-content-secondary">Ubah data akun yang sedang login.</p>
    </div>

    {{-- Banner sukses --}}
    @if ($saved)
        <div
            class="mt-4 flex items-center gap-2 rounded-lg border border-guide-300 bg-guide-50 px-4 py-3 text-sm text-guide-600"
            x-data="{ show: true }" x-show="show"
        >
            <i class="ti ti-circle-check text-lg" aria-hidden="true"></i>
            Perubahan berhasil disimpan.
            <button
                type="button"
                class="ml-auto text-guide-500 hover:text-guide-700"
                x-on:click="show = false"
                aria-label="Tutup notifikasi"
            >
                <i class="ti ti-x text-base"></i>
            </button>
        </div>
    @endif

    <form class="flex flex-col gap-4 mt-4" wire:submit="save">
        {{-- Card: Avatar --}}
        <div class="ina-card ina-card--stack p-6">
            <div class="flex flex-col">
                <h2 class="text-base font-semibold text-content-primary">Avatar</h2>
                <p class="mt-0.5 text-sm text-content-secondary">Tampilan foto akun Anda di sidebar dan menu aplikasi.</p>
            </div>

            <div class="pt-5">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-full border border-stroke-primary bg-guide-50 text-lg font-medium text-guide-600">
                        @if ($this->avatar instanceof TemporaryUploadedFile)
                            <img src="{{ $this->avatar->temporaryUrl() }}" alt="Avatar baru" class="h-16 w-16 shrink-0 object-cover">
                        @elseif ($avatar_path !== '')
                            <img src="{{ asset('storage/'.$avatar_path) }}" alt="Avatar {{ $name }}" class="h-16 w-16 shrink-0 object-cover">
                        @else
                            {{ collect(str_split($name !== '' ? $name : '?', 1))->take(2)->map(fn ($c) => strtoupper($c))->implode('') }}
                        @endif
                    </div>
                    <div class="min-w-0">
                        <label
                            for="settings-avatar"
                            class="ina-button ina-button--secondary ina-button--sm cursor-pointer"
                        >
                            <i class="ti ti-upload text-base" aria-hidden="true"></i>
                            Unggah Avatar
                        </label>
                        <input
                            id="settings-avatar"
                            type="file"
                            accept="image/*"
                            wire:model="avatar"
                            class="sr-only"
                        >
                        <button
                            type="button"
                            wire:click="removeAvatar"
                            class="ms-2 text-xs font-medium text-negative-600 hover:underline"
                        >
                            Hapus avatar
                        </button>
                        <p class="mt-1.5 text-xs text-content-tertiary">JPG, PNG, SVG, WEBP — maksimal 2 MB.</p>
                        @error('avatar')
                            <p class="mt-1 text-xs text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Card: Profil --}}
        <div class="ina-card ina-card--stack p-6">
            <div class="flex flex-col">
                <h2 class="text-base font-semibold text-content-primary">Profil</h2>
                <p class="mt-0.5 text-sm text-content-secondary">Informasi dasar akun yang muncul di seluruh aplikasi.</p>
            </div>

            <div class="pt-5">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="ina-text-field">
                        <label for="settings-name" class="ina-text-field__label">
                            Name
                            <span class="ina-text-field__required">*</span>
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('name') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="text"
                                id="settings-name"
                                wire:model="name"
                                class="ina-text-field__input"
                                required
                            >
                        </div>
                        @error('name')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="ina-text-field">
                        <label for="settings-email" class="ina-text-field__label">
                            Email
                            <span class="ina-text-field__required">*</span>
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('email') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="email"
                                id="settings-email"
                                wire:model="email"
                                class="ina-text-field__input"
                                required
                            >
                        </div>
                        @error('email')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Card: Password --}}
        <div class="ina-card ina-card--stack p-6">
            <div class="flex flex-col">
                <h2 class="text-base font-semibold text-content-primary">Password</h2>
                <p class="mt-0.5 text-sm text-content-secondary">Ganti password akun. Password saat ini diperlukan untuk verifikasi.</p>
            </div>

            <div class="pt-5">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="ina-text-field">
                        <label for="settings-password" class="ina-text-field__label">
                            Password Baru
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('password') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="password"
                                id="settings-password"
                                wire:model="password"
                                class="ina-text-field__input"
                                autocomplete="new-password"
                            >
                        </div>
                        @error('password')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="ina-text-field">
                        <label for="settings-password-confirmation" class="ina-text-field__label">
                            Ulangi Password Baru
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('passwordConfirmation') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="password"
                                id="settings-password-confirmation"
                                wire:model="passwordConfirmation"
                                class="ina-text-field__input"
                                autocomplete="new-password"
                            >
                        </div>
                    </div>
                </div>

                <div class="ina-text-field mt-4">
                    <label for="settings-current-password" class="ina-text-field__label">
                        Password Saat Ini
                        @if ($password !== '')
                            <span class="ina-text-field__required">*</span>
                        @endif
                    </label>
                    <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('current_password') ? 'ina-text-field__wrapper--status-error' : '' }}">
                        <input
                            type="password"
                            id="settings-current-password"
                            wire:model="current_password"
                            class="ina-text-field__input"
                            autocomplete="current-password"
                        >
                    </div>
                    <p class="mt-1.5 text-xs text-content-tertiary">Diperlukan hanya bila mengganti password.</p>
                    @error('current_password')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Aksi --}}
        <div class="flex justify-end">
            <button type="submit" class="ina-button ina-button--primary ina-button--md" wire:loading.attr="disabled">
                Simpan Perubahan
            </button>
        </div>
    </form>
</div>
