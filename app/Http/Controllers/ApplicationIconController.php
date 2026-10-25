<?php

namespace App\Http\Controllers;

use App\Models\Application;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicationIconController extends Controller
{
    /**
     * Tampilkan icon application dari disk privat (harus login); anonim tidak.
     */
    public function __invoke(string $applicationId): StreamedResponse
    {
        $application = Application::query()->where('application_id', $applicationId)->firstOrFail();

        if (blank($application->icon) || ! Storage::disk('private')->exists($application->icon)) {
            abort(404);
        }

        return Storage::disk('private')->response($application->icon, basename($application->icon), [
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
