<?php

namespace App\Livewire\Concerns;

use App\Actions\Databases\CreateDatabaseUser;
use App\Actions\Databases\DeleteDatabaseUser;
use App\Actions\Databases\UpdateDatabaseUser;
use App\Models\DatabaseInstance;
use App\Models\DatabaseUser;

/**
 * Form kelola user database (katalog kredensial) di dalam detail instance.
 */
trait InteractsWithDatabaseUserForm
{
    use Notifies;

    public bool $showUserModal = false;

    public bool $showUserDeleteModal = false;

    public ?int $editingUserId = null;

    public ?int $deletingUserId = null;

    public string $user_username = '';

    public string $user_password = '';

    public string $user_note = '';

    public function openUserCreate(): void
    {
        $this->resetUserForm();
        $this->showUserModal = true;
    }

    public function editUser(int $id): void
    {
        $user = DatabaseUser::findOrFail($id);

        $this->editingUserId = $user->id;
        $this->user_username = (string) $user->username;
        $this->user_password = '';
        $this->user_note = (string) $user->note;

        $this->showUserModal = true;
    }

    public function saveUser(): void
    {
        /** @var DatabaseInstance|null $instance */
        $instance = $this->instance ?? null;

        if ($instance === null) {
            return;
        }

        $data = [
            'username' => $this->user_username,
            'password' => $this->user_password,
            'note' => $this->user_note === '' ? null : $this->user_note,
        ];

        if ($this->editingUserId !== null) {
            app(UpdateDatabaseUser::class)(DatabaseUser::findOrFail($this->editingUserId), $data);
            $this->notify('User database '.$this->user_username.' berhasil diperbarui.');
        } else {
            app(CreateDatabaseUser::class)($instance, $data);
            $this->notify('User database '.$this->user_username.' berhasil ditambahkan.');
        }

        $this->showUserModal = false;
        $this->resetUserForm();
    }

    public function askUserDelete(int $id): void
    {
        $this->deletingUserId = $id;
        $this->showUserDeleteModal = true;
    }

    public function deleteUser(): void
    {
        if ($this->deletingUserId !== null) {
            app(DeleteDatabaseUser::class)(DatabaseUser::findOrFail($this->deletingUserId));
        }

        $this->notify('User database berhasil dihapus.');
        $this->showUserDeleteModal = false;
        $this->deletingUserId = null;
    }

    public function closeUserModal(): void
    {
        $this->showUserModal = false;
        $this->resetUserForm();
    }

    public function deletingUser(): ?DatabaseUser
    {
        return $this->deletingUserId !== null ? DatabaseUser::find($this->deletingUserId) : null;
    }

    public function resetUserForm(): void
    {
        $this->editingUserId = null;
        $this->resetValidation();
        $this->user_username = '';
        $this->user_password = '';
        $this->user_note = '';
    }
}
