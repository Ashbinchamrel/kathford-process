<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;

/**
 * Optimises raster uploads at the application boundary. PDFs are kept exactly
 * as supplied because lossless PDF optimisation needs a dedicated worker
 * (Ghostscript/qpdf) and must never silently change a legal invoice.
 */
class UploadOptimizationService
{
    /** @return array{path: string, original_name: string, mime_type: string, file_size: int} */
    public function store(UploadedFile $file, string $directory, string $disk = 'private'): array
    {
        $mime = $file->getMimeType() ?: 'application/octet-stream';

        if (in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            try {
                $encoded = ImageManager::gd()
                    ->read($file->getRealPath())
                    ->scaleDown(width: 2400, height: 2400)
                    ->toWebp(quality: 82);
                $path = trim($directory, '/').'/'.Str::uuid().'.webp';
                Storage::disk($disk)->put($path, (string) $encoded);

                return [
                    'path' => $path,
                    'original_name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME).'.webp',
                    'mime_type' => 'image/webp',
                    'file_size' => strlen((string) $encoded),
                ];
            } catch (\Throwable) {
                // Store the source safely if an image driver cannot decode it.
                // This also covers exotic source files while preserving uploads.
            }
        }

        $path = $file->store($directory, $disk);

        return [
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $mime,
            'file_size' => $file->getSize(),
        ];
    }
}
