<?php

namespace App\Livewire\Concerns;

use App\Actions\Assets\CreateWaf;
use App\Actions\Assets\UpdateWaf;
use App\Enums\WafTech;
use App\Models\Server;
use App\Models\Vms;
use App\Models\Waf;

/**
 * Form tambah/ubah WAF — dipakai bersama oleh halaman index
 * (tambah + ubah) dan halaman detail (ubah di tempat).
 */
trait InteractsWithWafForm
{
    use Notifies;

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $host = '';

    public string $name = '';

    public string $tech = '';

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
        $waf = Waf::findOrFail($id);

        $this->editingId = $waf->id;
        $this->host = $waf->vm_id !== null
            ? 'vm:'.$waf->vm_id
            : 'srv:'.$waf->server_id;
        $this->name = (string) $waf->name;
        $this->tech = $waf->tech->value;
        $this->internet_address = (string) $waf->internet_address;
        $this->internal_address = (string) $waf->internal_address;
        $this->version = (string) $waf->version;
        $this->username = (string) $waf->username;
        $this->password = '';

        $this->showModal = true;
    }

    public function save(): void
    {
        $data = [
            'host' => $this->host,
            'name' => $this->name,
            'tech' => $this->tech,
            'internet_address' => $this->internet_address === '' ? null : $this->internet_address,
            'internal_address' => $this->internal_address === '' ? null : $this->internal_address,
            'version' => $this->version === '' ? null : $this->version,
            'username' => $this->username,
            'password' => $this->password,
        ];

        if ($this->editingId !== null) {
            app(UpdateWaf::class)(Waf::findOrFail($this->editingId), $data);
            $this->notify('WAF '.$this->name.' berhasil diperbarui.');
        } else {
            app(CreateWaf::class)($data);
            $this->notify('WAF '.$this->name.' berhasil ditambahkan.');
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
     * Opsi teknologi WAF untuk form.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function techOptions(): array
    {
        return collect(WafTech::cases())
            ->map(fn (WafTech $tech) => [
                'value' => $tech->value,
                'label' => $tech->value,
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
        $this->tech = '';
        $this->internet_address = '';
        $this->internal_address = '';
        $this->version = '';
        $this->username = '';
        $this->password = '';
    }
}
