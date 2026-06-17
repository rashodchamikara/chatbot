<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
                    Conversations
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Monitor AI conversations, live-agent requests, and qualified sales activity.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2 text-xs font-medium text-slate-600">
                <span class="inline-flex items-center gap-2 rounded-full bg-amber-50 px-3 py-1.5 text-amber-700 ring-1 ring-amber-200">
                    <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                    Waiting for agent
                </span>

                <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1.5 text-emerald-700 ring-1 ring-emerald-200">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    Live conversation
                </span>
            </div>
        </div>
    </x-slot>

    <div class="min-h-screen bg-slate-50 py-8">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <form
                    method="GET"
                    action="{{ route('admin.conversations.index') }}"
                    class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-5"
                >
                    <div>
                        <label for="status" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Status
                        </label>

                        <select
                            id="status"
                            name="status"
                            class="h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="">All statuses</option>
                            <option value="active" @selected(request('status') === 'active')>Active</option>
                            <option value="closed" @selected(request('status') === 'closed')>Closed</option>
                        </select>
                    </div>

                    <div>
                        <label for="mode" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Chat mode
                        </label>

                        <select
                            id="mode"
                            name="mode"
                            class="h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="">All modes</option>
                            <option value="ai" @selected(request('mode') === 'ai')>AI</option>
                            <option value="live_waiting" @selected(request('mode') === 'live_waiting')>Waiting for agent</option>
                            <option value="live" @selected(request('mode') === 'live')>Live agent</option>
                        </select>
                    </div>

                    <div>
                        <label for="lead_stage" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Lead stage
                        </label>

                        <select
                            id="lead_stage"
                            name="lead_stage"
                            class="h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="">All lead stages</option>
                            <option value="discovery" @selected(request('lead_stage') === 'discovery')>Discovery</option>
                            <option value="product_interest_capture" @selected(request('lead_stage') === 'product_interest_capture')>Product interest</option>
                            <option value="name_capture" @selected(request('lead_stage') === 'name_capture')>Name capture</option>
                            <option value="email_capture" @selected(request('lead_stage') === 'email_capture')>Email capture</option>
                            <option value="phone_capture" @selected(request('lead_stage') === 'phone_capture')>Phone capture</option>
                            <option value="qualified" @selected(request('lead_stage') === 'qualified')>Qualified</option>
                        </select>
                    </div>

                    <div class="md:col-span-2 xl:col-span-1 xl:flex xl:items-end">
                        <button
                            type="submit"
                            class="inline-flex h-11 w-full items-center justify-center rounded-xl bg-slate-900 px-5 text-sm font-semibold text-white transition hover:bg-slate-800"
                        >
                            Apply filters
                        </button>
                    </div>

                    <div class="md:col-span-2 xl:col-span-1 xl:flex xl:items-end">
                        <a
                            href="{{ route('admin.conversations.index') }}"
                            class="inline-flex h-11 w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
                        >
                            Clear filters
                        </a>
                    </div>
                </form>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="hidden overflow-x-auto lg:block">
                    <table class="w-full table-auto border-collapse">
                        <thead class="bg-slate-50">
                            <tr class="border-b border-slate-200">
                                <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Visitor</th>
                                <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Website</th>
                                <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Lead</th>
                                <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Mode</th>
                                <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Agent</th>
                                <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Messages</th>

                                @if(auth()->user()->isSuperAdmin())
                                    <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Tenant</th>
                                @endif

                                <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Updated</th>
                                <th class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse($conversations as $conversation)
                                @php
                                    $leadLabel = $conversation->lead?->name
                                        ?? $conversation->lead?->email
                                        ?? ($conversation->lead ? 'Lead #' . $conversation->lead->id : 'No lead');

                                    $modeClasses = match ($conversation->mode) {
                                        'live_waiting' => 'bg-amber-50 text-amber-700 ring-amber-200',
                                        'live' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                        default => 'bg-blue-50 text-blue-700 ring-blue-200',
                                    };

                                    $modeLabel = match ($conversation->mode) {
                                        'live_waiting' => 'Waiting',
                                        'live' => 'Live agent',
                                        default => 'AI',
                                    };
                                @endphp

                                <tr class="transition hover:bg-slate-50">
                                    <td class="px-5 py-4 align-middle">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-600">
                                                {{ strtoupper(substr($conversation->visitor_id, 0, 2)) }}
                                            </div>

                                            <div class="min-w-0">
                                                <p class="max-w-48 truncate text-sm font-semibold text-slate-900">
                                                    {{ $conversation->visitor_id }}
                                                </p>

                                                <p class="mt-1 text-xs text-slate-500">
                                                    #{{ $conversation->id }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-5 py-4 align-middle text-sm text-slate-700">
                                        {{ $conversation->website->name ?? '—' }}
                                    </td>

                                    <td class="px-5 py-4 align-middle">
                                        <p class="max-w-48 truncate text-sm font-medium {{ $conversation->lead ? 'text-slate-800' : 'text-slate-400' }}">
                                            {{ $leadLabel }}
                                        </p>
                                    </td>

                                    <td class="px-5 py-4 align-middle">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $modeClasses }}">
                                            {{ $modeLabel }}
                                        </span>
                                    </td>

                                    <td class="px-5 py-4 align-middle text-sm text-slate-600">
                                        {{ $conversation->assignedAgent?->name ?? '—' }}
                                    </td>

                                    <td class="px-5 py-4 align-middle">
                                        <span class="inline-flex min-w-9 items-center justify-center rounded-lg bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">
                                            {{ $conversation->messages_count }}
                                        </span>
                                    </td>

                                    @if(auth()->user()->isSuperAdmin())
                                        <td class="px-5 py-4 align-middle text-sm text-slate-600">
                                            {{ $conversation->website?->tenant?->name ?? '—' }}
                                        </td>
                                    @endif

                                    <td class="px-5 py-4 align-middle">
                                        <p class="text-sm text-slate-700">{{ $conversation->updated_at->format('d M Y') }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ $conversation->updated_at->format('H:i') }}</p>
                                    </td>

                                    <td class="px-5 py-4 text-right align-middle">
                                        <a
                                            href="{{ route('admin.conversations.show', $conversation) }}"
                                            class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700"
                                        >
                                            {{ $conversation->mode === 'live_waiting' ? 'Respond' : ($conversation->mode === 'live' ? 'Open chat' : 'View') }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="{{ auth()->user()->isSuperAdmin() ? 9 : 8 }}"
                                        class="px-6 py-16 text-center"
                                    >
                                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8M8 14h5m-9 5 2.4-2.4A8 8 0 1 1 20 11a8 8 0 0 1-8 8H4Z"/>
                                            </svg>
                                        </div>

                                        <h3 class="mt-4 text-sm font-semibold text-slate-900">No conversations found</h3>
                                        <p class="mt-1 text-sm text-slate-500">Try changing the filters or wait for a new visitor conversation.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="divide-y divide-slate-100 lg:hidden">
                    @forelse($conversations as $conversation)
                        @php
                            $modeLabel = match ($conversation->mode) {
                                'live_waiting' => 'Waiting',
                                'live' => 'Live agent',
                                default => 'AI',
                            };

                            $modeClasses = match ($conversation->mode) {
                                'live_waiting' => 'bg-amber-50 text-amber-700',
                                'live' => 'bg-emerald-50 text-emerald-700',
                                default => 'bg-blue-50 text-blue-700',
                            };
                        @endphp

                        <article class="p-5">
                            <div class="flex items-start gap-3">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-600">
                                    {{ strtoupper(substr($conversation->visitor_id, 0, 2)) }}
                                </div>

                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-semibold text-slate-900">{{ $conversation->visitor_id }}</p>
                                    <p class="mt-1 truncate text-sm text-slate-500">{{ $conversation->website->name ?? 'Unknown website' }}</p>
                                </div>

                                <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold {{ $modeClasses }}">
                                    {{ $modeLabel }}
                                </span>
                            </div>

                            <div class="mt-4 grid grid-cols-3 gap-2">
                                <div class="rounded-xl bg-slate-50 p-3 text-center">
                                    <p class="font-semibold text-slate-900">{{ $conversation->messages_count }}</p>
                                    <p class="mt-1 text-[11px] text-slate-500">Messages</p>
                                </div>

                                <div class="rounded-xl bg-slate-50 p-3 text-center">
                                    <p class="truncate font-semibold text-slate-900">{{ $conversation->assignedAgent?->name ?? '—' }}</p>
                                    <p class="mt-1 text-[11px] text-slate-500">Agent</p>
                                </div>

                                <div class="rounded-xl bg-slate-50 p-3 text-center">
                                    <p class="font-semibold text-slate-900">{{ $conversation->updated_at->format('H:i') }}</p>
                                    <p class="mt-1 text-[11px] text-slate-500">Updated</p>
                                </div>
                            </div>

                            <a
                                href="{{ route('admin.conversations.show', $conversation) }}"
                                class="mt-4 inline-flex h-10 w-full items-center justify-center rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                            >
                                {{ $conversation->mode === 'live_waiting' ? 'Respond now' : 'Open conversation' }}
                            </a>
                        </article>
                    @empty
                        <div class="px-6 py-14 text-center text-sm text-slate-500">
                            No conversations found.
                        </div>
                    @endforelse
                </div>
            </section>

            <div class="mt-6">
                {{ $conversations->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
