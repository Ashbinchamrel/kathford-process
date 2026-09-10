<?php

namespace App\Support;

use App\Models\Setting;

class DocumentBranding
{
    /** Data shared by printable procurement documents. */
    public static function data(): array
    {
        $company = Setting::group('company');
        $storedLogo = $company['company_logo_path'] ?? null;
        $companyLogoPath = $storedLogo && is_file(storage_path('app/public/'.$storedLogo))
            ? storage_path('app/public/'.$storedLogo)
            : null;

        return compact('company', 'companyLogoPath');
    }
}
