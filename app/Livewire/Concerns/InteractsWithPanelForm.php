<?php

namespace App\Livewire\Concerns;

use App\Actions\Assets\CreatePanel;
use App\Actions\Assets\UpdatePanel;
use App\Enums\PanelType;
use App\Models\Panel;
use App\Models\Server;
use App\Models\Vms;

/**
 * Form tambah/ubah Panel — dipakai bersama oleh halaman index
 * (tambah + ubah) dan halaman detail (ubah di tempat).
 */
trait InteractsWithPanelForm
{
    use Notifies;

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $host = '';

    public string $name = '';

    public string $type = '';

    public string $internet_address = '';

    public string $internal_address = '';

    public string $version = '';

    public string $username = '';

    public string $password = '';

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $panel = Panel::findOrFail($id);

        $this->editingId = $panel->id;
        $this->host = $panel->vm_id !== null
            ? 'vm:'.$panel->vm_id
            : 'srv:'.$panel->server_id;
        $this->name = (string) $panel->name;
        $this->type = $panel->type->value;
        $this->internet_address = (string) $panel->internet_address;
        $this->internal_address = (string) $panel->internal_address;
        $this->version = (string) $panel->version;
        $this->username = (string) $panel->username;
        $this->password = '';

        $this->showModal = true;
    }

    public function save(): void
    {
        $data = [
            'host' => $this->host,
            'name' => $this->name,
            'type' => $this->type,
            'internet_address' => $this->internet_address === '' ? null : $this->internet_address,
            'internal_address' => $this->internal_address === '' ? null : $this->internal_address,
            'version' => $this->version === '' ? null : $this->version,
            'username' => $this->username,
            'password' => $this->password,
        ];

        if ($this->editingId !== null) {
            app(UpdatePanel::class)(Panel::findOrFail($this->editingId), $data);
            $this->notify('Panel '.$this->name.' berhasil diperbarui.');
        } else {
            app(CreatePanel::class)($data);
            $this->notify('Panel '.$this->name.' berhasil ditambahkan.');
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
     * Opsi jenis panel untuk form.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function typeOptions(): array
    {
        return collect(PanelType::cases())
            ->map(fn (PanelType $type) => [
                'value' => $type->value,
                'label' => $type->value,
            ])
            ->all();
    }

    /**
     * Opsi host untuk form: tergrup "Di VM" dan "Di Server". Wajib memilih.
     *
     * @return array<int, array{label: string|null, options: array<int, array{value: string, label: string}>}>
     */
    public function hostOptions(): array
    {
        return [
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
        ];
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->resetValidation();
        $this->host = '';
        $this->name = '';
        $this->type = '';
        $this->internet_address = '';
        $this->internal_address = '';
        $this->version = '';
        $this->username = '';
        $this->password = '';
    }
}
