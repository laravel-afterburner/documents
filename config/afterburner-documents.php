<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Documents Enabled
    |--------------------------------------------------------------------------
    |
    | Master switch for documents functionality.
    |
    */

    'enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | Cloudflare R2 Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Cloudflare R2 storage. These settings control how
    | documents are uploaded and stored in Cloudflare R2.
    |
    */

    'r2' => [
        'endpoint' => env('AFTERBURNER_DOCUMENTS_R2_ENDPOINT'),
        'access_key_id' => env('AFTERBURNER_DOCUMENTS_R2_ACCESS_KEY_ID'),
        'secret_access_key' => env('AFTERBURNER_DOCUMENTS_R2_SECRET_ACCESS_KEY'),
        'bucket' => env('AFTERBURNER_DOCUMENTS_R2_BUCKET'),
        'region' => env('AFTERBURNER_DOCUMENTS_R2_REGION', 'auto'),
        'url' => env('AFTERBURNER_DOCUMENTS_R2_URL'),
        'use_path_style_endpoint' => env('AFTERBURNER_DOCUMENTS_R2_USE_PATH_STYLE_ENDPOINT', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disk Configuration
    |--------------------------------------------------------------------------
    |
    | The R2 disk configuration that will be automatically registered in
    | Laravel's filesystem configuration. This disk is registered programmatically
    | by the DocumentsServiceProvider.
    |
    */

    'filesystem_disk' => [
        'driver' => 's3',
        'key' => env('AFTERBURNER_DOCUMENTS_R2_ACCESS_KEY_ID'),
        'secret' => env('AFTERBURNER_DOCUMENTS_R2_SECRET_ACCESS_KEY'),
        'region' => env('AFTERBURNER_DOCUMENTS_R2_REGION', 'auto'),
        'bucket' => env('AFTERBURNER_DOCUMENTS_R2_BUCKET'),
        'url' => env('AFTERBURNER_DOCUMENTS_R2_URL'),
        'endpoint' => env('AFTERBURNER_DOCUMENTS_R2_ENDPOINT'),
        'use_path_style_endpoint' => env('AFTERBURNER_DOCUMENTS_R2_USE_PATH_STYLE_ENDPOINT', false),
        'throw' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Upload Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for document uploads, including chunk size and limits.
    |
    */

    'upload' => [
        'chunk_size' => 5242880, // 5MB in bytes
        'max_file_size' => 3221225472, // 3GB in bytes
        'max_chunks' => 5000, // Maximum number of chunks per upload
        'session_ttl_hours' => 24,
        'notify_on_complete' => [
            'enabled' => true,
            'min_seconds' => 30,
            'min_bytes' => 10485760, // 10MB; 0 disables size floor
        ],
        'allowed_mime_types' => [
            // Documents
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain',
            'text/csv',
            // Images
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            // Archives
            'application/zip',
            'application/x-zip-compressed',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Path Structure
    |--------------------------------------------------------------------------
    |
    | Define the path structure for storing documents in R2.
    | Available placeholders: {team_id}, {year}, {month}, {document_id}
    |
    */

    'storage_path' => 'documents/{team_id}/{year}/{month}/{document_id}',

    /*
    |--------------------------------------------------------------------------
    | Version Control
    |--------------------------------------------------------------------------
    |
    | Configuration for document version control.
    |
    */

    'versioning' => [
        // Global kill switch. Per-team toggles live in System Settings → Documents.
        'enabled' => true,
        'auto_version_on_update' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Retention Tags
    |--------------------------------------------------------------------------
    |
    | Configuration for retention tag compliance (BC record-keeping).
    |
    */

    'retention' => [
        // Global kill switch. Per-team toggles live in System Settings → Documents.
        'enabled' => true,
        'default_retention_period_days' => 2555, // ~7 years
    ],

    /*
    |--------------------------------------------------------------------------
    | Search Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for document search functionality.
    |
    */

    'search' => [
        'enabled' => true,
        'index_document_content' => false, // Future: full-text search
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Configuration
    |--------------------------------------------------------------------------
    |
    | HTTP routes to exclude from request-level audit logging during uploads.
    | A single document.uploaded audit entry is written when storage completes.
    |
    */

    'audit' => [
        'skip_routes' => [
            'teams.documents.upload.process',
            'teams.documents.upload.patch',
            'teams.documents.upload.head',
            'teams.documents.upload.revert',
        ],
    ],

];
