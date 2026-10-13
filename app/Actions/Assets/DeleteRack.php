<?php

namespace App\Actions\Assets;

use App\Models\Rack;
use Illuminate\Validation\ValidationException;

class DeleteRack
{
    /**
     * @throws ValidationException bila rack masih berisi server.
     */
    public function __invoke(Rack $rack): bool
    {
        if ($rack->servers()->exists()) {
            throw ValidationException::withMessages([
                'deletingId' => 'Tidak dapat dihapus karena masih ada server di dalam rack ini.',
            ]);
        }

        return $rack->delete();
    }
}
