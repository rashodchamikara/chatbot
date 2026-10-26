<x-app-layout>
    @php
    extract(require resource_path('views/workspace/context.php'), EXTR_OVERWRITE);
@endphp
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-semibold tracking-tight text-slate-900">Channels</h1>
                    <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700 ring-1 ring-inset ring-blue-200">
                        Omnichannel
                    </span>
                </div>
                <p class="mt-1 text-sm text-slate-500">
                    Website chat, WhatsApp, and future messaging channels in one workspace.
                </p>
            </div>

            @if($workspaceManager)<a class="cn-button" href="#channel-catalog">Add a channel</a>@endif
        </div>
    </x-slot>

    <div class="min-h-screen bg-slate-50/70 py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('warning'))
                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 shadow-sm">
                    {{ session('warning') }}
                </div>
            @endif

            <section class="cn-panel"><h2>Your channel connections</h2><p>{{ number_format($summary['active']) }} active · {{ number_format($summary['total']) }} total connections. Manage connection health and the AI assistant assigned to each channel.</p></section>

            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <form method="GET" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @if(auth()->user()->isSuperAdmin())
                        <select aria-label="Tenant" name="tenant_id" class="rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All tenants</option>
                            @foreach($tenants as $tenant)
                                <option value="{{ $tenant->id }}" @selected((string) request('tenant_id') === (string) $tenant->id)>
                                    {{ $tenant->company_name ?: $tenant->name }}
                                </option>
                            @endforeach
                        </select>
                    @endif

                    <select aria-label="Channel type" name="type" class="rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All channel types</option>
                            @foreach($workspaceCatalog as $catalogType => $catalogItem)
                                @if($catalogItem['route'] && Route::has($catalogItem['route']))
                                    <option value="{{ $catalogType }}" @selected(request('type') === $catalogType)>{{ $catalogItem['label'] }}</option>
                                @endif
                            @endforeach
                    </select>

                    <select aria-label="Connection status" name="status" class="rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All statuses</option>
                        @foreach(['active', 'pending', 'error', 'suspended', 'disconnected'] as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>

                    <div class="flex gap-2">
                        <button class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Filter</button>
                        <a href="{{ route('admin.channels.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">Reset</a>
                    </div>
                </form>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                    <h2 class="font-semibold text-slate-900">Connected channels</h2>
                    <p class="mt-1 text-sm text-slate-500">Each channel is assigned to an AI agent and can share that agent's trained knowledge.</p>
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse($channels as $channel)
                        @php
                            $statusClasses = match($channel->status) {
                                'active' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                'pending' => 'bg-amber-50 text-amber-700 ring-amber-200',
                                'error' => 'bg-red-50 text-red-700 ring-red-200',
                                'suspended' => 'bg-violet-50 text-violet-700 ring-violet-200',
                                default => 'bg-slate-100 text-slate-600 ring-slate-200',
                            };
                        @endphp

                        <div class="flex flex-col gap-4 px-5 py-5 sm:px-6 lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex min-w-0 gap-4">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl {{ $channel->type === 'whatsapp' ? 'bg-emerald-50 text-emerald-600' : 'bg-blue-50 text-blue-600' }}">
                                    <span class="text-xs font-bold" aria-hidden="true">{{ $workspaceCatalog[$channel->type]['mark'] ?? strtoupper(substr($channel->type, 0, 2)) }}</span>
                                </div>

                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="truncate font-semibold text-slate-900">{{ $channel->name }}</h3>
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $statusClasses }}">
                                            {{ ucfirst($channel->status) }}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-sm text-slate-500">
                                        {{ $workspaceCatalog[$channel->type]['label'] ?? ucfirst(str_replace('_', ' ', $channel->type)) }}
                                        · AI assistant: {{ $channel->aiAgent?->name ?? 'Not assigned' }}
                                        @if(auth()->user()->isSuperAdmin() && $channel->tenant)
                                            · {{ $channel->tenant->company_name ?: $channel->tenant->name }}
                                        @endif
                                    </p>
                                    <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-400">
                                        <span>{{ number_format($channel->conversations_count) }} conversations</span>
                                        @if($channel->last_webhook_at)
                                            <span>Last inbound {{ $channel->last_webhook_at->diffForHumans() }}</span>
                                        @endif
                                        @if($channel->type === 'whatsapp' && data_get($channel->settings, 'display_phone_number'))
                                            <span>{{ data_get($channel->settings, 'display_phone_number') }}</span>
                                        @endif
                                    </div>
                                    @if($workspaceManager && $channel->last_error)
                                        <p class="mt-2 max-w-2xl text-xs text-red-600">{{ $channel->last_error }}</p>
                                    @endif
                                </div>
                            </div>

                            <div class="flex shrink-0 gap-2">
                                @if($workspaceManager && $channel->type === 'whatsapp')
                                    <a href="{{ route('admin.channels.whatsapp.edit', $channel) }}"
                                       class="rounded-xl border border-slate-200 px-3.5 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                        Manage
                                    </a>
                                @elseif($workspaceManager && $channel->type === 'website' && $channel->website && Route::has('admin.websites.show'))
                                    <a href="{{ route('admin.websites.show', $channel->website) }}"
                                       class="rounded-xl border border-slate-200 px-3.5 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                        Manage
                                    </a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-14 text-center">
                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
                                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5v14"/></svg>
                            </div>
                            <h3 class="mt-4 font-semibold text-slate-900">No channels found</h3>
                            <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">Try clearing the filters. If you are setting up your workspace, connect your first channel below.</p>
                            <div class="cn-quicklinks" style="justify-content:center"><a class="cn-muted-link" href="{{ route('admin.channels.index') }}">Clear filters</a>@if($workspaceManager)<a class="cn-button" href="#channel-catalog">Choose a channel</a>@endif</div>
                        </div>
                    @endforelse
                </div>

                @if($channels->hasPages())
                    <div class="border-t border-slate-100 px-5 py-4">{{ $channels->withQueryString()->links() }}</div>
                @endif
            </section>
            @if($workspaceManager)
                @include('workspace.channel-catalog')
            @endif
        </div>
    </div>
</x-app-layout>
