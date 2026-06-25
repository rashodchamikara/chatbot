<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2 text-sm text-slate-500">
                    <a
                        href="{{ route('admin.websites.index') }}"
                        class="transition hover:text-blue-600"
                    >
                        Websites
                    </a>

                    <svg
                        class="h-4 w-4 text-slate-400"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="m9 18 6-6-6-6"
                        />
                    </svg>

                    <a
                        href="{{ route('admin.websites.show', $website) }}"
                        class="max-w-xs truncate transition hover:text-blue-600"
                    >
                        {{ $website->name }}
                    </a>
                </div>

                <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">
                    Knowledge Sources
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Upload files that the AI assistant can use when answering customer questions.
                </p>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row">
                <a
                    href="{{ route('admin.websites.knowledge.index', $website) }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700"
                >
                    <svg
                        class="h-4 w-4"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"
                        />

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"
                        />
                    </svg>

                    Crawled Knowledge
                </a>

                <a
                    href="{{ route('admin.websites.show', $website) }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800"
                >
                    <svg
                        class="h-4 w-4"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="m15 18-6-6 6-6"
                        />
                    </svg>

                    Back to Website
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $totalSources = $sources->total();

        $readySources = $sources->getCollection()
            ->where('status', 'ready')
            ->count();

        $processingSources = $sources->getCollection()
            ->whereIn('status', [
                'queued',
                'extracting',
                'chunking',
                'embedding',
                'processing',
            ])
            ->count();

        $failedSources = $sources->getCollection()
            ->where('status', 'failed')
            ->count();

        $pageChunkCount = $sources->getCollection()
            ->sum(function ($source) {
                return (int) ($source->chunk_count ?? 0);
            });
    @endphp

    <div class="min-h-screen bg-slate-50 py-8">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">

            {{-- Success message --}}
            @if(session('success'))
                <div
                    class="mb-6 flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-sm"
                    role="alert"
                >
                    <div class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                        <svg
                            class="h-3.5 w-3.5"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.5"
                            aria-hidden="true"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="m5 12 4 4L19 6"
                            />
                        </svg>
                    </div>

                    <div>
                        <p class="font-semibold">
                            Upload successful
                        </p>

                        <p class="mt-0.5">
                            {{ session('success') }}
                        </p>
                    </div>
                </div>
            @endif

            {{-- Warning message --}}
            @if(session('warning'))
                <div
                    class="mb-6 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 shadow-sm"
                    role="alert"
                >
                    <svg
                        class="mt-0.5 h-5 w-5 shrink-0 text-amber-500"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 9v4"
                        />

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 17h.01"
                        />

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M10.3 3.7 2.8 17a2 2 0 0 0 1.7 3h15a2 2 0 0 0 1.7-3L13.7 3.7a2 2 0 0 0-3.4 0Z"
                        />
                    </svg>

                    <div>
                        <p class="font-semibold">
                            Please review
                        </p>

                        <p class="mt-0.5">
                            {{ session('warning') }}
                        </p>
                    </div>
                </div>
            @endif

            {{-- Validation errors --}}
            @if($errors->any())
                <div
                    class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-4 text-sm text-red-800 shadow-sm"
                    role="alert"
                >
                    <div class="flex items-start gap-3">
                        <svg
                            class="mt-0.5 h-5 w-5 shrink-0 text-red-500"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            aria-hidden="true"
                        >
                            <circle cx="12" cy="12" r="9"></circle>

                            <path
                                stroke-linecap="round"
                                d="M12 8v5"
                            ></path>

                            <path
                                stroke-linecap="round"
                                d="M12 16h.01"
                            ></path>
                        </svg>

                        <div>
                            <p class="font-semibold">
                                The upload could not be completed
                            </p>

                            <ul class="mt-2 list-disc space-y-1 pl-5">
                                @foreach($errors->all() as $error)
                                    <li>
                                        {{ $error }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Website summary --}}
            <section class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-col gap-5 p-5 sm:p-6 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex min-w-0 items-center gap-4">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 ring-1 ring-blue-100">
                            <svg
                                class="h-7 w-7"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                aria-hidden="true"
                            >
                                <circle cx="12" cy="12" r="9"></circle>
                                <path d="M3 12h18"></path>
                                <path d="M12 3a15 15 0 0 1 0 18"></path>
                                <path d="M12 3a15 15 0 0 0 0 18"></path>
                            </svg>
                        </div>

                        <div class="min-w-0">
                            <h2 class="truncate text-lg font-semibold text-slate-900">
                                {{ $website->name }}
                            </h2>

                            <p class="mt-1 truncate text-sm text-slate-500">
                                {{ $website->domain }}
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:min-w-[520px]">
                        <div class="rounded-xl bg-slate-50 px-4 py-3">
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
                                Sources
                            </p>

                            <p class="mt-1 text-xl font-semibold text-slate-900">
                                {{ number_format($totalSources) }}
                            </p>
                        </div>

                        <div class="rounded-xl bg-emerald-50 px-4 py-3">
                            <p class="text-xs font-medium uppercase tracking-wide text-emerald-600">
                                Ready
                            </p>

                            <p class="mt-1 text-xl font-semibold text-emerald-700">
                                {{ number_format($readySources) }}
                            </p>
                        </div>

                        <div class="rounded-xl bg-amber-50 px-4 py-3">
                            <p class="text-xs font-medium uppercase tracking-wide text-amber-600">
                                Processing
                            </p>

                            <p class="mt-1 text-xl font-semibold text-amber-700">
                                {{ number_format($processingSources) }}
                            </p>
                        </div>

                        <div class="rounded-xl bg-violet-50 px-4 py-3">
                            <p class="text-xs font-medium uppercase tracking-wide text-violet-600">
                                Page Chunks
                            </p>

                            <p class="mt-1 text-xl font-semibold text-violet-700">
                                {{ number_format($pageChunkCount) }}
                            </p>
                        </div>
                    </div>
                </div>

                @if($failedSources > 0)
                    <div class="border-t border-red-100 bg-red-50 px-5 py-3 text-sm text-red-700 sm:px-6">
                        <span class="font-semibold">
                            {{ $failedSources }}
                            {{ \Illuminate\Support\Str::plural('source', $failedSources) }}
                        </span>

                        failed during processing. Review the error details in the source list below.
                    </div>
                @endif
            </section>

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

                {{-- Upload panel --}}
                <section class="xl:col-span-1">
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                                    <svg
                                        class="h-5 w-5"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        aria-hidden="true"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M12 16V4"
                                        />

                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="m7 9 5-5 5 5"
                                        />

                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M5 20h14"
                                        />
                                    </svg>
                                </div>

                                <div>
                                    <h2 class="font-semibold text-slate-900">
                                        Upload Knowledge
                                    </h2>

                                    <p class="mt-0.5 text-sm text-slate-500">
                                        Add one or more source files.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <form
                            method="POST"
                            action="{{ route(
                                'admin.websites.knowledge-sources.store',
                                $website
                            ) }}"
                            enctype="multipart/form-data"
                            class="p-5"
                            id="knowledge-upload-form"
                        >
                            @csrf

                            <label
                                for="knowledge-files"
                                id="knowledge-drop-zone"
                                class="group flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center transition hover:border-blue-400 hover:bg-blue-50/50"
                            >
                                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-blue-600 shadow-sm ring-1 ring-slate-200 transition group-hover:ring-blue-200">
                                    <svg
                                        class="h-7 w-7"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        aria-hidden="true"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M12 16V4"
                                        />

                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="m7 9 5-5 5 5"
                                        />

                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M5 20h14"
                                        />
                                    </svg>
                                </div>

                                <span class="mt-4 text-sm font-semibold text-slate-900">
                                    Select files to upload
                                </span>

                                <span class="mt-1 text-sm text-slate-500">
                                    or drag and drop them here
                                </span>

                                <span class="mt-3 rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-500 ring-1 ring-slate-200">
                                    PDF, DOCX, TXT, CSV, XLSX, JPG, PNG or WebP
                                </span>

                                <input
                                    id="knowledge-files"
                                    name="files[]"
                                    type="file"
                                    multiple
                                    required
                                    accept=".pdf,.docx,.txt,.csv,.xlsx,.jpg,.jpeg,.png,.webp"
                                    class="sr-only"
                                >
                            </label>

                            <div
                                id="selected-file-summary"
                                class="mt-4 hidden rounded-xl border border-blue-100 bg-blue-50 px-4 py-3"
                            >
                                <div class="flex items-start gap-3">
                                    <svg
                                        class="mt-0.5 h-5 w-5 shrink-0 text-blue-600"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        aria-hidden="true"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"
                                        />

                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M14 2v6h6"
                                        />
                                    </svg>

                                    <div class="min-w-0">
                                        <p
                                            id="selected-file-count"
                                            class="text-sm font-semibold text-blue-900"
                                        ></p>

                                        <p
                                            id="selected-file-names"
                                            class="mt-1 break-words text-xs leading-5 text-blue-700"
                                        ></p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Processing workflow
                                </h3>

                                <div class="mt-3 space-y-3">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-semibold text-blue-700">
                                            1
                                        </span>

                                        <p class="text-sm text-slate-600">
                                            File is securely uploaded.
                                        </p>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-semibold text-blue-700">
                                            2
                                        </span>

                                        <p class="text-sm text-slate-600">
                                            Text is extracted and divided into searchable chunks.
                                        </p>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-semibold text-blue-700">
                                            3
                                        </span>

                                        <p class="text-sm text-slate-600">
                                            The content becomes available to the AI assistant.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <button
                                type="submit"
                                id="knowledge-upload-button"
                                class="mt-5 inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                <svg
                                    id="upload-button-icon"
                                    class="h-4 w-4"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    aria-hidden="true"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M12 16V4"
                                    />

                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="m7 9 5-5 5 5"
                                    />

                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M5 20h14"
                                    />
                                </svg>

                                <svg
                                    id="upload-spinner"
                                    class="hidden h-4 w-4 animate-spin"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    aria-hidden="true"
                                >
                                    <circle
                                        class="opacity-25"
                                        cx="12"
                                        cy="12"
                                        r="10"
                                        stroke="currentColor"
                                        stroke-width="4"
                                    ></circle>

                                    <path
                                        class="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"
                                    ></path>
                                </svg>

                                <span id="upload-button-text">
                                    Upload and Process
                                </span>
                            </button>
                        </form>
                    </div>
                </section>

                {{-- Sources list --}}
                <section class="xl:col-span-2">
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 class="font-semibold text-slate-900">
                                    Uploaded Sources
                                </h2>

                                <p class="mt-1 text-sm text-slate-500">
                                    Monitor uploaded files and their processing status.
                                </p>
                            </div>

                            <span class="inline-flex w-fit items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                                {{ number_format($totalSources) }}
                                {{ \Illuminate\Support\Str::plural('source', $totalSources) }}
                            </span>
                        </div>

                        {{-- Desktop table --}}
                        <div class="hidden lg:block">
                            <div class="overflow-x-auto">
                                <table class="w-full table-auto border-collapse">
                                    <thead class="bg-slate-50">
                                        <tr class="border-b border-slate-200">
                                            <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                Source
                                            </th>

                                            <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                Status
                                            </th>

                                            <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                Knowledge
                                            </th>

                                            <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                Size
                                            </th>

                                            <th class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                Uploaded
                                            </th>
                                        </tr>
                                    </thead>

                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        @forelse($sources as $source)
                                            @php
                                                $status = strtolower(
                                                    (string) ($source->status ?? 'unknown')
                                                );

                                                $statusLabel = match ($status) {
                                                    'queued' => 'Queued',
                                                    'extracting' => 'Extracting',
                                                    'chunking' => 'Preparing',
                                                    'embedding' => 'Indexing',
                                                    'processing' => 'Processing',
                                                    'ready' => 'Ready',
                                                    'failed' => 'Failed',
                                                    'disabled' => 'Disabled',
                                                    default => ucfirst(
                                                        str_replace('_', ' ', $status)
                                                    ),
                                                };

                                                $statusClasses = match ($status) {
                                                    'ready' =>
                                                        'bg-emerald-50 text-emerald-700 ring-emerald-200',

                                                    'failed' =>
                                                        'bg-red-50 text-red-700 ring-red-200',

                                                    'queued',
                                                    'extracting',
                                                    'chunking',
                                                    'embedding',
                                                    'processing' =>
                                                        'bg-amber-50 text-amber-700 ring-amber-200',

                                                    'disabled' =>
                                                        'bg-slate-100 text-slate-600 ring-slate-200',

                                                    default =>
                                                        'bg-slate-100 text-slate-600 ring-slate-200',
                                                };

                                                $statusDotClasses = match ($status) {
                                                    'ready' => 'bg-emerald-500',
                                                    'failed' => 'bg-red-500',

                                                    'queued',
                                                    'extracting',
                                                    'chunking',
                                                    'embedding',
                                                    'processing' => 'bg-amber-500',

                                                    default => 'bg-slate-400',
                                                };

                                                $extension = strtoupper(
                                                    (string) (
                                                        $source->extension
                                                        ?? pathinfo(
                                                            (string) $source->original_name,
                                                            PATHINFO_EXTENSION
                                                        )
                                                        ?: $source->source_type
                                                        ?: 'FILE'
                                                    )
                                                );

                                                $fileIconClasses = match (
                                                    strtolower(
                                                        (string) (
                                                            $source->extension
                                                            ?? $source->source_type
                                                        )
                                                    )
                                                ) {
                                                    'pdf' =>
                                                        'bg-red-50 text-red-600 ring-red-100',

                                                    'doc',
                                                    'docx',
                                                    'document' =>
                                                        'bg-blue-50 text-blue-600 ring-blue-100',

                                                    'csv',
                                                    'xls',
                                                    'xlsx',
                                                    'spreadsheet' =>
                                                        'bg-emerald-50 text-emerald-600 ring-emerald-100',

                                                    'txt',
                                                    'text' =>
                                                        'bg-slate-100 text-slate-600 ring-slate-200',

                                                    'jpg',
                                                    'jpeg',
                                                    'png',
                                                    'webp',
                                                    'image' =>
                                                        'bg-violet-50 text-violet-600 ring-violet-100',

                                                    default =>
                                                        'bg-slate-100 text-slate-600 ring-slate-200',
                                                };
                                            @endphp

                                            <tr class="transition hover:bg-slate-50">
                                                <td class="px-5 py-4 align-top">
                                                    <div class="flex min-w-0 items-start gap-3">
                                                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl ring-1 {{ $fileIconClasses }}">
                                                            <svg
                                                                class="h-5 w-5"
                                                                viewBox="0 0 24 24"
                                                                fill="none"
                                                                stroke="currentColor"
                                                                stroke-width="2"
                                                                aria-hidden="true"
                                                            >
                                                                <path
                                                                    stroke-linecap="round"
                                                                    stroke-linejoin="round"
                                                                    d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"
                                                                />

                                                                <path
                                                                    stroke-linecap="round"
                                                                    stroke-linejoin="round"
                                                                    d="M14 2v6h6"
                                                                />
                                                            </svg>
                                                        </div>

                                                        <div class="min-w-0">
                                                            <p
                                                                class="max-w-xs truncate text-sm font-semibold text-slate-900"
                                                                title="{{ $source->original_name }}"
                                                            >
                                                                {{ $source->original_name }}
                                                            </p>

                                                            <div class="mt-1 flex flex-wrap items-center gap-2">
                                                                <span class="text-xs font-medium text-slate-500">
                                                                    {{ $extension }}
                                                                </span>

                                                                @if(!empty($source->source_type))
                                                                    <span class="h-1 w-1 rounded-full bg-slate-300"></span>

                                                                    <span class="text-xs text-slate-500">
                                                                        {{ ucfirst(
                                                                            str_replace(
                                                                                '_',
                                                                                ' ',
                                                                                $source->source_type
                                                                            )
                                                                        ) }}
                                                                    </span>
                                                                @endif
                                                            </div>

                                                            @if(!empty($source->processing_error))
                                                                <div class="mt-2 max-w-md rounded-lg border border-red-100 bg-red-50 px-3 py-2">
                                                                    <p class="text-xs leading-5 text-red-700">
                                                                        {{ \Illuminate\Support\Str::limit(
                                                                            $source->processing_error,
                                                                            180
                                                                        ) }}
                                                                    </p>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>

                                                <td class="px-5 py-4 align-top">
                                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $statusClasses }}">
                                                        <span class="h-1.5 w-1.5 rounded-full {{ $statusDotClasses }}"></span>

                                                        {{ $statusLabel }}
                                                    </span>

                                                    @if(
                                                        in_array(
                                                            $status,
                                                            [
                                                                'queued',
                                                                'extracting',
                                                                'chunking',
                                                                'embedding',
                                                                'processing',
                                                            ],
                                                            true
                                                        )
                                                    )
                                                        <p class="mt-2 text-xs text-slate-500">
                                                            Processing in background
                                                        </p>
                                                    @endif
                                                </td>

                                                <td class="px-5 py-4 align-top">
                                                    <p class="text-sm font-semibold text-slate-900">
                                                        {{ number_format(
                                                            (int) ($source->chunk_count ?? 0)
                                                        ) }}
                                                        {{ \Illuminate\Support\Str::plural(
                                                            'chunk',
                                                            (int) ($source->chunk_count ?? 0)
                                                        ) }}
                                                    </p>

                                                    @if(!empty($source->page_count))
                                                        <p class="mt-1 text-xs text-slate-500">
                                                            {{ number_format(
                                                                (int) $source->page_count
                                                            ) }}
                                                            {{ \Illuminate\Support\Str::plural(
                                                                'page',
                                                                (int) $source->page_count
                                                            ) }}
                                                        </p>
                                                    @else
                                                        <p class="mt-1 text-xs text-slate-500">
                                                            Searchable knowledge
                                                        </p>
                                                    @endif
                                                </td>

                                                <td class="px-5 py-4 align-top">
                                                    <p class="text-sm text-slate-700">
                                                        @if(!empty($source->size_bytes))
                                                            @php
                                                                $sizeBytes = (int) $source->size_bytes;

                                                                if ($sizeBytes >= 1048576) {
                                                                    $formattedSize =
                                                                        number_format(
                                                                            $sizeBytes / 1048576,
                                                                            2
                                                                        ) . ' MB';
                                                                } else {
                                                                    $formattedSize =
                                                                        number_format(
                                                                            $sizeBytes / 1024,
                                                                            2
                                                                        ) . ' KB';
                                                                }
                                                            @endphp

                                                            {{ $formattedSize }}
                                                        @else
                                                            —
                                                        @endif
                                                    </p>
                                                </td>

                                                <td class="px-5 py-4 text-right align-top">
                                                    <p class="text-sm font-medium text-slate-700">
                                                        {{ optional(
                                                            $source->created_at
                                                        )->format('d M Y') }}
                                                    </p>

                                                    <p class="mt-1 text-xs text-slate-500">
                                                        {{ optional(
                                                            $source->created_at
                                                        )->format('H:i') }}
                                                    </p>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td
                                                    colspan="5"
                                                    class="px-6 py-16 text-center"
                                                >
                                                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                                        <svg
                                                            class="h-8 w-8"
                                                            viewBox="0 0 24 24"
                                                            fill="none"
                                                            stroke="currentColor"
                                                            stroke-width="2"
                                                            aria-hidden="true"
                                                        >
                                                            <path
                                                                stroke-linecap="round"
                                                                stroke-linejoin="round"
                                                                d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"
                                                            />

                                                            <path
                                                                stroke-linecap="round"
                                                                stroke-linejoin="round"
                                                                d="M14 2v6h6"
                                                            />

                                                            <path
                                                                stroke-linecap="round"
                                                                stroke-linejoin="round"
                                                                d="M8 13h8M8 17h5"
                                                            />
                                                        </svg>
                                                    </div>

                                                    <h3 class="mt-4 text-sm font-semibold text-slate-900">
                                                        No knowledge sources uploaded
                                                    </h3>

                                                    <p class="mx-auto mt-1 max-w-md text-sm text-slate-500">
                                                        Upload a document, spreadsheet, text file or image to add business knowledge to the AI assistant.
                                                    </p>

                                                    <button
                                                        type="button"
                                                        onclick="document.getElementById('knowledge-files').click()"
                                                        class="mt-5 inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
                                                    >
                                                        Select your first file
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Mobile and tablet cards --}}
                        <div class="divide-y divide-slate-100 lg:hidden">
                            @forelse($sources as $source)
                                @php
                                    $mobileStatus = strtolower(
                                        (string) ($source->status ?? 'unknown')
                                    );

                                    $mobileStatusLabel = match ($mobileStatus) {
                                        'queued' => 'Queued',
                                        'extracting' => 'Extracting',
                                        'chunking' => 'Preparing',
                                        'embedding' => 'Indexing',
                                        'processing' => 'Processing',
                                        'ready' => 'Ready',
                                        'failed' => 'Failed',
                                        'disabled' => 'Disabled',
                                        default => ucfirst(
                                            str_replace(
                                                '_',
                                                ' ',
                                                $mobileStatus
                                            )
                                        ),
                                    };

                                    $mobileStatusClasses = match ($mobileStatus) {
                                        'ready' =>
                                            'bg-emerald-50 text-emerald-700',

                                        'failed' =>
                                            'bg-red-50 text-red-700',

                                        'queued',
                                        'extracting',
                                        'chunking',
                                        'embedding',
                                        'processing' =>
                                            'bg-amber-50 text-amber-700',

                                        default =>
                                            'bg-slate-100 text-slate-600',
                                    };
                                @endphp

                                <article class="p-5">
                                    <div class="flex items-start gap-3">
                                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600 ring-1 ring-blue-100">
                                            <svg
                                                class="h-5 w-5"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                aria-hidden="true"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"
                                                />

                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M14 2v6h6"
                                                />
                                            </svg>
                                        </div>

                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-sm font-semibold text-slate-900">
                                                {{ $source->original_name }}
                                            </p>

                                            <p class="mt-1 text-xs text-slate-500">
                                                {{ ucfirst(
                                                    str_replace(
                                                        '_',
                                                        ' ',
                                                        (string) $source->source_type
                                                    )
                                                ) }}
                                            </p>
                                        </div>

                                        <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold {{ $mobileStatusClasses }}">
                                            {{ $mobileStatusLabel }}
                                        </span>
                                    </div>

                                    <div class="mt-4 grid grid-cols-3 gap-2">
                                        <div class="rounded-xl bg-slate-50 p-3 text-center">
                                            <p class="text-base font-semibold text-slate-900">
                                                {{ number_format(
                                                    (int) ($source->chunk_count ?? 0)
                                                ) }}
                                            </p>

                                            <p class="mt-1 text-[11px] text-slate-500">
                                                Chunks
                                            </p>
                                        </div>

                                        <div class="rounded-xl bg-slate-50 p-3 text-center">
                                            <p class="text-base font-semibold text-slate-900">
                                                {{ number_format(
                                                    (int) ($source->page_count ?? 0)
                                                ) }}
                                            </p>

                                            <p class="mt-1 text-[11px] text-slate-500">
                                                Pages
                                            </p>
                                        </div>

                                        <div class="rounded-xl bg-slate-50 p-3 text-center">
                                            <p class="truncate text-sm font-semibold text-slate-900">
                                                @if(!empty($source->size_bytes))
                                                    @php
                                                        $mobileSize =
                                                            (int) $source->size_bytes;

                                                        if ($mobileSize >= 1048576) {
                                                            echo number_format(
                                                                $mobileSize / 1048576,
                                                                1
                                                            ) . ' MB';
                                                        } else {
                                                            echo number_format(
                                                                $mobileSize / 1024,
                                                                0
                                                            ) . ' KB';
                                                        }
                                                    @endphp
                                                @else
                                                    —
                                                @endif
                                            </p>

                                            <p class="mt-1 text-[11px] text-slate-500">
                                                File size
                                            </p>
                                        </div>
                                    </div>

                                    @if(!empty($source->processing_error))
                                        <div class="mt-4 rounded-xl border border-red-100 bg-red-50 px-3 py-2">
                                            <p class="text-xs leading-5 text-red-700">
                                                {{ \Illuminate\Support\Str::limit(
                                                    $source->processing_error,
                                                    220
                                                ) }}
                                            </p>
                                        </div>
                                    @endif

                                    <div class="mt-4 flex items-center justify-between text-xs text-slate-500">
                                        <span>
                                            Uploaded
                                        </span>

                                        <span class="font-medium text-slate-700">
                                            {{ optional(
                                                $source->created_at
                                            )->format('d M Y, H:i') }}
                                        </span>
                                    </div>
                                </article>
                            @empty
                                <div class="px-6 py-14 text-center">
                                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                        <svg
                                            class="h-7 w-7"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            aria-hidden="true"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"
                                            />

                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M14 2v6h6"
                                            />
                                        </svg>
                                    </div>

                                    <h3 class="mt-4 text-sm font-semibold text-slate-900">
                                        No knowledge sources found
                                    </h3>

                                    <p class="mt-1 text-sm text-slate-500">
                                        Upload your first knowledge file to begin.
                                    </p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    @if($sources->hasPages())
                        <div class="mt-6">
                            {{ $sources->links() }}
                        </div>
                    @endif
                </section>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('knowledge-upload-form');
            const fileInput = document.getElementById('knowledge-files');
            const dropZone = document.getElementById('knowledge-drop-zone');
            const summary = document.getElementById('selected-file-summary');
            const fileCount = document.getElementById('selected-file-count');
            const fileNames = document.getElementById('selected-file-names');
            const uploadButton = document.getElementById('knowledge-upload-button');
            const uploadButtonText = document.getElementById('upload-button-text');
            const uploadButtonIcon = document.getElementById('upload-button-icon');
            const uploadSpinner = document.getElementById('upload-spinner');

            if (
                !form ||
                !fileInput ||
                !dropZone ||
                !summary ||
                !fileCount ||
                !fileNames
            ) {
                return;
            }

            function formatFileSize(bytes) {
                if (!Number.isFinite(bytes) || bytes <= 0) {
                    return '0 KB';
                }

                if (bytes >= 1024 * 1024) {
                    return (
                        bytes / (1024 * 1024)
                    ).toFixed(2) + ' MB';
                }

                return (
                    bytes / 1024
                ).toFixed(2) + ' KB';
            }

            function updateFileSummary() {
                const files = Array.from(fileInput.files || []);

                if (files.length === 0) {
                    summary.classList.add('hidden');
                    fileCount.textContent = '';
                    fileNames.textContent = '';

                    return;
                }

                const totalBytes = files.reduce(function (total, file) {
                    return total + file.size;
                }, 0);

                fileCount.textContent =
                    files.length +
                    (files.length === 1 ? ' file selected' : ' files selected') +
                    ' · ' +
                    formatFileSize(totalBytes);

                fileNames.textContent = files
                    .map(function (file) {
                        return file.name;
                    })
                    .join(', ');

                summary.classList.remove('hidden');
            }

            fileInput.addEventListener('change', updateFileSummary);

            ['dragenter', 'dragover'].forEach(function (eventName) {
                dropZone.addEventListener(eventName, function (event) {
                    event.preventDefault();
                    event.stopPropagation();

                    dropZone.classList.remove(
                        'border-slate-300',
                        'bg-slate-50'
                    );

                    dropZone.classList.add(
                        'border-blue-500',
                        'bg-blue-50'
                    );
                });
            });

            ['dragleave', 'drop'].forEach(function (eventName) {
                dropZone.addEventListener(eventName, function (event) {
                    event.preventDefault();
                    event.stopPropagation();

                    dropZone.classList.remove(
                        'border-blue-500',
                        'bg-blue-50'
                    );

                    dropZone.classList.add(
                        'border-slate-300',
                        'bg-slate-50'
                    );
                });
            });

            dropZone.addEventListener('drop', function (event) {
                const droppedFiles = event.dataTransfer.files;

                if (!droppedFiles || droppedFiles.length === 0) {
                    return;
                }

                try {
                    const transfer = new DataTransfer();

                    Array.from(droppedFiles).forEach(function (file) {
                        transfer.items.add(file);
                    });

                    fileInput.files = transfer.files;
                    updateFileSummary();
                } catch (error) {
                    fileInput.click();
                }
            });

            form.addEventListener('submit', function () {
                if (!fileInput.files || fileInput.files.length === 0) {
                    return;
                }

                uploadButton.disabled = true;
                uploadButtonText.textContent = 'Uploading...';
                uploadButtonIcon.classList.add('hidden');
                uploadSpinner.classList.remove('hidden');
            });
        });
    </script>
</x-app-layout>