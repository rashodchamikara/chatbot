@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">
                Knowledge Sources
            </h1>

            <p class="text-muted mb-0">
                Upload documents for {{ $website->name }}.
            </p>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if (session('warning'))
        <div class="alert alert-warning">
            {{ session('warning') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>
                        {{ $error }}
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-header">
            Upload documents
        </div>

        <div class="card-body">
            <form
                method="POST"
                action="{{ route(
                    'websites.knowledge-sources.store',
                    $website
                ) }}"
                enctype="multipart/form-data"
            >
                @csrf

                <div class="mb-3">
                    <label
                        for="knowledge-files"
                        class="form-label"
                    >
                        Select files
                    </label>

                    <input
                        type="file"
                        name="files[]"
                        id="knowledge-files"
                        class="form-control"
                        multiple
                        required
                        accept=".pdf,.docx,.txt,.csv,.xlsx,.jpg,.jpeg,.png,.webp"
                    >

                    <div class="form-text">
                        Supported formats: PDF, DOCX, TXT, CSV,
                        XLSX, JPG, PNG and WebP.
                    </div>
                </div>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Upload and process
                </button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            Uploaded sources
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>File</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Size</th>
                            <th>Chunks</th>
                            <th>Uploaded</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($sources as $source)
                            <tr>
                                <td>
                                    {{ $source->original_name }}
                                </td>

                                <td>
                                    {{ ucfirst(
                                        $source->source_type
                                    ) }}
                                </td>

                                <td>
                                    {{ ucfirst(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $source->status
                                        )
                                    ) }}
                                </td>

                                <td>
                                    {{ number_format(
                                        $source->size_bytes / 1024,
                                        2
                                    ) }} KB
                                </td>

                                <td>
                                    {{ $source->chunk_count }}
                                </td>

                                <td>
                                    {{ optional(
                                        $source->created_at
                                    )->format('Y-m-d H:i') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="6"
                                    class="text-center py-4"
                                >
                                    No uploaded knowledge sources found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($sources->hasPages())
            <div class="card-footer">
                {{ $sources->links() }}
            </div>
        @endif
    </div>
</div>
@endsection