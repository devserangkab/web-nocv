<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\DB;
use SirajCSE\UniqueIdGenerator\UniqueIdGenerator;

/**
 * Meng-generate kode unik (mis. RAK-00001) saat model dibuat.
 * Kolom kode & prefix ditentukan lewat properti statis di model:
 *
 *     protected static string $codeColumn = 'rack_id';
 *     protected static string $codePrefix = 'RAK-';
 */
trait GeneratesCode
{
    public static function bootGeneratesCode(): void
    {
        static::creating(function ($model) {
            $column = static::$codeColumn;

            if (filled($model->{$column})) {
                return;
            }

            $model->{$column} = DB::connection()->getDriverName() === 'sqlite'
                ? static::nextCodeFallback()
                : UniqueIdGenerator::generate([
                    'table' => $model->getTable(),
                    'field' => $column,
                    'length' => static::$codeLength,
                    'prefix' => static::$codePrefix,
                ]);
        });
    }

    /**
     * Fallback untuk SQLite (testing): paket memakai information_schema
     * yang tidak tersedia di SQLite — replikasi pola max+1 secara manual.
     */
    protected static function nextCodeFallback(): string
    {
        $prefix = static::$codePrefix;
        $digits = static::$codeLength - strlen($prefix);

        $max = static::query()
            ->whereNotNull(static::$codeColumn)
            ->get()
            ->map(fn ($model) => (int) substr((string) $model->{static::$codeColumn}, strlen($prefix)))
            ->max() ?? 0;

        return $prefix.str_pad((string) ($max + 1), $digits, '0', STR_PAD_LEFT);
    }
}
