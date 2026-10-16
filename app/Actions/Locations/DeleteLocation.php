<?php

namespace App\Actions\Locations;

use App\Actions\Activities\RecordActivity;
use App\Models\Location;
use App\Support\Authorize;
use Illuminate\Validation\ValidationException;

class DeleteLocation
{
    /**
     * @throws ValidationException bila masih ada rack atau server langsung di server room.
     */
    public function __invoke(Location $location): bool
    {
        Authorize::infrastructureManage();
        if ($location->racks()->exists()) {
            throw ValidationException::withMessages([
                'deletingId' => 'Tidak dapat dihapus karena masih ada rack di server room ini.',
            ]);
        }

        if ($location->servers()->exists()) {
            throw ValidationException::withMessages([
                'deletingId' => 'Tidak dapat dihapus karena masih ada server yang terhubung langsung ke server room ini.',
            ]);
        }

        $deleted = $location->delete();

        app(RecordActivity::class)->audit($location, 'Menghapus server room '.($location->name ?? '-').' ('.($location->location_id ?? '-').')');

        return $deleted;
    }
}
