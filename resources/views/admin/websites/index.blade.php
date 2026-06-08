<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Websites
            </h2>

            <a href="{{ route('admin.websites.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded">
                Add Website
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

            <div class="bg-white rounded shadow p-6 mb-6">
                <form method="GET" action="{{ route('admin.websites.index') }}" class="flex gap-4">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search website name or domain..."
                        class="border rounded px-3 py-2 w-full"
                    >

                    <select name="status" class="border rounded px-3 py-2">
                        <option value="">All Statuses</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
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
                            <th class="text-left px-4 py-3">Website</th>
                            @if(auth()->user()->isSuperAdmin())
                                <th class="text-left px-4 py-3">Tenant</th>
                            @endif
                            <th class="text-left px-4 py-3">Domain</th>
                            <th class="text-left px-4 py-3">Indexed Pages</th>
                            <th class="text-left px-4 py-3">Leads</th>
                            <th class="text-left px-4 py-3">Conversations</th>
                            <th class="text-left px-4 py-3">Status</th>
                            <th class="text-left px-4 py-3">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($websites as $website)
                            <tr class="border-t">
                                <td class="px-4 py-3">
                                    <div class="font-semibold">
                                        {{ $website->name }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        ID: {{ $website->id }}
                                    </div>
                                </td>

                                @if(auth()->user()->isSuperAdmin())
                                    <td class="px-4 py-3">
                                        {{ $website->tenant->name ?? '-' }}
                                    </td>
                                @endif

                                <td class="px-4 py-3 text-sm">
                                    {{ $website->domain }}
                                </td>

                                <td class="px-4 py-3">
                                    {{ $website->knowledge_pages_count }}
                                </td>

                                <td class="px-4 py-3">
                                    {{ $website->leads_count }}
                                </td>

                                <td class="px-4 py-3">
                                    {{ $website->conversations_count }}
                                </td>

                                <td class="px-4 py-3">
                                    @if($website->is_active)
                                        <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-700">Active</span>
                                    @else
                                        <span class="px-2 py-1 text-xs rounded bg-red-100 text-red-700">Inactive</span>
                                    @endif
                                </td>

                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.websites.show', $website) }}" class="text-blue-600">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-6 text-center text-gray-500">
                                    No websites found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $websites->links() }}
            </div>

        </div>
    </div>
</x-app-layout>