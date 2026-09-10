<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Allowed Email Domain
    |--------------------------------------------------------------------------
    | New accounts created by an administrator must use this email domain.
    */
    'allowed_email_domain' => env('ALLOWED_EMAIL_DOMAIN', 'kathford.edu.np'),

    /*
    |--------------------------------------------------------------------------
    | Two-Factor Authentication
    |--------------------------------------------------------------------------
    */
    'two_factor_required' => env('TWO_FACTOR_REQUIRED', true),

    /*
    |--------------------------------------------------------------------------
    | RFQ Vendor Link Expiry
    |--------------------------------------------------------------------------
    */
    'rfq_link_expiry_days' => env('RFQ_LINK_EXPIRY_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Application Name (for PDFs, emails)
    |--------------------------------------------------------------------------
    */
    'college_name'    => env('COLLEGE_NAME', 'Kathford International College'),
    'college_address' => env('COLLEGE_ADDRESS', 'Balkumari, Lalitpur, Nepal'),
    'college_phone'   => env('COLLEGE_PHONE', ''),
    'college_website' => env('COLLEGE_WEBSITE', 'https://kathford.edu.np'),

    /*
    |--------------------------------------------------------------------------
    | Fiscal Year (Nepali BS)
    |--------------------------------------------------------------------------
    | Used in form numbering. Override if needed.
    */
    'fiscal_year_start_month' => 4, // Shrawan = month 4 in BS

    /*
    |--------------------------------------------------------------------------
    | File Upload Limits
    |--------------------------------------------------------------------------
    */
    'max_attachment_size_kb' => env('MAX_ATTACHMENT_SIZE_KB', 10240), // 10 MB
    'allowed_mime_types'     => [
        'image/jpeg',
        'image/png',
        'image/gif',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ],

];
