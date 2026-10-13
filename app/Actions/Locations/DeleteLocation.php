<?php

namespace App\Actions\Locations;

use App\Models\Location;
use Illuminate\Validation\ValidationException;

class DeleteLocation
{
    /**
     * @throws ValidationException bila masih ada rack di server room.
     */
    public function __invoke(Location $location): bool
    {
        if ($location->racks()->exists()) {
            throw ValidationException::withMessages([
                'deletingId' => 'Tidak dapat dihapus karena masih ada rack di server room ini.',
            ]);
        }

        return $location->delete();
    }
}
