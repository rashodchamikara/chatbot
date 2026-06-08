<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Conversations
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white rounded shadow p-6 mb-6">
                <form method="GET" action="{{ route('admin.conversations.index') }}" class="flex gap-4">

                    <select name="status" class="border rounded px-3 py-2">
                        <option value="">All Statuses</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="closed" @selected(request('status') === 'closed')>Closed</option>
                    </select>

                    <select name="lead_stage" class="border rounded px-3 py-2">
                        <option value="">All Lead Stages</option>
                        <option value="discovery" @selected(request('lead_stage') === 'discovery')>Discovery</option>
                        <option value="product_interest_capture" @selected(request('lead_stage') === 'product_interest_capture')>Product Interest</option>
                        <option value="name_capture" @selected(request('lead_stage') === 'name_capture')>Name Capture</option>
                        <option value="email_capture" @selected(request('lead_stage') === 'email_capture')>Email Capture</option>
                        <option value="phone_capture" @selected(request('lead_stage') === 'phone_capture')>Phone Capture</option>
                        <option value="qualified" @selected(request('lead_stage') === 'qualified')>Qualified</option>
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
                            <th class="text-left px-4 py-3">Visitor</th>
                            <th class="text-left px-4 py-3">Website</th>
                            <th class="text-left px-4 py-3">Lead</th>
                            <th class="text-left px-4 py-3">Stage</th>
                            <th class="text-left px-4 py-3">Status</th>
                            @if(auth()->user()->isSuperAdmin())
                            <th class="text-left px-4 py-3">Tenant</th>
                            @endif
                            <th class="text-left px-4 py-3">Created</th>
                            <th class="text-left px-4 py-3">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($conversations as $conversation)
                            <tr class="border-t">
                                <td class="px-4 py-3 text-sm">
                                    {{ $conversation->visitor_id }}
                                </td>

                                <td class="px-4 py-3">
                                    {{ $conversation->website->name ?? '-' }}
                                </td>

                                <td class="px-4 py-3">
                                    @if($conversation->lead)
                                        {{ $conversation->lead->name ?? $conversation->lead->email ?? 'Lead #' . $conversation->lead->id }}
                                    @else
                                        No lead
                                    @endif
                                </td>

                                <td class="px-4 py-3">
                                    {{ $conversation->lead_stage }}
                                </td>

                                <td class="px-4 py-3">
                                    {{ ucfirst($conversation->status) }}
                                </td>
                                @if(auth()->user()->isSuperAdmin())
                                <td class="px-4 py-3">
                                    Tenant: {{ $conversation->website->tenant->name ?? '-' }}
                                </td>
                                @endif

                                <td class="px-4 py-3 text-sm">
                                    {{ $conversation->created_at->format('Y-m-d H:i') }}
                                </td>

                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.conversations.show', $conversation) }}" class="text-blue-600">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-6 text-center text-gray-500">
                                    No conversations found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

            </div>

            <div class="mt-6">
                {{ $conversations->links() }}
            </div>

        </div>
    </div>
</x-app-layout>