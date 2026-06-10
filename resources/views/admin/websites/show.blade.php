<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Website Details
            </h2>

            <a href="{{ route('admin.websites.edit', $website) }}" class="bg-blue-600 text-white px-4 py-2 rounded">
                Edit Website
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                <div class="bg-white rounded shadow p-6">
                    <h3 class="font-semibold text-lg mb-4">Website Info</h3>

                    <div class="space-y-3 text-sm">
                        <div>
                            <strong>Name:</strong>
                            {{ $website->name }}
                        </div>

                        <div>
                            <strong>Domain:</strong>
                            {{ $website->domain }}
                        </div>

                        @if(auth()->user()->isSuperAdmin())
                            <div>
                                <strong>Tenant:</strong>
                                {{ $website->tenant->name ?? '-' }}
                            </div>
                        @endif

                        <div>
                            <strong>Status:</strong>
                            {{ $website->is_active ? 'Active' : 'Inactive' }}
                        </div>

                        <div>
                            <strong>Domain Verification:</strong>
                            {{ $website->verify_domain ? 'Enabled' : 'Disabled' }}
                        </div>

                        <div>
                            <strong>Created:</strong>
                            {{ $website->created_at->format('Y-m-d H:i') }}
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded shadow p-6">
                    <h3 class="font-semibold text-lg mb-4">Stats</h3>

                    <div class="space-y-3 text-sm">
                        <div>
                            <strong>Indexed Pages:</strong>
                            {{ $website->knowledge_pages_count }}
                        </div>

                        <div>
                            <strong>Knowledge Chunks:</strong>
                            {{ $website->knowledge_chunks_count }}
                        </div>

                        <div>
                            <strong>Conversations:</strong>
                            {{ $website->conversations_count }}
                        </div>

                        <div>
                            <strong>Leads:</strong>
                            {{ $website->leads_count }}
                        </div>
                        
                    </div>

                    <form
                        method="POST"
                        action="{{ route('admin.websites.indexKnowledge', $website) }}"
                        class="mt-6"
                    >
                        @csrf

                        <label class="block text-sm font-semibold mb-2">
                            Crawl Limit
                        </label>

                        <input
                            type="number"
                            name="limit"
                            value="20"
                            min="1"
                            max="1000"
                            class="border rounded px-3 py-2 w-full mb-3"
                        >
                        <div>
                        <strong>Indexing Status:</strong>

                        @if($website->indexing_status === 'completed')
                            <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-700">
                                Completed
                            </span>
                        @elseif($website->indexing_status === 'processing')
                            <span class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-700">
                                Processing
                            </span>
                        @elseif($website->indexing_status === 'failed')
                            <span class="px-2 py-1 text-xs rounded bg-red-100 text-red-700">
                                Failed
                            </span>
                        @else
                            <span class="px-2 py-1 text-xs rounded bg-yellow-100 text-yellow-700">
                                Pending
                            </span>
                        @endif
                    </div>
                    @if($website->indexing_started_at)
                        <div>
                            <strong>Indexing Started:</strong>
                            {{ $website->indexing_started_at->format('Y-m-d H:i') }}
                        </div>
                    @endif

                    @if($website->indexing_completed_at)
                        <div>
                            <strong>Indexing Completed:</strong>
                            {{ $website->indexing_completed_at->format('Y-m-d H:i') }}
                        </div>
                    @endif

                    @if($website->indexing_error)
                        <div class="mt-3 p-3 bg-red-50 text-red-700 rounded text-xs">
                            <strong>Indexing Error:</strong>
                            {{ $website->indexing_error }}
                        </div>
                    @endif
                        <button class="bg-green-600 text-white px-4 py-2 rounded w-full">
                            Index / Re-index Website
                        </button>
                    </form>
                    <div class="mt-4">
                        <a
                            href="{{ route('admin.websites.knowledge.index', $website) }}"
                            class="bg-blue-600 text-white px-4 py-2 rounded block text-center"
                        >
                            Manage Knowledge Base
                        </a>
                    </div>

                </div>

                <div class="bg-white rounded shadow p-6">
                    <h3 class="font-semibold text-lg mb-4">Embed Token</h3>

                    <div class="bg-gray-100 p-3 rounded text-xs break-all mb-4">
                        {{ $website->embed_token }}
                    </div>

                    <form
                        method="POST"
                        action="{{ route('admin.websites.regenerateToken', $website) }}"
                        onsubmit="return confirm('Regenerating token will break existing embed code until updated. Continue?')"
                    >
                        @csrf

                        <button class="bg-red-600 text-white px-4 py-2 rounded w-full">
                            Regenerate Token
                        </button>
                    </form>
                </div>

            </div>

            <div class="bg-white rounded shadow p-6 mt-6">
                <h3 class="font-semibold text-lg mb-4">Embed Code</h3>

                <p class="text-sm text-gray-600 mb-3">
                    Copy and paste this before the closing <code>&lt;/body&gt;</code> tag of the client website.
                </p>

                <textarea
                    readonly
                    rows="8"
                    class="border rounded px-3 py-2 w-full text-sm font-mono"
                    onclick="this.select();"
                ><script src="{{ url('https://chat.tetris.lk/widget/widget.js') }}"></script>
                    <script>
                    ChatAgent.init({
                        token: "{{ $website->embed_token }}",
                        server: "{{ url('/') }}",
                        public_server: "{{ url('/') }}/widget/"
                    });
</script></textarea>

                <p class="text-xs text-gray-500 mt-2">
                    Click inside the box to select the full embed code.
                </p>
            </div>

            <div class="bg-white rounded shadow p-6 mt-6">
                <h3 class="font-semibold text-lg mb-4">Recently Indexed Pages</h3>

                @if($website->knowledgePages->count())
                    <table class="w-full">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="text-left px-4 py-3">Title</th>
                                <th class="text-left px-4 py-3">URL</th>
                                <th class="text-left px-4 py-3">Indexed</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($website->knowledgePages as $page)
                                <tr class="border-t">
                                    <td class="px-4 py-3">
                                        {{ $page->title ?? 'Untitled' }}
                                    </td>

                                    <td class="px-4 py-3 text-sm">
                                        <a href="{{ $page->url }}" target="_blank" class="text-blue-600">
                                            {{ $page->url }}
                                        </a>
                                    </td>

                                    <td class="px-4 py-3">
                                        {{ $page->is_indexed ? 'Yes' : 'No' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-gray-500">
                        No indexed pages yet. Click “Index / Re-index Website”.
                    </p>
                @endif
            </div>

        </div>
    </div>
    @if(in_array($website->indexing_status, ['pending', 'processing']))
    <script>
        setTimeout(function () {
            window.location.reload();
        }, 10000);
    </script>
   @endif
</x-app-layout>