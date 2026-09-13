<x-app-layout>
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
                    Connect the places where customers contact you. A website is optional.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                @if(Route::has('admin.websites.create'))
                    <a href="{{ route('admin.websites.create') }}"
                       class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18"/>
                        </svg>
                        Add website
                    </a>
                @endif

                <a href="{{ route('admin.channels.whatsapp.create') }}"
                   class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 11.5a8.2 8.2 0 0 1-12.5 7L3 20l1.5-4.2A8.2 8.2 0 1 1 20 11.5Z"/>
                        <path stroke-linecap="round" d="M8.5 9.2c.8 2.3 2 3.5 4.3 4.3"/>
                    </svg>
                    Connect WhatsApp
                </a>
            </div>
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

            <section class="overflow-hidden rounded-3xl bg-gradient-to-br from-slate-950 via-slate-900 to-emerald-950 p-6 text-white shadow-xl sm:p-8">
                <div class="grid gap-6 lg:grid-cols-[1.4fr_.6fr] lg:items-center">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.22em] text-emerald-300">Channel-first workspace</p>
                        <h2 class="mt-3 text-2xl font-semibold tracking-tight sm:text-3xl">
                            Sell and support customers even if you do not have a website.
                        </h2>
                        <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300 sm:text-base">
                            Connect WhatsApp directly to an AI agent, train it with business knowledge, and move any conversation to a human agent when needed. Website chat is simply another channel.
                        </p>
                        <div class="mt-5 flex flex-wrap gap-3">
                            <a href="{{ route('admin.channels.whatsapp.create') }}"
                               class="inline-flex items-center rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-900 transition hover:bg-slate-100">
                                Connect WhatsApp
                            </a>
                            @if(Route::has('admin.knowledge-hub.index'))
                                <a href="{{ route('admin.knowledge-hub.index') }}"
                                   class="inline-flex items-center rounded-xl border border-white/15 bg-white/5 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-white/10">
                                    Train your AI
                                </a>
                            @endif
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-400">Active</p>
                            <p class="mt-2 text-3xl font-bold">{{ number_format($summary['active']) }}</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-400">Total</p>
                            <p class="mt-2 text-3xl font-bold">{{ number_format($summary['total']) }}</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-400">WhatsApp</p>
                            <p class="mt-2 text-2xl font-bold">{{ number_format($summary['whatsapp']) }}</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-400">Websites</p>
                            <p class="mt-2 text-2xl font-bold">{{ number_format($summary['website']) }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <form method="GET" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @if(auth()->user()->isSuperAdmin())
                        <select name="tenant_id" class="rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All tenants</option>
                            @foreach($tenants as $tenant)
                                <option value="{{ $tenant->id }}" @selected((string) request('tenant_id') === (string) $tenant->id)>
                                    {{ $tenant->company_name ?: $tenant->name }}
                                </option>
                            @endforeach
                        </select>
                    @endif

                    <select name="type" class="rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All channel types</option>
                        <option value="website" @selected(request('type') === 'website')>Website</option>
                        <option value="whatsapp" @selected(request('type') === 'whatsapp')>WhatsApp</option>
                    </select>

                    <select name="status" class="rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
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
                                    @if($channel->type === 'whatsapp')
                                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 11.5a8.2 8.2 0 0 1-12.5 7L3 20l1.5-4.2A8.2 8.2 0 1 1 20 11.5Z"/><path d="M8.5 9.2c.8 2.3 2 3.5 4.3 4.3"/></svg>
                                    @else
                                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18"/></svg>
                                    @endif
                                </div>

                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="truncate font-semibold text-slate-900">{{ $channel->name }}</h3>
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $statusClasses }}">
                                            {{ ucfirst($channel->status) }}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-sm text-slate-500">
                                        {{ ucfirst($channel->type) }}
                                        · AI: {{ $channel->aiAgent?->name ?? 'Not assigned' }}
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
                                    @if($channel->last_error)
                                        <p class="mt-2 max-w-2xl text-xs text-red-600">{{ $channel->last_error }}</p>
                                    @endif
                                </div>
                            </div>

                            <div class="flex shrink-0 gap-2">
                                @if($channel->type === 'whatsapp')
                                    <a href="{{ route('admin.channels.whatsapp.edit', $channel) }}"
                                       class="rounded-xl border border-slate-200 px-3.5 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                        Manage
                                    </a>
                                @elseif($channel->website && Route::has('admin.websites.show'))
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
                            <h3 class="mt-4 font-semibold text-slate-900">Connect your first customer channel</h3>
                            <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">Start with WhatsApp if you do not have a website. You can add a website later without changing the AI agent.</p>
                            <a href="{{ route('admin.channels.whatsapp.create') }}" class="mt-5 inline-flex rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Connect WhatsApp</a>
                        </div>
                    @endforelse
                </div>

                @if($channels->hasPages())
                    <div class="border-t border-slate-100 px-5 py-4">{{ $channels->links() }}</div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
