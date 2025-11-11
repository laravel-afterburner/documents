# Implementation Plan: Retention Tags, Chunked Uploads, and Version Restore

## Overview
This plan covers the implementation of three advanced features for the Afterburner Documents package:
1. **Retention Tags** - BC record-keeping compliance
2. **Chunked Uploads** - Efficient large file uploads to Cloudflare R2
3. **Version Restore** - Restore previous document versions

---

## 1. Retention Tags

### Purpose
Implement retention tags for BC record-keeping compliance. Documents can be tagged with retention policies that prevent deletion until the retention period expires.

### Implementation Steps

#### 1.1 Database Schema
- Create migration: `create_retention_tags_table.php`
  - Fields: `id`, `team_id`, `name`, `slug`, `retention_period_days`, `description`, `color`, `created_by`, `timestamps`
- Create migration: `add_retention_tag_id_to_documents_table.php`
  - Add `retention_tag_id` foreign key to documents table
  - Add `retention_expires_at` timestamp field

#### 1.2 Model
- Create `RetentionTag` model
  - Relationships: `team()`, `documents()`, `creator()`
  - Methods: `generateSlug()`, `isExpired()`
- Update `Document` model
  - Add `retentionTag()` relationship
  - Add `retention_expires_at` to fillable and casts
  - Add method: `isRetentionProtected()`, `canBeDeleted()`

#### 1.3 Actions
- Create `AssignRetentionTag` action
- Create `CreateRetentionTag` action
- Create `UpdateRetentionTag` action
- Update `DeleteDocument` action to check retention protection

#### 1.4 Policies
- Create `RetentionTagPolicy`
- Update `DocumentPolicy` to check retention before deletion

#### 1.5 UI Components
- Add retention tag selector to document upload/edit forms
- Add retention tag management page (admin)
- Display retention tag badge on document cards
- Show retention expiration date in document viewer

#### 1.6 Seeder
- Update `DocumentPermissionsSeeder` to include retention tag permissions (already exists: `manage_retention_tags`)

---

## 2. Chunked Uploads

### Purpose
Implement proper chunked upload functionality using FilePond's chunked upload API to handle large files efficiently.

### Implementation Steps

#### 2.1 Backend Endpoints
- Create `ChunkedUploadController`
  - `POST /teams/{team}/documents/chunks` - Upload a chunk
  - `POST /teams/{team}/documents/chunks/assemble` - Assemble chunks into document
  - `DELETE /teams/{team}/documents/chunks/{chunkId}` - Delete a chunk (cleanup)

#### 2.2 Storage Service Updates
- Update `StorageService` to handle chunk tracking
- Add method: `trackChunk()`, `getChunkPaths()`, `cleanupChunks()`

#### 2.3 Chunk Tracking
- Create migration: `create_document_chunks_table.php`
  - Fields: `id`, `document_id`, `chunk_index`, `chunk_id`, `storage_path`, `size`, `created_at`
  - Indexes for efficient lookup

#### 2.4 Upload Flow
1. Frontend: FilePond sends chunks to `/chunks` endpoint
2. Backend: Store each chunk in R2, track in database
3. Frontend: After all chunks uploaded, call `/chunks/assemble`
4. Backend: Assemble chunks, create document record, cleanup chunks

#### 2.5 Livewire Component Updates
- Update `Index` component to use chunked upload API
- Add progress tracking for chunked uploads
- Handle chunk upload errors gracefully

#### 2.6 Configuration
- Ensure chunk size config is properly used
- Add validation for chunk limits

---

## 3. Version Restore

### Purpose
Allow users to restore previous versions of documents.

### Implementation Steps

#### 3.1 Action
- Create `RestoreDocumentVersion` action
  - Copy version file to current document storage path
  - Create new version from current document (before restore)
  - Update document with restored version data
  - Create audit log entry

#### 3.2 Policy
- Update `DocumentPolicy` to add `restoreVersion()` method
  - Check `restore_document_versions` permission

#### 3.3 UI Integration
- Add "Restore" button to version list in `DocumentViewer`
- Add confirmation modal for restore action
- Show success/error messages
- Refresh document after restore

#### 3.4 Document Model Updates
- Add helper method: `restoreVersion(DocumentVersion $version)`

---

## Implementation Order

1. **Version Restore** (Simplest, builds on existing version system)
2. **Retention Tags** (Medium complexity, new feature)
3. **Chunked Uploads** (Most complex, requires new endpoints and flow)

---

## Testing Considerations

- Test retention tag assignment and enforcement
- Test chunked upload with various file sizes
- Test version restore and verify audit trail
- Test edge cases (expired retention, failed chunks, etc.)

