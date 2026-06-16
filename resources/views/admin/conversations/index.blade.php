<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Conversations
            </h2>

            <div class="flex items-center gap-2 text-sm">
                <span class="inline-flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-yellow-500"></span>
                    Waiting
                </span>

                <span class="inline-flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-green-500"></span>
                    Live
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white rounded shadow p-6 mb-6">
                <form
                    method="GET"
                    action="{{ route('admin.conversations.index') }}"
                    class="flex flex-wrap gap-4"
                >
                    <select
                        name="status"
                        class="border rounded px-3 py-2"
                    >
                        <option value="">All Statuses</option>

                        <option
                            value="active"
                            @selected(request('status') === 'active')
                        >
                            Active
                        </option>

                        <option
                            value="closed"
                            @selected(request('status') === 'closed')
                        >
                            Closed
                        </option>
                    </select>

                    <select
                        name="mode"
                        class="border rounded px-3 py-2"
                    >
                        <option value="">All Chat Modes</option>

                        <option
                            value="ai"
                            @selected(request('mode') === 'ai')
                        >
                            AI
                        </option>

                        <option
                            value="live_waiting"
                            @selected(request('mode') === 'live_waiting')
                        >
                            Waiting for Agent
                        </option>

                        <option
                            value="live"
                            @selected(request('mode') === 'live')
                        >
                            Live Agent
                        </option>
                    </select>

                    <select
                        name="lead_stage"
                        class="border rounded px-3 py-2"
                    >
                        <option value="">All Lead Stages</option>

                        <option
                            value="discovery"
                            @selected(request('lead_stage') === 'discovery')
                        >
                            Discovery
                        </option>

                        <option
                            value="product_interest_capture"
                            @selected(request('lead_stage') === 'product_interest_capture')
                        >
                            Product Interest
                        </option>

                        <option
                            value="name_capture"
                            @selected(request('lead_stage') === 'name_capture')
                        >
                            Name Capture
                        </option>

                        <option
                            value="email_capture"
                            @selected(request('lead_stage') === 'email_capture')
                        >
                            Email Capture
                        </option>

                        <option
                            value="phone_capture"
                            @selected(request('lead_stage') === 'phone_capture')
                        >
                            Phone Capture
                        </option>

                        <option
                            value="qualified"
                            @selected(request('lead_stage') === 'qualified')
                        >
                            Qualified
                        </option>
                    </select>

                    <button
                        type="submit"
                        class="bg-blue-600 text-white px-4 py-2 rounded"
                    >
                        Filter
                    </button>

                    <a
                        href="{{ route('admin.conversations.index') }}"
                        class="border px-4 py-2 rounded"
                    >
                        Clear
                    </a>
                </form>
            </div>

            <div class="bg-white rounded shadow overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="text-left px-4 py-3">
                                Visitor
                            </th>

                            <th class="text-left px-4 py-3">
                                Website
                            </th>

                            <th class="text-left px-4 py-3">
                                Lead
                            </th>

                            <th class="text-left px-4 py-3">
                                Chat Mode
                            </th>

                            <th class="text-left px-4 py-3">
                                Assigned Agent
                            </th>

                            <th class="text-left px-4 py-3">
                                Messages
                            </th>

                            @if(auth()->user()->isSuperAdmin())
                                <th class="text-left px-4 py-3">
                                    Tenant
                                </th>
                            @endif

                            <th class="text-left px-4 py-3">
                                Updated
                            </th>

                            <th class="text-left px-4 py-3">
                                Action
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($conversations as $conversation)
                            <tr
                                class="border-t
                                    @if($conversation->mode === 'live_waiting')
                                        bg-yellow-50
                                    @elseif($conversation->mode === 'live')
                                        bg-green-50
                                    @endif
                                "
                            >
                                <td class="px-4 py-3 text-sm">
                                    {{ $conversation->visitor_id }}
                                </td>

                                <td class="px-4 py-3">
                                    {{ $conversation->website->name ?? '-' }}
                                </td>

                                <td class="px-4 py-3">
                                    @if($conversation->lead)
                                        {{
                                            $conversation->lead->name
                                            ?? $conversation->lead->email
                                            ?? 'Lead #' . $conversation->lead->id
                                        }}
                                    @else
                                        No lead
                                    @endif
                                </td>

                                <td class="px-4 py-3">
                                    @if($conversation->mode === 'live_waiting')
                                        <span class="inline-flex rounded-full bg-yellow-100 text-yellow-800 px-3 py-1 text-xs font-semibold">
                                            Waiting for Agent
                                        </span>
                                    @elseif($conversation->mode === 'live')
                                        <span class="inline-flex rounded-full bg-green-100 text-green-800 px-3 py-1 text-xs font-semibold">
                                            Live Agent
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-full bg-gray-100 text-gray-700 px-3 py-1 text-xs font-semibold">
                                            AI
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 py-3">
                                    {{ $conversation->assignedAgent?->name ?? '-' }}
                                </td>

                                <td class="px-4 py-3">
                                    {{ $conversation->messages_count }}
                                </td>

                                @if(auth()->user()->isSuperAdmin())
                                    <td class="px-4 py-3">
                                        {{
                                            $conversation
                                                ->website
                                                ?->tenant
                                                ?->name
                                            ?? '-'
                                        }}
                                    </td>
                                @endif

                                <td class="px-4 py-3 text-sm">
                                    {{ $conversation->updated_at->format('Y-m-d H:i') }}
                                </td>

                                <td class="px-4 py-3">
                                    <a
                                        href="{{ route('admin.conversations.show', $conversation) }}"
                                        class="font-medium
                                            @if($conversation->mode === 'live_waiting')
                                                text-yellow-700
                                            @elseif($conversation->mode === 'live')
                                                text-green-700
                                            @else
                                                text-blue-600
                                            @endif
                                        "
                                    >
                                        @if($conversation->mode === 'live_waiting')
                                            Respond
                                        @elseif($conversation->mode === 'live')
                                            Open Chat
                                        @else
                                            View
                                        @endif
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="9"
                                    class="px-4 py-8 text-center text-gray-500"
                                >
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