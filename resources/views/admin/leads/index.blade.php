<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Leads
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white rounded shadow p-6 mb-6">

                <form method="GET" action="{{ route('admin.leads.index') }}" class="flex gap-4">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search name, email, phone, product..."
                        class="border rounded px-3 py-2 w-full"
                    >

                    <select name="status" class="border rounded px-3 py-2">
                        <option value="">All Statuses</option>
                        <option value="new" @selected(request('status') === 'new')>New</option>
                        <option value="qualified" @selected(request('status') === 'qualified')>Qualified</option>
                        <option value="contacted" @selected(request('status') === 'contacted')>Contacted</option>
                        <option value="converted" @selected(request('status') === 'converted')>Converted</option>
                        <option value="closed" @selected(request('status') === 'closed')>Closed</option>
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
                            <th class="text-left px-4 py-3">Lead</th>
                            <th class="text-left px-4 py-3">Contact</th>
                            <th class="text-left px-4 py-3">Interest</th>
                            <th class="text-left px-4 py-3">Score</th>
                            <th class="text-left px-4 py-3">Status</th>
                            @if(auth()->user()->isSuperAdmin())
                            <th class="text-left px-4 py-3">Tenant</th>
                            @endif
                            <th class="text-left px-4 py-3">Created</th>
                            <th class="text-left px-4 py-3">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($leads as $lead)
                            <tr class="border-t">
                                <td class="px-4 py-3">
                                    <div class="font-semibold">
                                        {{ $lead->name ?? 'Unknown' }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ $lead->website->name ?? '-' }}
                                    </div>
                                </td>

                                <td class="px-4 py-3 text-sm">
                                    <div>{{ $lead->email ?? 'No email' }}</div>
                                    <div>{{ $lead->phone ?? 'No phone' }}</div>
                                </td>

                                <td class="px-4 py-3">
                                    {{ $lead->product_interest ?? '-' }}
                                </td>

                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 rounded bg-gray-100">
                                        {{ $lead->lead_score }}
                                    </span>
                                </td>

                                <td class="px-4 py-3">
                                    {{ ucfirst($lead->status) }}
                                </td>
                                @if(auth()->user()->isSuperAdmin())
                                <td class="px-4 py-3">
                                    {{ $lead->tenant->name ?? '-' }}
                                </td>
                                @endif

                                <td class="px-4 py-3 text-sm">
                                    {{ $lead->created_at->format('Y-m-d H:i') }}
                                </td>

                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.leads.show', $lead) }}" class="text-blue-600">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-6 text-center text-gray-500">
                                    No leads found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

            </div>

            <div class="mt-6">
                {{ $leads->links() }}
            </div>

        </div>
    </div>
</x-app-layout>