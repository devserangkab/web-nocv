<?php

namespace App\Enums;

use App\Models\Panel;
use App\Models\Server;
use App\Models\Vms;

/**
 * Jenis host deployment application — nilai turunan dari relasi polymorphic
 * `hostings.hostable` (tidak disimpan di database).
 */
enum HostingKind: string
{
    case Server = 'server';

    case Vm = 'vm';

    case Panel = 'panel';

    case External = 'external';

    /**
     * Label Indonesia untuk UI (badge, select, dsb).
     */
    public function label(): string
    {
        return match ($this) {
            self::Server => 'Server Fisik',
            self::Vm => 'Virtual Machine',
            self::Panel => 'Panel',
            self::External => 'Eksternal',
        };
    }

    /**
     * Model host yang mewakili jenis ini; null untuk host eksternal.
     *
     * @return class-string<Server|Vms|Panel>|null
     */
    public function hostClass(): ?string
    {
        return match ($this) {
            self::Server => Server::class,
            self::Vm => Vms::class,
            self::Panel => Panel::class,
            self::External => null,
        };
    }

    /**
     * Ikon Tabler untuk jenis host.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Server => 'ti-server',
            self::Vm => 'ti-box-multiple-3',
            self::Panel => 'ti-app-window',
            self::External => 'ti-cloud',
        };
    }

    /**
     * Kode unik yang dipakai untuk meng-resolve instance host.
     */
    public function codeColumn(): string
    {
        return match ($this) {
            self::Server => 'server_id',
            self::Vm => 'vm_id',
            self::Panel => 'panel_id',
            self::External => throw new \LogicException('External host tidak punya kode instance.'),
        };
    }

    /**
     * Konversi morph class menjadi kind; null bila bukan host internal.
     *
     * @param  class-string|null  $hostableType
     */
    public static function fromHostable(?string $hostableType): ?self
    {
        return match ($hostableType) {
            Vms::class => self::Vm,
            Server::class => self::Server,
            Panel::class => self::Panel,
            default => null,
        };
    }
}
