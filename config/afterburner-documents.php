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

    'enabled' => env('AFTERBURNER_DOCUMENTS_ENABLED', true),

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
        'endpoint' => env('AFTERBURNER_DOCUMENTS_R2_ENDPOINT', env('CLOUDFLARE_R2_ENDPOINT')),
        'access_key_id' => env('AFTERBURNER_DOCUMENTS_R2_ACCESS_KEY_ID', env('CLOUDFLARE_R2_ACCESS_KEY_ID')),
        'secret_access_key' => env('AFTERBURNER_DOCUMENTS_R2_SECRET_ACCESS_KEY', env('CLOUDFLARE_R2_SECRET_ACCESS_KEY')),
        'bucket' => env('AFTERBURNER_DOCUMENTS_R2_BUCKET', env('CLOUDFLARE_R2_BUCKET')),
        'region' => env('AFTERBURNER_DOCUMENTS_R2_REGION', env('CLOUDFLARE_R2_REGION', 'auto')),
        'url' => env('AFTERBURNER_DOCUMENTS_R2_URL', env('CLOUDFLARE_R2_URL')),
        'use_path_style_endpoint' => env('AFTERBURNER_DOCUMENTS_R2_USE_PATH_STYLE_ENDPOINT', env('CLOUDFLARE_R2_USE_PATH_STYLE_ENDPOINT', false)),
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
        'chunk_size' => env('AFTERBURNER_DOCUMENTS_CHUNK_SIZE', 5242880), // 5MB in bytes
        'max_file_size' => env('AFTERBURNER_DOCUMENTS_MAX_FILE_SIZE', 2147483648), // 2GB in bytes
        'max_chunks' => env('AFTERBURNER_DOCUMENTS_MAX_CHUNKS', 5000), // Maximum number of chunks per upload
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

    'storage_path' => env('AFTERBURNER_DOCUMENTS_STORAGE_PATH', 'documents/{team_id}/{year}/{month}/{document_id}'),

    /*
    |--------------------------------------------------------------------------
    | Version Control
    |--------------------------------------------------------------------------
    |
    | Configuration for document version control.
    |
    */

    'versioning' => [
        'enabled' => env('AFTERBURNER_DOCUMENTS_VERSIONING_ENABLED', true),
        'auto_version_on_update' => env('AFTERBURNER_DOCUMENTS_AUTO_VERSION_ON_UPDATE', true),
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
        'enabled' => env('AFTERBURNER_DOCUMENTS_RETENTION_ENABLED', true),
        'default_retention_period_days' => env('AFTERBURNER_DOCUMENTS_DEFAULT_RETENTION_DAYS', 2555), // ~7 years
    ],

    /*
    |--------------------------------------------------------------------------
    | Permissions
    |--------------------------------------------------------------------------
    |
    | Default permissions for document access.
    |
    */

    'permissions' => [
        'default_view_roles' => ['member', 'owner'],
        'default_edit_roles' => ['admin', 'owner'],
        'default_delete_roles' => ['admin', 'owner'],
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
        'enabled' => env('AFTERBURNER_DOCUMENTS_SEARCH_ENABLED', true),
        'index_document_content' => env('AFTERBURNER_DOCUMENTS_INDEX_CONTENT', false), // Future: full-text search
    ],

];

