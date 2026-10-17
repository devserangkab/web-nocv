<?php

namespace App\Livewire\Concerns;

use App\Actions\Assets\CreateServer;
use App\Actions\Assets\UpdateServer;
use App\Enums\ServerStatus;
use App\Models\Location;
use App\Models\Rack;
use App\Models\Server;
use Illuminate\Support\Collection;

/**
 * Form tambah/ubah Server — dipakai bersama oleh halaman index
 * (tambah + ubah) dan halaman detail (ubah di tempat).
 */
trait InteractsWithServerForm
{
    use Notifies;

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $server_name = '';

    public string $rack_id = '';

    public string $position = '';

    public string $brand = '';

    public string $storage_size = '';

    public string $storage = '';

    public string $memory_size = '';

    public string $memory = '';

    public string $status = ServerStatus::Off->value;

    public string $cpu = '';

    public string $static_ip = '';

    public string $location_id = '';

    public string $os = '';

    public string $username = '';

    public string $password = '';

    public function updatedRackId(string $value): void
    {
        if ($value === '') {
            $this->location_id = '';

            return;
        }

        $this->location_id = (string) (Rack::with('location')->where('rack_id', $value)->first()?->location?->location_id ?? '');
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $server = Server::findOrFail($id);

        $this->editingId = $server->id;
        $this->server_name = $server->server_name;
        $this->rack_id = (string) $server->rack_id;
        $this->location_id = (string) $server->location_id;
        $this->position = (string) $server->position;
        $this->brand = (string) $server->brand;
        $this->storage_size = (string) $server->storage_size;
        $this->storage = (string) $server->storage;
        $this->memory_size = (string) $server->memory_size;
        $this->memory = (string) $server->memory;
        $this->status = $server->status?->value ?? ServerStatus::Off->value;
        $this->cpu = (string) $server->cpu;
        $this->static_ip = (string) $server->static_ip;
        $this->os = (string) $server->os;
        $this->username = (string) $server->username;
        $this->password = '';

        $this->showModal = true;
    }

    public function save(): void
    {
        // Semua rule & pesan validasi dimiliki oleh action (satu sumber kebenaran).
        $data = [
            'server_name' => $this->server_name,
            'rack_id' => $this->rack_id === '' ? null : $this->rack_id,
            'location_id' => $this->location_id === '' ? null : $this->location_id,
            'position' => $this->position === '' ? null : (int) $this->position,
            'brand' => $this->brand === '' ? null : $this->brand,
            'storage_size' => $this->storage_size === '' ? null : (int) $this->storage_size,
            'storage' => $this->storage === '' ? null : $this->storage,
            'memory_size' => $this->memory_size === '' ? null : (int) $this->memory_size,
            'memory' => $this->memory === '' ? null : $this->memory,
            'status' => $this->status,
            'cpu' => $this->cpu === '' ? null : $this->cpu,
            'static_ip' => $this->static_ip === '' ? null : $this->static_ip,
            'os' => $this->os === '' ? null : $this->os,
            'username' => $this->username,
            'password' => $this->password,
        ];

        if ($this->editingId !== null) {
            app(UpdateServer::class)(Server::findOrFail($this->editingId), $data);
            $this->notify('Server '.$this->server_name.' berhasil diperbarui.');
        } else {
            app(CreateServer::class)($data);
            $this->notify('Server '.$this->server_name.' berhasil ditambahkan.');
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
     * Location options untuk form & filter.
     *
     * @return Collection<int, Location>
     */
    public function locationOptions(): Collection
    {
        return Location::query()->orderBy('location_id')->get();
    }

    /**
     * Daftar rack untuk pilihan form, bergrup per lokasi.
     *
     * @return array<string, array<int, array{value: string, label: string}>>
     */
    public function rackOptions(): array
    {
        return Rack::query()
            ->with('location')
            ->orderBy('rack_id')
            ->get()
            ->groupBy(fn (Rack $rack) => $rack->location?->name ?? 'Tanpa Lokasi')
            ->map(fn ($racks) => $racks->map(fn (Rack $rack) => [
                'value' => $rack->rack_id,
                'label' => "{$rack->name} ({$rack->rack_id})",
            ])->all())
            ->all();
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->resetValidation();
        $this->server_name = '';
        $this->rack_id = '';
        $this->location_id = '';
        $this->position = '';
        $this->brand = '';
        $this->storage_size = '';
        $this->storage = '';
        $this->memory_size = '';
        $this->memory = '';
        $this->status = ServerStatus::Off->value;
        $this->cpu = '';
        $this->static_ip = '';
        $this->os = '';
        $this->username = '';
        $this->password = '';
    }
}
