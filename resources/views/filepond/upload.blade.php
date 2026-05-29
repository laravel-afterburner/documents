@php
$isCustomPlaceholder = isset($placeholder);
@endphp

@props([
    'multiple' => false,
    'required' => false,
    'disabled' => false,
    'maxFiles' => null,
    'placeholder' => __('Drag & Drop your files or <span class="filepond--label-action"> Browse </span>'),
    'maxfilesmsg' => __('You can upload a maximum of :max files.'),
    'uploadUrl' => null,
    'chunkSize' => null,
    'folderId' => null,
    'uploadNotes' => null,
])

@php
if (! $wireModelAttribute = $attributes->whereStartsWith('wire:model')->first()) {
    throw new Exception("You must wire:model to the filepond input.");
}

$pondProperties = $attributes->except([
    'class',
    'placeholder',
    'required',
    'disabled',
    'multiple',
    'wire:model',
    'upload-url',
    'uploadUrl',
    'chunk-size',
    'chunkSize',
    'folder-id',
    'folderId',
    'upload-notes',
    'uploadNotes',
]);

if ($maxFiles !== null) {
    $pondProperties['max-files'] = $maxFiles;
}

$pondProperties = collect($pondProperties)
    ->mapWithKeys(fn ($value, $key) => [Illuminate\Support\Str::camel($key) => $value])
    ->toArray();

$pondLocalizations = __('livewire-filepond::filepond');
$resolvedUploadUrl = $uploadUrl ?? '';
$resolvedChunkSize = (int) ($chunkSize ?? config('afterburner-documents.upload.chunk_size', 5242880));
@endphp

<div
    class="{{ $attributes->get('class') }}"
    wire:ignore
    x-cloak
    x-data="{
        model: @entangle($wireModelAttribute),
        isMultiple: @js($multiple),
        current: undefined,
        files: [],
        uploadUrl: @js($resolvedUploadUrl),
        chunkSize: @js($resolvedChunkSize),
        csrfToken: @js(csrf_token()),
@if($folderId !== null)
        folderId: @entangle($folderId),
@else
        folderId: null,
@endif
@if($uploadNotes !== null)
        uploadNotes: @entangle($uploadNotes),
@else
        uploadNotes: null,
@endif
        appendUploadContext(formData) {
            if (this.folderId !== null && this.folderId !== undefined && this.folderId !== '') {
                formData.append('folderId', this.folderId);
            }

            if (this.uploadNotes) {
                formData.append('notes', this.uploadNotes);
            }

            return formData;
        },
        resolveUploadError(response) {
            if (typeof response === 'string' && response.trim() !== '') {
                return response.trim();
            }

            if (response?.body && typeof response.body === 'string' && response.body.trim() !== '') {
                return response.body.trim();
            }

            if (response?.main && typeof response.main === 'string' && response.main.trim() !== '') {
                return response.main.trim();
            }

            return @js(__('Upload failed. Please try again.'));
        },
        async loadModel() {
            if (! this.model) {
              return;
            }

            if (this.isMultiple) {
              await Promise.all(Object.values(this.model).map(async (picture) => this.files.push(await URLtoFile(picture))))
              return;
            }

            try {
                this.files.push(await URLtoFile(this.model))
            } catch (e) {
                console.error(e)
            }
        }
    }"
    x-init="async () => {
      while (typeof window.LivewireFilePond === 'undefined' || typeof window.URLtoFile === 'undefined') {
          await new Promise(resolve => setTimeout(resolve, 50));
      }

      await loadModel();

      const pond = window.LivewireFilePond.create($refs.input);

      const normalizedUploadUrl = uploadUrl.replace(/\/$/, '');

      const serverConfig = uploadUrl ? {
          timeout: 0,
          process: {
              url: normalizedUploadUrl,
              method: 'POST',
              headers: (file) => ({
                  'X-CSRF-TOKEN': csrfToken,
                  'Accept': 'text/plain',
                  'X-Requested-With': 'XMLHttpRequest',
                  'Upload-Length': file.size,
                  'Upload-Name': file.name,
              }),
              ondata: (formData) => appendUploadContext(formData),
              onerror: (response) => resolveUploadError(response),
          },
          patch: {
              url: `${normalizedUploadUrl}/`,
              headers: (chunk) => ({
                  'X-CSRF-TOKEN': csrfToken,
                  'Accept': 'text/plain',
                  'X-Requested-With': 'XMLHttpRequest',
                  'Content-Type': 'application/offset+octet-stream',
                  'Upload-Offset': chunk.offset,
                  'Upload-Length': chunk.file.size,
                  'Upload-Name': chunk.file.name,
              }),
              onerror: (response) => resolveUploadError(response),
          },
          revert: (uniqueId, load, error) => {
              fetch(`${normalizedUploadUrl}/${uniqueId}`, {
                  method: 'DELETE',
                  headers: {
                      'X-CSRF-TOKEN': csrfToken,
                      'Accept': 'text/plain',
                      'X-Requested-With': 'XMLHttpRequest',
                  },
              })
                  .then((response) => {
                      if (! response.ok) {
                          throw new Error('Unable to revert upload.');
                      }

                      load();
                  })
                  .catch(error);
          },
      } : {
          process: async (fieldName, file, metadata, load, error, progress) => {
              $wire.dispatchSelf('filepond-upload-started', '{{ $wireModelAttribute }}');
              await @this.upload('{{ $wireModelAttribute }}', file, async (response) => {
                let validationResult  = await @this.call('validateUploadedFile', response);
                    if (validationResult === true) {
                        load(response);
                        $wire.dispatchSelf('filepond-upload-finished', { '{{ $wireModelAttribute }}': response });
                    } else {
                        error('Filepond Api Ignores This Message');
                        $wire.dispatchSelf('filepond-upload-reset', '{{ $wireModelAttribute }}');
                    }
              }, error, (event) => {
                    progress(event.detail.progress, event.detail.progress, 100);
            });
          },
          revert: async (filename, load) => {
              await @this.revert('{{ $wireModelAttribute }}', filename, load);
              $wire.dispatchSelf('filepond-upload-reverted', {'attribute' : '{{ $wireModelAttribute }}'});
          },
          remove: async (file, load) => {
              await @this.remove('{{ $wireModelAttribute }}', file.name);
              load();
              $wire.dispatchSelf('filepond-upload-file-removed', {'attribute' : '{{ $wireModelAttribute }}'});
          },
      };

      pond.setOptions({
          allowMultiple: isMultiple,
          chunkUploads: !!uploadUrl,
          chunkSize: chunkSize,
          chunkForce: false,
          server: serverConfig,
          required: @js($required),
          disabled: @js($disabled),
      });

      pond.setOptions(@js($pondLocalizations));
      pond.setOptions(@js($pondProperties));
      pond.setOptions({
          labelFileProcessingError: (error) => resolveUploadError(error),
          labelFileTypeNotAllowed: @js(__('File type is not allowed.')),
          fileValidateTypeLabelExpectedTypes: @js(__('Allowed: documents, spreadsheets, presentations, images, and ZIP files.')),
      });

      @if($isCustomPlaceholder)
      pond.setOptions({ labelIdle: @js($placeholder) });
      @endif

      let batchPending = 0;
      let batchSucceeded = 0;
      let batchFailed = 0;

      pond.addFiles(files)
      pond.on('addfile', (error, file) => {
          if (error) console.log(error);
      });

      pond.on('warning', (error) => {
          if (error?.body === 'Max files' && {{ $maxFiles ? 'true' : 'false' }}) {
              const message = @js($maxfilesmsg).replace(':max', {{ $maxFiles ?? 0 }});
              $wire.call('setMaxFilesError', message);
          }
      });

      pond.on('processfilestart', () => {
          batchPending++;
      });

      pond.on('processfile', (error) => {
          batchPending--;

          if (error) {
              batchFailed++;
          } else {
              batchSucceeded++;
          }

          if (batchPending > 0) {
              return;
          }

          if (batchSucceeded > 0) {
              $wire.dispatch('documents-upload-complete', {
                  succeeded: batchSucceeded,
                  failed: batchFailed,
              });
          }

          batchPending = 0;
          batchSucceeded = 0;
          batchFailed = 0;
      });

      pond.on('processfiles', () => {
          $wire.dispatchSelf('filepond-upload-completed', {'attribute' : '{{ $wireModelAttribute }}'});
      });

      $wire.on('filepond-reset-{{ $wireModelAttribute }}', () => {
          pond.removeFiles();
      });
    }"
>
    <input type="file" x-ref="input">
</div>
