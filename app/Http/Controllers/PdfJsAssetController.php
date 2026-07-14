<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PdfJsAssetController extends Controller
{
    public function show(string $file): BinaryFileResponse
    {
        $allowed = [
            'pdf.min.js',
            'pdf.worker.min.js',
        ];

        abort_unless(in_array($file, $allowed, true), 404);

        $path = resource_path('js/vendor/pdfjs/'.$file);

        abort_unless(is_readable($path), 404);

        return response()->file($path, [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'Cache-Control' => 'public, max-age=604800, immutable',
        ]);
    }
}
