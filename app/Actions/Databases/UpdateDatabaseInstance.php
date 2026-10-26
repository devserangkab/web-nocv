<?php

namespace App\Actions\Databases;

use App\Actions\Activities\RecordActivity;
use App\Models\DatabaseInstance;
use App\Support\Authorize;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UpdateDatabaseInstance
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(DatabaseInstance $instance, array $data): DatabaseInstance
    {
        Authorize::infrastructureManage();
        $data = Validator::make($data, CreateDatabaseInstance::rules(), CreateDatabaseInstance::messages())->validate();

        $normalized = CreateDatabaseInstance::normalize($data);

        // Kredensial: kosong saat edit = biarkan nilai lama
        foreach (['username', 'password'] as $credential) {
            if (blank($normalized[$credential])) {
                unset($normalized[$credential]);
            }
        }

        // Nilai teknologi sebelum perubahan — untuk aktivitas riwayat versi
        $before = [
            'tech' => $instance->tech->value,
            'version' => $instance->version,
        ];

        DB::transaction(function () use ($instance, $normalized, $before): void {
            $instance->update($normalized);

            $after = [
                'tech' => $instance->tech->value,
                'version' => $instance->version,
            ];

            if ($after !== $before) {
                $beforeText = trim($before['tech'].' '.($before['version'] ?? ''));
                $afterText = trim($after['tech'].' '.($after['version'] ?? ''));

                app(RecordActivity::class)->audit(
                    $instance,
                    'Pembaruan versi database: '.$beforeText.' → '.$afterText.' pada '.$instance->name.' ('.$instance->dbi_id.')'
                );
            }

            app(RecordActivity::class)->audit(
                $instance,
                'Mengubah database server '.$instance->name.' ('.$instance->dbi_id.')'
            );
        });

        return $instance;
    }
}
