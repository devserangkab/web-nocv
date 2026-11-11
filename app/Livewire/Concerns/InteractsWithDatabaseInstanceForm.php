<?php

namespace App\Livewire\Concerns;

use App\Actions\Databases\CreateDatabaseInstance;
use App\Actions\Databases\UpdateDatabaseInstance;
use App\Enums\DatabaseTech;
use App\Models\DatabaseInstance;
use App\Models\Panel;
use App\Models\Server;
use App\Models\Vms;

/**
 * Form tambah/ubah Database Instance — dipakai bersama oleh halaman index
 * (tambah + ubah) dan halaman detail (ubah di tempat).
 */
trait InteractsWithDatabaseInstanceForm
{
    use Notifies;

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $host = '';

    public string $name = '';

    public string $tech = '';

    public string $version = '';

    public string $internal_address = '';

    public string $username = '';

    public string $password = '';

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $instance = DatabaseInstance::findOrFail($id);

        $this->editingId = $instance->id;
        $this->host = $instance->vm_id !== null
            ? 'vm:'.$instance->vm_id
            : ($instance->server_id !== null ? 'srv:'.$instance->server_id : 'pnl:'.$instance->panel_id);
        $this->name = (string) $instance->name;
        $this->tech = $instance->tech->value;
        $this->version = (string) $instance->version;
        $this->internal_address = (string) $instance->internal_address;
        $this->username = (string) $instance->username;
        $this->password = '';

        $this->showModal = true;
    }

    public function save(): void
    {
        $data = [
            'host' => $this->host,
            'name' => $this->name,
            'tech' => $this->tech,
            'version' => $this->version === '' ? null : $this->version,
            'internal_address' => $this->internal_address === '' ? null : $this->internal_address,
            'username' => $this->username,
            'password' => $this->password,
        ];

        if ($this->editingId !== null) {
            app(UpdateDatabaseInstance::class)(DatabaseInstance::findOrFail($this->editingId), $data);
            // Refresh model yang ditampilkan halaman detail agar render langsung berubah.
            if (isset($this->instance)) {
                $this->instance->refresh();
            }
            $this->notify('Database server '.$this->name.' berhasil diperbarui.');
        } else {
            app(CreateDatabaseInstance::class)($data);
            $this->notify('Database server '.$this->name.' berhasil ditambahkan.');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    /**
     * Opsi teknologi database untuk form.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function techOptions(): array
    {
        return collect(DatabaseTech::cases())
            ->map(fn (DatabaseTech $tech) => [
                'value' => $tech->value,
                'label' => $tech->value,
            ])
            ->all();
    }

    /**
     * Opsi host untuk form: tergrup "Di Server", "Di VM", dan "Di Panel". Wajib memilih.
     *
     * @return array<int, array{label: string|null, options: array<int, array{value: string, label: string}>}>
     */
    public function hostOptions(): array
    {
        return [
            [
                'label' => 'Di Server',
                'options' => Server::query()
                    ->orderBy('server_id')
                    ->get()
                    ->map(fn (Server $server) => [
                        'value' => 'srv:'.$server->server_id,
                        'label' => "{$server->server_name} ({$server->server_id})",
                    ])
                    ->all(),
            ],
            [
                'label' => 'Di VM',
                'options' => Vms::query()
                    ->orderBy('vm_id')
                    ->get()
                    ->map(fn (Vms $vm) => [
                        'value' => 'vm:'.$vm->vm_id,
                        'label' => "{$vm->name} ({$vm->vm_id})",
                    ])
                    ->all(),
            ],
            [
                'label' => 'Di Panel',
                'options' => Panel::query()
                    ->orderBy('panel_id')
                    ->get()
                    ->map(fn (Panel $panel) => [
                        'value' => 'pnl:'.$panel->panel_id,
                        'label' => "{$panel->name} ({$panel->panel_id})",
                    ])
                    ->all(),
            ],
        ];
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->resetValidation();
        $this->host = '';
        $this->name = '';
        $this->tech = '';
        $this->version = '';
        $this->internal_address = '';
        $this->username = '';
        $this->password = '';
    }
}
