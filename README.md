# Afterburner Documents Package

Document management package for Laravel Afterburner Jetstream.

## Features

- **Chunked uploads to Cloudflare R2** - Efficient large file uploads
- **Folder and file structure** - Google Drive-like hierarchical organization
- **Version control** - Track revisions and editors
- **Document permissions by role** - Fine-grained access control
- **Retention tags** - BC record-keeping compliance
- **Search and filter** - Find documents quickly
- **Audit trail** - Track edits, deletions, and access

## Installation

### Prerequisites

If you plan to use Cloudflare R2 for storage (recommended), you'll need to install the AWS S3 Flysystem adapter:

```bash
composer require league/flysystem-aws-s3-v3
```

**Note**: This package is required because Cloudflare R2 is S3-compatible and uses the same adapter.

### Local Development Setup

For local development, add the package as a path repository:

```bash
composer config repositories.afterburner-documents path ../afterburner-documents
composer require laravel-afterburner/documents:@dev
composer require league/flysystem-aws-s3-v3  # Required for R2 support
```

### Quick Install (Recommended)

Run the install command to automatically set up the package:

```bash
php artisan documents:install
```

This command will:
- Publish config, migrations, and views
- Add required environment variables to your `.env` file (with placeholder values)
- Update `.env.example` if it exists

### Manual Install

If you prefer to install manually:

```bash
php artisan vendor:publish --tag=afterburner-documents-config
php artisan vendor:publish --tag=afterburner-documents-migrations
php artisan vendor:publish --tag=afterburner-documents-assets
php artisan migrate
```

## Configuration

After installation, configure your Cloudflare R2 credentials in `.env`. The install command will have added placeholder values - replace them with your actual credentials:

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

### Upload Configuration

The package supports chunked uploads for large files. Configure upload limits in your `.env`:

```env
# Upload Limits (max_file_size in bytes, max_chunks per upload, chunk_size in bytes)
# Default: 2GB max file size, 5000 max chunks, 5MB chunk size
AFTERBURNER_DOCUMENTS_MAX_FILE_SIZE=2147483648
AFTERBURNER_DOCUMENTS_MAX_CHUNKS=5000
AFTERBURNER_DOCUMENTS_CHUNK_SIZE=5242880
```

- **MAX_FILE_SIZE**: Maximum file size in bytes (default: 2GB = 2,147,483,648 bytes)
- **MAX_CHUNKS**: Maximum number of chunks per upload (default: 5,000)
- **CHUNK_SIZE**: Size of each chunk in bytes (default: 5MB = 5,242,880 bytes)

Files larger than the chunk size are automatically uploaded using chunked uploads. For example, a 2GB file with 5MB chunks will be split into ~409 chunks.

#### PHP Configuration Requirements

**Important**: Livewire file uploads respect PHP's `upload_max_filesize` and `post_max_size` settings. If you encounter errors like "The file field must not be greater than X kilobytes", you need to increase these PHP settings.

For large file uploads, ensure your `php.ini` has:

```ini
upload_max_filesize = 256M
post_max_size = 256M
```

Or set them in your `.htaccess` (if using Apache):

```apache
php_value upload_max_filesize 256M
php_value post_max_size 256M
```

**Note**: Files larger than PHP's `upload_max_filesize` will automatically use chunked uploads, but Livewire may still reject them during initial validation. It's recommended to set `upload_max_filesize` to at least match your `CHUNK_SIZE` setting (default 5MB) or higher for better compatibility.

See [CLOUDFLARE_R2_SETUP.md](CLOUDFLARE_R2_SETUP.md) for detailed setup instructions.

## Usage

Document management functionality will be available through Livewire components and controllers once implemented.

## Development Status

🚧 **In Development** - This package is currently under active development.

## License

MIT License

