<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Knowledge Page Details
                </h2>
                <div class="text-sm text-gray-500">
                    {{ $knowledgePage->website->name }}
                </div>
            </div>

            <div class="flex gap-2">
                <a href="{{ route('admin.websites.knowledge.index', $knowledgePage->website) }}" class="px-4 py-2 border rounded">
                    Back
                </a>

                <a href="{{ route('admin.knowledge.edit', $knowledgePage) }}" class="bg-blue-600 text-white px-4 py-2 rounded">
                    Edit
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 p-4 bg-red-100 text-red-800 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                <div class="bg-white rounded shadow p-6">
                    <h3 class="font-semibold text-lg mb-4">Page Info</h3>

                    <div class="space-y-3 text-sm">
                        <div>
                            <strong>Title:</strong>
                            {{ $knowledgePage->title ?? '-' }}
                        </div>

                        <div>
                            <strong>URL:</strong>
                            <span class="break-all">{{ $knowledgePage->url }}</span>
                        </div>

                        <div>
                            <strong>Type:</strong>
                            {{ ucfirst($knowledgePage->type) }}
                        </div>

                        <div>
                            <strong>Source:</strong>
                            {{ ucfirst($knowledgePage->source_type ?? 'crawler') }}
                        </div>

                        <div>
                            <strong>Active:</strong>
                            {{ $knowledgePage->is_active ? 'Yes' : 'No' }}
                        </div>

                        <div>
                            <strong>Indexed:</strong>
                            {{ $knowledgePage->is_indexed ? 'Yes' : 'No' }}
                        </div>

                        <div>
                            <strong>Indexed At:</strong>
                            {{ $knowledgePage->indexed_at?->format('Y-m-d H:i') ?? '-' }}
                        </div>

                        <div>
                            <strong>Chunks:</strong>
                            {{ $knowledgePage->chunks->count() }}
                        </div>
                    </div>

                    <div class="mt-6 space-y-3">

                        <form method="POST" action="{{ route('admin.knowledge.indexPage', $knowledgePage) }}">
                            @csrf

                            <button class="bg-green-600 text-white px-4 py-2 rounded w-full">
                                Index / Re-index This Page
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.knowledge.toggleActive', $knowledgePage) }}">
                            @csrf

                            <button class="bg-yellow-600 text-white px-4 py-2 rounded w-full">
                                {{ $knowledgePage->is_active ? 'Disable Page' : 'Enable Page' }}
                            </button>
                        </form>

                        <form
                            method="POST"
                            action="{{ route('admin.knowledge.destroy', $knowledgePage) }}"
                            onsubmit="return confirm('Delete this knowledge page and all its chunks?')"
                        >
                            @csrf
                            @method('DELETE')

                            <button class="bg-red-600 text-white px-4 py-2 rounded w-full">
                                Delete Page
                            </button>
                        </form>

                    </div>
                </div>

                <div class="md:col-span-2 bg-white rounded shadow p-6">
                    <h3 class="font-semibold text-lg mb-4">Content</h3>

                    <div class="border rounded p-4 bg-gray-50 text-sm whitespace-pre-wrap max-h-96 overflow-y-auto">
                        {{ $knowledgePage->content }}
                    </div>
                </div>

            </div>

            <div class="bg-white rounded shadow p-6 mt-6">
                <h3 class="font-semibold text-lg mb-4">Knowledge Chunks</h3>

                @if($knowledgePage->chunks->count())
                    <div class="space-y-4">
                        @foreach($knowledgePage->chunks as $chunk)
                            <div class="border rounded p-4">
                                <div class="text-xs text-gray-500 mb-2">
                                    Chunk #{{ $chunk->chunk_index }}
                                    —
                                    Embedding dimensions:
                                    {{ is_array($chunk->embedding) ? count($chunk->embedding) : 0 }}
                                </div>

                                <div class="text-sm whitespace-pre-wrap">
                                    {{ $chunk->chunk_text }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500">
                        No chunks yet. Click “Index / Re-index This Page”.
                    </p>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>