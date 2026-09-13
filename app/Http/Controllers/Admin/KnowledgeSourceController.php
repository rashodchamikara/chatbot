<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreKnowledgeSourceRequest;
use App\Jobs\Knowledge\ExtractKnowledgeSourceJob;
use App\Models\KnowledgeSource;
use App\Models\Website;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class KnowledgeSourceController extends Controller
{
    public function index(Website $website)
    {
        $this->authorizeWebsiteAccess($website);

        $sources = KnowledgeSource::query()
            ->where('tenant_id', $website->tenant_id)
            ->where('website_id', $website->id)
            ->latest()
            ->paginate(20);

        return view(
            'admin.knowledge-sources.index',
            compact('website', 'sources')
        );
    }

    public function store(
        StoreKnowledgeSourceRequest $request,
        Website $website
    ): RedirectResponse {
        $this->authorizeWebsiteAccess($website);

        $disk = config('knowledge.disk');

        foreach ($request->file('files') as $file) {
            $checksum = hash_file('sha256', $file->getRealPath());

            $alreadyExists = KnowledgeSource::query()
                ->where('tenant_id', $website->tenant_id)
                ->where('ai_agent_id', $website->ai_agent_id)
                ->where('website_id', $website->id)
                ->where('checksum_sha256', $checksum)
                ->whereNull('deleted_at')
                ->exists();

            if ($alreadyExists) {
                continue;
            }

            $sourceUuid = (string) Str::uuid();
            $extension = strtolower($file->getClientOriginalExtension());

            $directory = sprintf(
                'knowledge/tenants/%d/websites/%d/sources/%s',
                $website->tenant_id,
                $website->id,
                $sourceUuid
            );

            $storagePath = Storage::disk($disk)->putFileAs(
                $directory,
                $file,
                'original.' . $extension,
                ['visibility' => 'private']
            );

            try {
                $source = KnowledgeSource::query()->create([
                    'uuid' => $sourceUuid,
                    'tenant_id' => $website->tenant_id,
                    'ai_agent_id' => $website->ai_agent_id,
                    'website_id' => $website->id,
                    'uploaded_by' => auth()->id(),
                    'source_type' => $this->resolveSourceType($extension),
                    'name' => pathinfo(
                        $file->getClientOriginalName(),
                        PATHINFO_FILENAME
                    ),
                    'original_name' => $file->getClientOriginalName(),
                    'storage_disk' => $disk,
                    'storage_path' => $storagePath,
                    'mime_type' => $file->getMimeType(),
                    'extension' => $extension,
                    'size_bytes' => $file->getSize(),
                    'checksum_sha256' => $checksum,
                    'status' => 'queued',
                    'is_enabled' => true,
                    'processing_version' => 1,
                    'active_version' => 0,
                ]);
            } catch (Throwable $exception) {
                Storage::disk($disk)->delete($storagePath);
                throw $exception;
            }

            ExtractKnowledgeSourceJob::dispatch($source->id)
                ->onQueue('knowledge-extract')
                ->afterCommit();
        }

        return back()->with(
            'success',
            'Documents were uploaded and queued for processing.'
        );
    }

    private function resolveSourceType(string $extension): string
    {
        return match ($extension) {
            'pdf' => 'pdf',
            'docx' => 'document',
            'csv', 'xlsx' => 'spreadsheet',
            'jpg', 'jpeg', 'png', 'webp' => 'image',
            default => 'text',
        };
    }

    private function authorizeWebsiteAccess(Website $website): void
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return;
        }

        if ((int) $website->tenant_id !== (int) $user->tenant_id) {
            abort(403, 'Unauthorized website access.');
        }
    }
}
