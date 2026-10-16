<?php

namespace App\Livewire\Concerns;

use App\Actions\Assets\CreateVms;
use App\Actions\Assets\UpdateVms;
use App\Enums\VmsStatus;
use App\Models\Proxmox;
use App\Models\Vms;

/**
 * Form tambah/ubah VM — dipakai bersama oleh tab index
 * (tambah + ubah) dan halaman detail (ubah di tempat).
 */
trait InteractsWithVmsForm
{
    public bool $showVmsModal = false;

    public ?int $editingVmsId = null;

    public string $proxmox_id = '';

    public string $proxmox_number = '';

    public string $vm_name = '';

    public string $local_ip = '';

    public string $public_ip = '';

    public string $cpu = '';

    public string $ram = '';

    public string $storage = '';

    public string $os = '';

    public string $status = VmsStatus::Off->value;

    public string $note = '';

    public string $username = '';

    public string $password = '';

    public function openVmsCreate(): void
    {
        $this->resetVmsForm();
        $this->showVmsModal = true;
    }

    public function editVms(int $id): void
    {
        $vm = Vms::findOrFail($id);

        $this->editingVmsId = $vm->id;
        $this->proxmox_id = (string) $vm->proxmox_id;
        $this->proxmox_number = (string) $vm->proxmox_number;
        $this->vm_name = (string) $vm->name;
        $this->local_ip = (string) $vm->local_ip;
        $this->public_ip = (string) $vm->public_ip;
        $this->cpu = (string) $vm->cpu;
        $this->ram = (string) $vm->ram;
        $this->storage = (string) $vm->storage;
        $this->os = (string) $vm->os;
        $this->status = $vm->status?->value ?? VmsStatus::Off->value;
        $this->note = (string) $vm->note;
        $this->username = (string) $vm->username;
        $this->password = '';

        $this->showVmsModal = true;
    }

    public function saveVms(): void
    {
        $data = [
            'proxmox_id' => $this->proxmox_id,
            'proxmox_number' => $this->proxmox_number === '' ? null : (int) $this->proxmox_number,
            'name' => $this->vm_name,
            'local_ip' => $this->local_ip === '' ? null : $this->local_ip,
            'public_ip' => $this->public_ip === '' ? null : $this->public_ip,
            'cpu' => $this->cpu === '' ? null : (int) $this->cpu,
            'ram' => $this->ram === '' ? null : (int) $this->ram,
            'storage' => $this->storage === '' ? null : (int) $this->storage,
            'os' => $this->os === '' ? null : $this->os,
            'status' => $this->status,
            'note' => $this->note === '' ? null : $this->note,
            'username' => $this->username,
            'password' => $this->password,
        ];

        if ($this->editingVmsId !== null) {
            app(UpdateVms::class)(Vms::findOrFail($this->editingVmsId), $data);
        } else {
            app(CreateVms::class)($data);
        }

        $this->showVmsModal = false;
        $this->resetVmsForm();
    }

    public function closeVmsModal(): void
    {
        $this->showVmsModal = false;
        $this->resetVmsForm();
    }

    /**
     * Opsi host Proxmox untuk form.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function proxmoxOptions(): array
    {
        return Proxmox::query()
            ->orderBy('proxmox_id')
            ->get()
            ->map(fn (Proxmox $proxmox) => [
                'value' => $proxmox->proxmox_id,
                'label' => "{$proxmox->name} ({$proxmox->proxmox_id})",
            ])
            ->all();
    }

    public function resetVmsForm(): void
    {
        $this->editingVmsId = null;
        $this->resetValidation();
        $this->proxmox_id = '';
        $this->proxmox_number = '';
        $this->vm_name = '';
        $this->local_ip = '';
        $this->public_ip = '';
        $this->cpu = '';
        $this->ram = '';
        $this->storage = '';
        $this->os = '';
        $this->status = VmsStatus::Off->value;
        $this->note = '';
        $this->username = '';
        $this->password = '';
    }
}
