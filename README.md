# Afterburner Documents Package

Document management package for Laravel Afterburner Jetstream.

## Features

- **FilePond uploads to Cloudflare R2** — Hybrid single-request and native chunked uploads for large files (multi-GB supported without raising PHP upload limits)
- **Folder and file structure** — Google Drive-like hierarchical organization with slug-based URLs
- **Version control** — Track revisions and editors; per-team toggle in System Settings
- **Document linking** — Attach documents to other team records (ballots, meetings, etc.) via polymorphic links
- **In-browser preview** — PDF, images, and plain text preview without downloading
- **Document permissions by role** — Fine-grained access control with team-scoped permission gates
- **Retention tags** — BC record-keeping compliance; per-team toggle in System Settings
- **Search and filter** — Find documents quickly in the index UI
- **Audit trail** — Track edits, deletions, and access (upload chunk requests are excluded from noisy HTTP audit logging)
- **Upload notifications** — Optional database notifications when large or slow uploads complete

## Installation

### Prerequisites

This package requires the AWS S3 Flysystem adapter (Cloudflare R2 is S3-compatible) and Spatie Livewire FilePond:

```bash
composer require league/flysystem-aws-s3-v3 spatie/livewire-filepond
```

### Local Development Setup

For local development, add the package as a path repository:

```bash
composer config repositories.afterburner-documents path ../afterburner-documents
composer require laravel-afterburner/documents:@dev
composer require league/flysystem-aws-s3-v3 spatie/livewire-filepond
```

### Quick Install (Recommended)

Run the install command to automatically set up the package:

```bash
php artisan afterburner:documents:install
```

This command will:

- Publish config, migrations, and views
- Publish FilePond static assets (required for uploads)
- Add required environment variables to your `.env` file (with placeholder values)
- Update `.env.example` if it exists
- Optionally run migrations and seed document permissions

### Manual Install

If you prefer to install manually:

```bash
php artisan vendor:publish --tag=afterburner-documents-config
php artisan vendor:publish --tag=afterburner-documents-migrations
php artisan vendor:publish --tag=afterburner-documents-assets
php artisan vendor:publish --tag=livewire-filepond-assets
php artisan migrate
php artisan db:seed --class="Afterburner\\Documents\\Database\\Seeders\\DocumentPermissionsSeeder"
```

If you use `migrate:fresh --seed`, add `DocumentPermissionsSeeder` to your app's `DatabaseSeeder` (after `SystemAdminSeeder`). The seeder is also registered with `PackageSeederRegistry` when available. Without it, folder and retention-tag actions stay hidden because roles only receive `view_documents` / `create_documents` from the default role templates—not `manage_folders`.

## Configuration

After installation, configure your Cloudflare R2 credentials in `.env`. The install command will have added placeholder values — replace them with your actual credentials:

```env
AFTERBURNER_DOCUMENTS_R2_ENDPOINT=https://your-account-id.r2.cloudflarestorage.com
AFTERBURNER_DOCUMENTS_R2_ACCESS_KEY_ID=your-access-key-id
AFTERBURNER_DOCUMENTS_R2_SECRET_ACCESS_KEY=your-secret-access-key
AFTERBURNER_DOCUMENTS_R2_BUCKET=your-bucket-name
AFTERBURNER_DOCUMENTS_R2_REGION=auto
AFTERBURNER_DOCUMENTS_R2_URL=
AFTERBURNER_DOCUMENTS_R2_USE_PATH_STYLE_ENDPOINT=false
```

**Note**: You can also use the generic `CLOUDFLARE_R2_*` environment variables if you have them set up for other parts of your application. The package will fall back to those if the specific `AFTERBURNER_DOCUMENTS_R2_*` variables aren't set.

When R2 credentials are not configured, the package falls back to a local `storage/app/documents` disk for development.

### Upload Configuration

The package uses **FilePond native chunked uploads** for files larger than `CHUNK_SIZE`, and single-request uploads for smaller files. Configure limits in your `.env`:

```env
# Upload limits (bytes unless noted)
AFTERBURNER_DOCUMENTS_MAX_FILE_SIZE=3221225472   # 3GB example
AFTERBURNER_DOCUMENTS_CHUNK_SIZE=5242880         # 5MB chunks
AFTERBURNER_DOCUMENTS_MAX_CHUNKS=5000
AFTERBURNER_DOCUMENTS_SESSION_TTL_HOURS=24

# Upload completion notifications
AFTERBURNER_DOCUMENTS_NOTIFY_ON_COMPLETE=true
AFTERBURNER_DOCUMENTS_NOTIFY_MIN_SECONDS=30
AFTERBURNER_DOCUMENTS_NOTIFY_MIN_BYTES=10485760   # 10MB; set to 0 to ignore size
```

- **MAX_FILE_SIZE**: Maximum file size in bytes
- **CHUNK_SIZE**: FilePond chunk size and hybrid threshold (files larger than this use PATCH chunking)
- **MAX_CHUNKS**: Maximum number of chunks per upload
- **SESSION_TTL_HOURS**: How long abandoned upload sessions are kept before cleanup
- **NOTIFY_ON_COMPLETE**: Master switch for upload-complete database notifications
- **NOTIFY_MIN_SECONDS**: Only notify if upload + finalize took at least this many seconds
- **NOTIFY_MIN_BYTES**: Optional size floor in bytes; set to `0` to notify based on time alone

#### PHP / web server requirements

Chunked uploads only require PHP and your web server to accept requests up to the chunk size (default ~5MB):

```ini
upload_max_filesize = 8M
post_max_size = 10M
```

You do **not** need multi-gigabyte PHP upload limits for large files — chunks are uploaded separately and assembled server-side.

#### Cleanup command

Schedule the upload session cleanup command in your host app:

```php
$schedule->command('documents:cleanup-upload-sessions')->hourly();
```

See [CLOUDFLARE_R2_SETUP.md](CLOUDFLARE_R2_SETUP.md) for detailed setup instructions.

### Versioning and Retention

Global kill switches in config; per-team toggles live in **System Settings → Documents**:

```env
AFTERBURNER_DOCUMENTS_VERSIONING_ENABLED=true
AFTERBURNER_DOCUMENTS_AUTO_VERSION_ON_UPDATE=true
AFTERBURNER_DOCUMENTS_RETENTION_ENABLED=true
AFTERBURNER_DOCUMENTS_DEFAULT_RETENTION_DAYS=2555
```

## Usage

### Routes

| Route | Name | Description |
|-------|------|-------------|
| `GET /teams/{team}/documents` | `teams.documents.index` | Document index (root folder) |
| `GET /teams/{team}/documents/{folder_slug}` | `teams.documents.folder` | Browse a folder |
| `GET /teams/{team}/documents/{document}/download` | `teams.documents.download` | Download a document |
| `GET /teams/{team}/documents/{document}/preview` | `teams.documents.preview` | Inline browser preview |
| `POST /teams/{team}/documents/upload` | `teams.documents.upload.process` | FilePond upload init |
| `PATCH /teams/{team}/documents/upload/{uploadId}` | `teams.documents.upload.patch` | FilePond chunk upload |

The package registers a **Documents** navigation item and a **Documents** section in System Settings when the host app provides `Navigation` and `SystemSettings` support classes.

### Livewire Components

| Component | Description |
|-----------|-------------|
| `documents.index` | Main document browser (folders, uploads, search, preview) |
| `documents.document-viewer` | Document detail and version history modal |
| `documents.settings.documents-settings` | Team settings for versioning and retention tags |

### Linking Documents to Other Records

Use the `HasDocumentLinks` trait on any Eloquent model that belongs to a team, then link documents with the provided actions:

```php
use Afterburner\Documents\Concerns\HasDocumentLinks;
use Afterburner\Documents\Actions\LinkDocument;
use Afterburner\Documents\Actions\UnlinkDocument;

class Meeting extends Model
{
    use HasDocumentLinks;
}

// Link
app(LinkDocument::class)->execute($document, $meeting, $user);

// Unlink
app(UnlinkDocument::class)->execute($document, $meeting, $user);

// Query linked documents
$meeting->linkedDocuments;
```

### Permissions

Document permissions are seeded by `DocumentPermissionsSeeder` and enforced through Laravel policies (`DocumentPolicy`, `FolderPolicy`, `RetentionTagPolicy`). Team owners receive full document permissions automatically.

Available permission slugs include: `view_documents`, `create_documents`, `edit_documents`, `delete_documents`, `download_documents`, `manage_folders`, `manage_retention_tags`, `view_document_versions`, and others — see `DocumentPermissionDefinitions` for the full list.

## Testing

The package includes a PHPUnit test suite using Orchestra Testbench:

```bash
composer install
./vendor/bin/phpunit
```

## License

MIT License
