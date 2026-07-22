<?php

namespace App\Jobs\Knowledge;

use App\Models\KnowledgeSource;
use App\Services\Knowledge\Extraction\ExtractorManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ExtractKnowledgeSourceJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    
    public int $tries = 5;

    public int $timeout = 240;

    /**
     * Delay between retries.
     *
     * @var array<int, int>
     */
    public array $backoff = [
        10,
        30,
        60,
        120,
    ];

    public function __construct(
        public int $knowledgeSourceId
    ) {
        /*
         * Keep document extraction jobs on the dedicated queue.
         */
        $this->onConnection('database');
        $this->onQueue('knowledge-extract');
    }

    /**
     * Execute the queued job.
     */
    public function handle(
        ExtractorManager $extractorManager
    ): void {
        $source = KnowledgeSource::query()
            ->find($this->knowledgeSourceId);

        /*
         * The source might have been deleted before the worker
         * started processing the job.
         */
        if (!$source) {
            Log::warning(
                'Knowledge source extraction skipped because the source no longer exists.',
                [
                    'knowledge_source_id' =>
                        $this->knowledgeSourceId,
                ]
            );

            return;
        }

        /*
         * Do not process disabled or deleted sources.
         */
        if (
            method_exists($source, 'trashed')
            && $source->trashed()
        ) {
            return;
        }

        if (
            isset($source->is_enabled)
            && !$source->is_enabled
        ) {
            return;
        }

        $temporaryFilePath = null;

        try {
            $source->forceFill([
                'status' => 'extracting',
                'processing_error' => null,
            ])->save();

            $diskName = $source->storage_disk
                ?: config('knowledge.disk', 'local');

            $storagePath = $source->storage_path;

            if (!$storagePath) {
                throw new RuntimeException(
                    'The knowledge source does not have a storage path.'
                );
            }

            $disk = Storage::disk($diskName);

            if (!$disk->exists($storagePath)) {
                throw new RuntimeException(
                    sprintf(
                        'The uploaded source file does not exist on disk "%s" at path "%s".',
                        $diskName,
                        $storagePath
                    )
                );
            }

            $temporaryFilePath =
                $this->createTemporaryLocalCopy(
                    $diskName,
                    $storagePath,
                    $source->extension
                );

            $segments = $extractorManager->extract(
                $temporaryFilePath,
                $source->mime_type,
                $source->extension
            );

            if (
                !is_array($segments)
                && !$segments instanceof \Traversable
            ) {
                throw new RuntimeException(
                    'The document extractor returned an invalid result.'
                );
            }

            $normalizedSegments = [];

            foreach ($segments as $segment) {
                if (
                    is_object($segment)
                    && method_exists($segment, 'toArray')
                ) {
                    $segment = $segment->toArray();
                } elseif (is_object($segment)) {
                    $segment = (array) $segment;
                }

                if (!is_array($segment)) {
                    continue;
                }

                $text = trim(
                    (string) ($segment['text'] ?? '')
                );

                if ($text === '') {
                    continue;
                }

                $normalizedSegments[] = [
                    'text' => $text,

                    'page_number' =>
                        isset($segment['page_number'])
                            ? (int) $segment['page_number']
                            : null,

                    'section_title' =>
                        isset($segment['section_title'])
                            ? trim(
                                (string) $segment['section_title']
                            )
                            : null,

                    'metadata' =>
                        isset($segment['metadata'])
                        && is_array($segment['metadata'])
                            ? $segment['metadata']
                            : [],
                ];
            }

            if ($normalizedSegments === []) {
                throw new RuntimeException(
                    'No readable text could be extracted from the uploaded file.'
                );
            }

            $processingVersion = max(
                1,
                (int) ($source->processing_version ?? 1)
            );

            $extractedStoragePath = sprintf(
                'knowledge/tenants/%d/websites/%d/sources/%s/extracted-v%d.json.gz',
                (int) $source->tenant_id,
                (int) $source->website_id,
                (string) $source->uuid,
                $processingVersion
            );

            $json = json_encode(
                [
                    'knowledge_source_id' =>
                        $source->id,

                    'processing_version' =>
                        $processingVersion,

                    'original_name' =>
                        $source->original_name,

                    'mime_type' =>
                        $source->mime_type,

                    'extension' =>
                        $source->extension,

                    'segments' =>
                        $normalizedSegments,
                ],
                JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_THROW_ON_ERROR
            );

            $compressedJson = gzencode(
                $json,
                6
            );

            if ($compressedJson === false) {
                throw new RuntimeException(
                    'The extracted document content could not be compressed.'
                );
            }

            $stored = $disk->put(
                $extractedStoragePath,
                $compressedJson,
                [
                    'visibility' => 'private',
                    'ContentType' => 'application/gzip',
                ]
            );

            if (!$stored) {
                throw new RuntimeException(
                    'The extracted document content could not be stored.'
                );
            }

            $pageCount = collect(
                $normalizedSegments
            )
                ->pluck('page_number')
                ->filter(
                    fn ($pageNumber) =>
                        $pageNumber !== null
                )
                ->unique()
                ->count();
            if ($pageCount === 0) {
                $pageCount = 1;
            }

            $source->forceFill([
                'extracted_storage_path' =>
                    $extractedStoragePath,

                'status' => 'chunking',

                'page_count' => $pageCount,

                'processing_error' => null,
            ])->save();


            ChunkKnowledgeSourceJob::dispatch(
                $source->id
            )
                ->onConnection('database')
                ->onQueue('knowledge-extract');
        } catch (Throwable $exception) {
            $realError = sprintf(
                '%s: %s',
                get_class($exception),
                $exception->getMessage()
            );

            $source->forceFill([
                'status' => 'failed',
                'processing_error' => mb_substr(
                    $realError,
                    0,
                    5000
                ),
            ])->save();

            Log::error(
                'Knowledge source extraction failed.',
                [
                    'knowledge_source_id' => $source->id,
                    'website_id' => $source->website_id,
                    'storage_disk' => $source->storage_disk,
                    'storage_path' => $source->storage_path,
                    'exception_class' => get_class($exception),
                    'error' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                ]
            );

            $this->fail($exception);

            return;
        } finally {
            /*
             * Always remove the temporary local file.
             */
            if (
                $temporaryFilePath
                && is_file($temporaryFilePath)
            ) {
                @unlink($temporaryFilePath);
            }
        }
    }

    
    private function createTemporaryLocalCopy(
        string $diskName,
        string $storagePath,
        ?string $extension
    ): string {
        $extension = strtolower(
            trim((string) $extension)
        );

        $extension = preg_replace(
            '/[^a-z0-9]/',
            '',
            $extension
        );

        $temporaryBasePath = tempnam(
            sys_get_temp_dir(),
            'knowledge-source-'
        );

        if ($temporaryBasePath === false) {
            throw new RuntimeException(
                'Unable to create a temporary file for document extraction.'
            );
        }

        $temporaryFilePath = $extension !== ''
            ? $temporaryBasePath . '.' . $extension
            : $temporaryBasePath;

        if (
            $temporaryFilePath !==
            $temporaryBasePath
        ) {
            if (
                !rename(
                    $temporaryBasePath,
                    $temporaryFilePath
                )
            ) {
                @unlink($temporaryBasePath);

                throw new RuntimeException(
                    'Unable to prepare the temporary extraction file.'
                );
            }
        }

        $readStream = Storage::disk(
            $diskName
        )->readStream(
            $storagePath
        );

        if (!is_resource($readStream)) {
            @unlink($temporaryFilePath);

            throw new RuntimeException(
                'Unable to read the uploaded source file from storage.'
            );
        }

        $writeStream = fopen(
            $temporaryFilePath,
            'wb'
        );

        if (!is_resource($writeStream)) {
            fclose($readStream);
            @unlink($temporaryFilePath);

            throw new RuntimeException(
                'Unable to open the temporary extraction file.'
            );
        }

        try {
            $copiedBytes = stream_copy_to_stream(
                $readStream,
                $writeStream
            );

            if ($copiedBytes === false) {
                throw new RuntimeException(
                    'Unable to copy the uploaded source file into temporary storage.'
                );
            }
        } finally {
            fclose($readStream);
            fclose($writeStream);
        }

        if (
            !is_file($temporaryFilePath)
            || filesize($temporaryFilePath) === 0
        ) {
            @unlink($temporaryFilePath);

            throw new RuntimeException(
                'The temporary extraction file is empty.'
            );
        }

        return $temporaryFilePath;
    }

    public function failed(?Throwable $exception): void
    {
        $source = KnowledgeSource::query()
            ->find($this->knowledgeSourceId);

        if (!$source) {
            return;
        }

        /*
        * Do not overwrite a more useful processing_error that was already
        * saved inside handle().
        */
        if (!empty($source->processing_error)) {
            return;
        }

        $source->forceFill([
            'status' => 'failed',
            'processing_error' => mb_substr(
                $exception
                    ? get_class($exception) . ': ' . $exception->getMessage()
                    : 'Document extraction failed after all retry attempts.',
                0,
                5000
            ),
        ])->save();

        Log::critical(
            'Knowledge source extraction permanently failed.',
            [
                'knowledge_source_id' => $this->knowledgeSourceId,
                'error' => $exception?->getMessage(),
            ]
        );
    }
}