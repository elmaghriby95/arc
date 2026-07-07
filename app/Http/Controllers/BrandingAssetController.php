<?php

namespace App\Http\Controllers;

use App\Services\BrandingStorage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BrandingAssetController extends Controller
{
    public function show(string $file): BinaryFileResponse
    {
        $path = BrandingStorage::resolveFile($file);

        abort_unless($path, 404);

        return response()->file($path, [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
