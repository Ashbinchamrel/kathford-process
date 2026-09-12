<?php

namespace App\Support;

use App\Models\Setting;

class DocumentBranding
{
    /**
     * Bounding box the logo must fit inside on printable documents.
     * dompdf doesn't support CSS `object-fit`, so the scaled width/height
     * has to be computed here and applied as explicit pixel dimensions.
     */
    private const LOGO_MAX_WIDTH = 150;
    private const LOGO_MAX_HEIGHT = 70;

    /** Data shared by printable procurement documents. */
    public static function data(): array
    {
        $company = Setting::group('company');
        $storedLogo = $company['company_logo_path'] ?? null;
        $companyLogoPath = $storedLogo && is_file(storage_path('app/public/'.$storedLogo))
            ? storage_path('app/public/'.$storedLogo)
            : null;

        [$logoWidth, $logoHeight] = $companyLogoPath
            ? self::fitLogo($companyLogoPath)
            : [self::LOGO_MAX_WIDTH, self::LOGO_MAX_HEIGHT];

        return compact('company', 'companyLogoPath', 'logoWidth', 'logoHeight');
    }

    /** Scale the logo's real dimensions down to fit the bounding box, preserving aspect ratio. */
    private static function fitLogo(string $path): array
    {
        $size = @getimagesize($path);
        if (! $size) {
            return [self::LOGO_MAX_WIDTH, self::LOGO_MAX_HEIGHT];
        }

        [$naturalWidth, $naturalHeight] = $size;
        $scale = min(self::LOGO_MAX_WIDTH / $naturalWidth, self::LOGO_MAX_HEIGHT / $naturalHeight, 1);

        return [
            (int) round($naturalWidth * $scale),
            (int) round($naturalHeight * $scale),
        ];
    }
}
