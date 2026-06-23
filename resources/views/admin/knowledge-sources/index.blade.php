<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h4 mb-1">
                    Knowledge Sources
                </h2>

                <p class="text-muted mb-0">
                    Upload and manage knowledge files for
                    {{ $website->name }}.
                </p>
            </div>

            <a
                href="{{ route(
                    'admin.websites.show',
                    $website
                ) }}"
                class="btn btn-outline-secondary"
            >
                Back to Website
            </a>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container">
            @if (session('success'))
                <div
                    class="alert alert-success alert-dismissible fade show"
                    role="alert"
                >
                    {{ session('success') }}

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                        aria-label="Close"
                    ></button>
                </div>
            @endif

            @if (session('warning'))
                <div
                    class="alert alert-warning alert-dismissible fade show"
                    role="alert"
                >
                    {{ session('warning') }}

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                        aria-label="Close"
                    ></button>
                </div>
            @endif

            @if ($errors->any())
                <div
                    class="alert alert-danger"
                    role="alert"
                >
                    <div class="fw-semibold mb-2">
                        Please correct the following issues:
                    </div>

                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>
                                {{ $error }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h3 class="h5 mb-1">
                        Upload Documents
                    </h3>

                    <p class="text-muted small mb-0">
                        Uploaded files will be processed and added to
                        the chatbot knowledge base.
                    </p>
                </div>

                <div class="card-body">
                    <form
                        method="POST"
                        action="{{ route(
                            'admin.websites.knowledge-sources.store',
                            $website
                        ) }}"
                        enctype="multipart/form-data"
                    >
                        @csrf

                        <div class="mb-3">
                            <label
                                for="knowledge-files"
                                class="form-label fw-semibold"
                            >
                                Select files
                            </label>

                            <input
                                type="file"
                                name="files[]"
                                id="knowledge-files"
                                class="form-control @error('files') is-invalid @enderror"
                                multiple
                                required
                                accept=".pdf,.docx,.txt,.csv,.xlsx,.jpg,.jpeg,.png,.webp"
                            >

                            <div class="form-text">
                                Supported formats: PDF, DOCX, TXT,
                                CSV, XLSX, JPG, JPEG, PNG and WebP.
                            </div>

                            @error('files')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            Upload and Process
                        </button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div
                    class="card-header bg-white py-3 d-flex justify-content-between align-items-center"
                >
                    <div>
                        <h3 class="h5 mb-1">
                            Uploaded Sources
                        </h3>

                        <p class="text-muted small mb-0">
                            Files uploaded for this website.
                        </p>
                    </div>

                    <span class="badge bg-secondary">
                        {{ $sources->total() }}
                    </span>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table
                            class="table table-hover align-middle mb-0"
                        >
                            <thead class="table-light">
                                <tr>
                                    <th class="px-4">
                                        File
                                    </th>

                                    <th>
                                        Type
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                    <th>
                                        Size
                                    </th>

                                    <th>
                                        Chunks
                                    </th>

                                    <th class="pe-4">
                                        Uploaded
                                    </th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($sources as $source)
                                    <tr>
                                        <td class="px-4">
                                            <div class="fw-semibold">
                                                {{ $source->original_name }}
                                            </div>

                                            @if ($source->processing_error)
                                                <div class="text-danger small mt-1">
                                                    {{ $source->processing_error }}
                                                </div>
                                            @endif
                                        </td>

                                        <td>
                                            <span class="badge bg-light text-dark">
                                                {{ ucfirst(
                                                    $source->source_type
                                                ) }}
                                            </span>
                                        </td>

                                        <td>
                                            @php
                                                $statusClass = match (
                                                    $source->status
                                                ) {
                                                    'ready' =>
                                                        'bg-success',

                                                    'failed' =>
                                                        'bg-danger',

                                                    'queued',
                                                    'extracting',
                                                    'chunking',
                                                    'embedding' =>
                                                        'bg-warning text-dark',

                                                    default =>
                                                        'bg-secondary',
                                                };
                                            @endphp

                                            <span
                                                class="badge {{ $statusClass }}"
                                            >
                                                {{ ucfirst(
                                                    str_replace(
                                                        '_',
                                                        ' ',
                                                        $source->status
                                                    )
                                                ) }}
                                            </span>
                                        </td>

                                        <td>
                                            @if ($source->size_bytes)
                                                {{ number_format(
                                                    $source->size_bytes / 1024,
                                                    2
                                                ) }}
                                                KB
                                            @else
                                                —
                                            @endif
                                        </td>

                                        <td>
                                            {{ $source->chunk_count ?? 0 }}
                                        </td>

                                        <td class="pe-4">
                                            {{ optional(
                                                $source->created_at
                                            )->format('Y-m-d H:i') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td
                                            colspan="6"
                                            class="text-center py-5"
                                        >
                                            <div class="text-muted">
                                                No uploaded knowledge
                                                sources found.
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if ($sources->hasPages())
                    <div class="card-footer bg-white">
                        {{ $sources->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>