<?php

namespace App\Livewire\Concerns;

use App\Actions\Assets\CreateProxmox;
use App\Actions\Assets\UpdateProxmox;
use App\Models\Proxmox;
use App\Models\Server;

/**
 * Form tambah/ubah Proxmox — dipakai bersama oleh tab index
 * (tambah + ubah) dan halaman detail (ubah di tempat).
 */
trait InteractsWithProxmoxForm
{
    public bool $showProxmoxModal = false;

    public ?int $editingProxmoxId = null;

    public string $proxmox_name = '';

    public string $proxmox_url = '';

    public string $proxmox_version = '';

    public string $proxmox_username = '';

    public string $proxmox_password = '';

    public string $assign_server_id = '';

    public function openProxmoxCreate(): void
    {
        $this->resetProxmoxForm();
        $this->showProxmoxModal = true;
    }

    public function editProxmox(int $id): void
    {
        $proxmox = Proxmox::findOrFail($id);

        $this->editingProxmoxId = $proxmox->id;
        $this->proxmox_name = (string) $proxmox->name;
        $this->proxmox_url = (string) $proxmox->url;
        $this->proxmox_version = (string) $proxmox->version;
        $this->proxmox_username = (string) $proxmox->username;
        $this->proxmox_password = '';
        $this->assign_server_id = (string) $proxmox->server_id;

        $this->showProxmoxModal = true;
    }

    public function saveProxmox(): void
    {
        $data = [
            'name' => $this->proxmox_name,
            'url' => $this->proxmox_url === '' ? null : $this->proxmox_url,
            'version' => $this->proxmox_version === '' ? null : $this->proxmox_version,
            'assign_server_id' => $this->assign_server_id,
            'username' => $this->proxmox_username,
            'password' => $this->proxmox_password,
        ];

        if ($this->editingProxmoxId !== null) {
            app(UpdateProxmox::class)(Proxmox::findOrFail($this->editingProxmoxId), $data);
        } else {
            app(CreateProxmox::class)($data);
        }

        $this->showProxmoxModal = false;
        $this->resetProxmoxForm();
    }

    public function closeProxmoxModal(): void
    {
        $this->showProxmoxModal = false;
        $this->resetProxmoxForm();
    }

    /**
     * Opsi server untuk form: server yang sudah dipakai Proxmox lain di-disable.
     */
    public function serverOptions(): array
    {
        return Server::query()
            ->with('proxmox')
            ->orderBy('server_id')
            ->get()
            ->map(fn (Server $server) => [
                'value' => $server->server_id,
                'label' => "{$server->server_name} ({$server->server_id})",
                'taken' => $server->proxmox !== null && $server->proxmox->id !== $this->editingProxmoxId,
            ])
            ->all();
    }

    public function resetProxmoxForm(): void
    {
        $this->editingProxmoxId = null;
        $this->resetValidation();
        $this->proxmox_name = '';
        $this->proxmox_url = '';
        $this->proxmox_version = '';
        $this->proxmox_username = '';
        $this->proxmox_password = '';
        $this->assign_server_id = '';
    }
}
