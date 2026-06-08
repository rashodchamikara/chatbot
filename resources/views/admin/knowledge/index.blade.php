<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Knowledge Base
                </h2>
                <div class="text-sm text-gray-500">
                    Website: {{ $website->name }}
                </div>
            </div>

            <div class="flex gap-2">
                <a href="{{ route('admin.websites.show', $website) }}" class="px-4 py-2 border rounded">
                    Back to Website
                </a>

                <a href="{{ route('admin.websites.knowledge.create', $website) }}" class="bg-blue-600 text-white px-4 py-2 rounded">
                    Add Manual Knowledge
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

            <div class="bg-white rounded shadow p-6 mb-6">
                <form method="GET" action="{{ route('admin.websites.knowledge.index', $website) }}" class="grid grid-cols-1 md:grid-cols-6 gap-4">

                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search title, URL, content..."
                        class="border rounded px-3 py-2 md:col-span-2"
                    >

                    <select name="type" class="border rounded px-3 py-2">
                        <option value="">All Types</option>
                        @foreach(['page', 'product', 'blog', 'whitepaper', 'faq', 'service', 'pricing', 'other'] as $type)
                            <option value="{{ $type }}" @selected(request('type') === $type)>
                                {{ ucfirst($type) }}
                            </option>
                        @endforeach
                    </select>

                    <select name="source_type" class="border rounded px-3 py-2">
                        <option value="">All Sources</option>
                        <option value="crawler" @selected(request('source_type') === 'crawler')>Crawler</option>
                        <option value="manual" @selected(request('source_type') === 'manual')>Manual</option>
                    </select>

                    <select name="indexed" class="border rounded px-3 py-2">
                        <option value="">Indexed?</option>
                        <option value="yes" @selected(request('indexed') === 'yes')>Indexed</option>
                        <option value="no" @selected(request('indexed') === 'no')>Not Indexed</option>
                    </select>

                    <button class="bg-blue-600 text-white px-4 py-2 rounded">
                        Filter
                    </button>
                </form>
            </div>

            <div class="bg-white rounded shadow overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="text-left px-4 py-3">Page</th>
                            <th class="text-left px-4 py-3">Type</th>
                            <th class="text-left px-4 py-3">Source</th>
                            <th class="text-left px-4 py-3">Chunks</th>
                            <th class="text-left px-4 py-3">Indexed</th>
                            <th class="text-left px-4 py-3">Active</th>
                            <th class="text-left px-4 py-3">Updated</th>
                            <th class="text-left px-4 py-3">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($pages as $page)
                            <tr class="border-t">
                                <td class="px-4 py-3">
                                    <div class="font-semibold">
                                        {{ $page->title ?? 'Untitled' }}
                                    </div>

                                    <div class="text-xs text-gray-500 break-all">
                                        {{ $page->url }}
                                    </div>

                                    <div class="text-xs text-gray-400 mt-1">
                                        {{ str($page->content)->limit(120) }}
                                    </div>
                                </td>

                                <td class="px-4 py-3">
                                    {{ ucfirst($page->type) }}
                                </td>

                                <td class="px-4 py-3">
                                    {{ ucfirst($page->source_type ?? 'crawler') }}
                                </td>

                                <td class="px-4 py-3">
                                    {{ $page->chunks_count }}
                                </td>

                                <td class="px-4 py-3">
                                    @if($page->is_indexed)
                                        <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-700">
                                            Yes
                                        </span>
                                    @else
                                        <span class="px-2 py-1 text-xs rounded bg-yellow-100 text-yellow-700">
                                            No
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 py-3">
                                    @if($page->is_active)
                                        <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-700">
                                            Active
                                        </span>
                                    @else
                                        <span class="px-2 py-1 text-xs rounded bg-red-100 text-red-700">
                                            Disabled
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-sm">
                                    {{ $page->updated_at->format('Y-m-d H:i') }}
                                </td>

                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.knowledge.show', $page) }}" class="text-blue-600">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-6 text-center text-gray-500">
                                    No knowledge pages found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $pages->links() }}
            </div>

            <div class="bg-white rounded shadow p-6 mt-6">
                <h3 class="font-semibold text-lg mb-3 text-red-700">
                    Danger Zone
                </h3>

                <form
                    method="POST"
                    action="{{ route('admin.websites.knowledge.deleteAll', $website) }}"
                    onsubmit="return confirm('This will delete all knowledge pages and chunks for this website. Continue?')"
                >
                    @csrf
                    @method('DELETE')

                    <button class="bg-red-600 text-white px-4 py-2 rounded">
                        Delete All Knowledge for This Website
                    </button>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>