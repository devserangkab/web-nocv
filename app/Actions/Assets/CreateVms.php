<?php

namespace App\Actions\Assets;

use App\Actions\Activities\RecordActivity;
use App\Enums\VmsStatus;
use App\Models\Vms;
use App\Support\Authorize;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreateVms
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(array $data): Vms
    {
        Authorize::infrastructureManage();
        $data = Validator::make($data, static::rules(), static::messages())->validate();

        $vms = Vms::create($this->normalize($data));

        app(RecordActivity::class)->audit($vms, 'Menambahkan vm '.$vms->name.' ('.$vms->vm_id.')');

        return $vms;
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'proxmox_id' => ['required', 'exists:proxmoxs,proxmox_id'],
            'proxmox_number' => ['nullable', 'integer', 'min:1'],
            'name' => ['required', 'max:255'],
            'local_ip' => ['nullable', 'ip'],
            'public_ip' => ['nullable', 'ip'],
            'cpu' => ['nullable', 'integer', 'min:1'],
            'ram' => ['nullable', 'integer', 'min:0'],
            'storage' => ['nullable', 'integer', 'min:0'],
            'os' => ['nullable', 'max:255'],
            'status' => ['required', Rule::enum(VmsStatus::class)],
            'note' => ['nullable', 'max:1000'],
            'username' => ['nullable', 'max:255'],
            'password' => ['nullable', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'proxmox_id.required' => 'Host Proxmox wajib dipilih.',
            'proxmox_id.exists' => 'Host Proxmox tidak ditemukan.',
            'proxmox_number.min' => 'VMID harus berupa angka (minimal 1).',
            'name.required' => 'Nama VM wajib diisi.',
            'name.max' => 'Nama VM maksimal 255 karakter.',
            'local_ip.ip' => 'Local IP harus berupa alamat IP yang valid.',
            'public_ip.ip' => 'Public IP harus berupa alamat IP yang valid.',
            'cpu.min' => 'CPU harus berupa jumlah core (minimal 1).',
            'ram.min' => 'RAM harus berupa angka megabyte (MB).',
            'storage.min' => 'Storage harus berupa angka megabyte (MB).',
            'os.max' => 'OS maksimal 255 karakter.',
            'status.in' => 'Status harus ON, OFF, atau DESTROYED.',
            'note.max' => 'Note maksimal 1000 karakter.',
            'username.max' => 'Username maksimal 255 karakter.',
            'password.max' => 'Password maksimal 255 karakter.',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalize(array $data): array
    {
        return [
            'proxmox_id' => $data['proxmox_id'],
            'proxmox_number' => filled($data['proxmox_number'] ?? null) ? (string) (int) $data['proxmox_number'] : null,
            'name' => $data['name'],
            'local_ip' => filled($data['local_ip'] ?? null) ? $data['local_ip'] : null,
            'public_ip' => filled($data['public_ip'] ?? null) ? $data['public_ip'] : null,
            'cpu' => filled($data['cpu'] ?? null) ? (int) $data['cpu'] : null,
            'ram' => filled($data['ram'] ?? null) ? (int) $data['ram'] : null,
            'storage' => filled($data['storage'] ?? null) ? (int) $data['storage'] : null,
            'os' => filled($data['os'] ?? null) ? $data['os'] : null,
            'status' => VmsStatus::from($data['status']),
            'note' => filled($data['note'] ?? null) ? $data['note'] : null,
            'username' => filled($data['username'] ?? null) ? $data['username'] : null,
            'password' => filled($data['password'] ?? null) ? $data['password'] : null,
        ];
    }
}
