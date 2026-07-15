<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PdfJsAssetController extends Controller
{
    public function show(string $file): BinaryFileResponse
    {
        $map = [
            'pdf.min.js' => 'pdfjs/pdf.min.js',
            'pdf.worker.min.js' => 'pdfjs/pdf.worker.min.js',
        ];

        abort_unless(isset($map[$file]), 404);

        $path = resource_path('js/vendor/'.$map[$file]);

        abort_unless(is_readable($path), 404);

        return response()->file($path, [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'Cache-Control' => 'public, max-age=604800, immutable',
        ]);
    }
}
