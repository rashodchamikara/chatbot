<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-semibold tracking-tight text-slate-900">Dashboard</h1>
                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ auth()->user()->isSuperAdmin() ? 'bg-violet-50 text-violet-700 ring-violet-200' : 'bg-blue-50 text-blue-700 ring-blue-200' }}">
                        {{ auth()->user()->isSuperAdmin() ? 'System Admin' : 'Workspace' }}
                    </span>
                </div>
                <p class="mt-1 text-sm text-slate-500">Manage AI conversations across WhatsApp, websites and future channels.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if(Route::has('admin.channels.whatsapp.create'))
                    <a href="{{ route('admin.channels.whatsapp.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8M8 14h5m-9 5 2.4-2.4A8 8 0 1 1 20 11a8 8 0 0 1-8 8H4Z"/></svg>
                        Connect WhatsApp
                    </a>
                @endif
                @if(Route::has('admin.conversations.index'))
                    <a href="{{ route('admin.conversations.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">Open Inbox</a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="min-h-screen bg-slate-50/70 py-8">
        <div class="mx-auto max-w-7xl space-y-7 px-4 sm:px-6 lg:px-8">
            @foreach(['success' => 'emerald', 'warning' => 'amber', 'error' => 'rose'] as $flash => $tone)
                @if(session($flash))
                    <div class="rounded-2xl border px-4 py-3 text-sm font-medium {{ $tone === 'emerald' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : ($tone === 'amber' ? 'border-amber-200 bg-amber-50 text-amber-800' : 'border-rose-200 bg-rose-50 text-rose-800') }}">{{ session($flash) }}</div>
                @endif
            @endforeach

            <section class="relative overflow-hidden rounded-3xl bg-slate-950 px-6 py-8 text-white shadow-xl sm:px-8">
                <div class="pointer-events-none absolute -right-16 -top-24 h-64 w-64 rounded-full bg-blue-500/20 blur-3xl"></div>
                <div class="relative flex flex-col gap-7 lg:flex-row lg:items-center lg:justify-between">
                    <div class="max-w-2xl">
                        <p class="text-sm font-semibold text-blue-300">Omnichannel AI workspace</p>
                        <h2 class="mt-2 text-2xl font-semibold tracking-tight sm:text-3xl">Welcome back, {{ auth()->user()->name }}</h2>
                        <p class="mt-3 text-sm leading-6 text-slate-300 sm:text-base">A website is now one channel, not the foundation of the account. Connect WhatsApp directly, train an AI agent with business knowledge, and manage every conversation in the same inbox.</p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                            <p class="text-xs uppercase tracking-wide text-slate-400">Active channels</p>
                            <p class="mt-1 text-2xl font-semibold">{{ number_format($activeChannels) }}</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                            <p class="text-xs uppercase tracking-wide text-slate-400">Knowledge</p>
                            <p class="mt-1 text-2xl font-semibold">{{ number_format($totalKnowledgeItems) }}</p>
                        </div>
                    </div>
                </div>
            </section>

            @if(($setup['percent'] ?? 100) < 100)
                <section class="rounded-3xl border border-blue-200 bg-gradient-to-br from-blue-50 to-white p-5 shadow-sm sm:p-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-blue-600">Workspace setup</p>
                            <h2 class="mt-1 text-lg font-semibold text-slate-900">Get your AI ready for real customers</h2>
                            <p class="mt-1 text-sm text-slate-500">{{ $setup['completed'] }} of 3 core steps complete.</p>
                        </div>
                        <div class="w-full sm:w-56">
                            <div class="h-2 overflow-hidden rounded-full bg-blue-100"><div class="h-full rounded-full bg-blue-600" style="width: {{ $setup['percent'] }}%"></div></div>
                            <p class="mt-1 text-right text-xs font-semibold text-blue-700">{{ $setup['percent'] }}%</p>
                        </div>
                    </div>
                    <div class="mt-5 grid gap-3 md:grid-cols-3">
                        <a href="{{ Route::has('admin.channels.index') ? route('admin.channels.index') : '#' }}" class="rounded-2xl border p-4 transition hover:-translate-y-0.5 hover:shadow-sm {{ $setup['has_channel'] ? 'border-emerald-200 bg-emerald-50/70' : 'border-slate-200 bg-white' }}">
                            <div class="flex items-center justify-between"><span class="text-sm font-semibold text-slate-900">1. Connect a channel</span><span class="text-sm {{ $setup['has_channel'] ? 'text-emerald-600' : 'text-slate-300' }}">{{ $setup['has_channel'] ? '✓' : '→' }}</span></div>
                            <p class="mt-1 text-xs leading-5 text-slate-500">WhatsApp or website. Either can be your first channel.</p>
                        </a>
                        <a href="{{ Route::has('admin.knowledge-hub.index') ? route('admin.knowledge-hub.index') : '#' }}" class="rounded-2xl border p-4 transition hover:-translate-y-0.5 hover:shadow-sm {{ $setup['has_knowledge'] ? 'border-emerald-200 bg-emerald-50/70' : 'border-slate-200 bg-white' }}">
                            <div class="flex items-center justify-between"><span class="text-sm font-semibold text-slate-900">2. Train the AI</span><span class="text-sm {{ $setup['has_knowledge'] ? 'text-emerald-600' : 'text-slate-300' }}">{{ $setup['has_knowledge'] ? '✓' : '→' }}</span></div>
                            <p class="mt-1 text-xs leading-5 text-slate-500">Add FAQs, services, prices, policies or upload business documents.</p>
                        </a>
                        <a href="{{ Route::has('admin.conversations.index') ? route('admin.conversations.index') : '#' }}" class="rounded-2xl border p-4 transition hover:-translate-y-0.5 hover:shadow-sm {{ $setup['has_conversation'] ? 'border-emerald-200 bg-emerald-50/70' : 'border-slate-200 bg-white' }}">
                            <div class="flex items-center justify-between"><span class="text-sm font-semibold text-slate-900">3. Start a conversation</span><span class="text-sm {{ $setup['has_conversation'] ? 'text-emerald-600' : 'text-slate-300' }}">{{ $setup['has_conversation'] ? '✓' : '→' }}</span></div>
                            <p class="mt-1 text-xs leading-5 text-slate-500">Test the AI, then let a human take over when needed.</p>
                        </a>
                    </div>
                </section>
            @endif

            <section class="grid gap-4 sm:grid-cols-2 {{ auth()->user()->isSuperAdmin() ? 'xl:grid-cols-6' : 'xl:grid-cols-5' }}">
                @if(auth()->user()->isSuperAdmin())
                    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm font-medium text-slate-500">Tenants</p><p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format($totalTenants) }}</p></article>
                @endif
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm font-medium text-slate-500">Channels</p><p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format($totalChannels) }}</p><p class="mt-1 text-xs text-emerald-600">{{ number_format($activeChannels) }} active</p></article>
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm font-medium text-slate-500">Conversations</p><p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format($totalConversations) }}</p></article>
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm font-medium text-slate-500">Knowledge</p><p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format($totalKnowledgeItems) }}</p></article>
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm font-medium text-slate-500">Leads</p><p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format($totalLeads) }}</p><p class="mt-1 text-xs text-slate-500">{{ number_format($qualifiedLeads) }} qualified</p></article>
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm font-medium text-slate-500">Websites</p><p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format($totalWebsites) }}</p><p class="mt-1 text-xs text-slate-400">Optional channel</p></article>
            </section>

            <div class="grid gap-6 xl:grid-cols-3">
                <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm xl:col-span-2">
                    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 sm:px-6">
                        <div><h2 class="font-semibold text-slate-900">Recent conversations</h2><p class="mt-0.5 text-sm text-slate-500">Across all connected channels.</p></div>
                        @if(Route::has('admin.conversations.index'))<a href="{{ route('admin.conversations.index') }}" class="text-sm font-semibold text-blue-600 hover:text-blue-700">View inbox</a>@endif
                    </div>
                    <div class="divide-y divide-slate-100">
                        @forelse($recentConversations as $conversation)
                            @php
                                $contactName = $conversation->contact?->name ?: $conversation->contact?->phone ?: $conversation->visitor_id ?: 'Customer';
                                $channelType = $conversation->channelConnection?->type ?: ($conversation->website ? 'website' : 'channel');
                                $channelName = $conversation->channelConnection?->name ?: $conversation->website?->name ?: ucfirst($channelType);
                            @endphp
                            <a href="{{ route('admin.conversations.show', $conversation) }}" class="flex items-center gap-4 px-5 py-4 transition hover:bg-slate-50 sm:px-6">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $channelType === 'whatsapp' ? 'bg-emerald-50 text-emerald-700' : 'bg-blue-50 text-blue-700' }}"><span class="text-xs font-bold">{{ strtoupper(substr($channelType, 0, 2)) }}</span></div>
                                <div class="min-w-0 flex-1"><p class="truncate text-sm font-semibold text-slate-900">{{ $contactName }}</p><p class="mt-0.5 truncate text-xs text-slate-500">{{ ucfirst($channelType) }} · {{ $channelName }}</p></div>
                                <div class="text-right"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ $conversation->mode === 'live_waiting' ? 'Waiting' : ($conversation->mode === 'live' ? 'Human' : 'AI') }}</span><p class="mt-1 text-[11px] text-slate-400">{{ $conversation->updated_at?->diffForHumans() }}</p></div>
                            </a>
                        @empty
                            <div class="px-6 py-12 text-center"><p class="font-semibold text-slate-900">No conversations yet</p><p class="mt-1 text-sm text-slate-500">Connect a channel and send a test message.</p></div>
                        @endforelse
                    </div>
                </section>

                <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4"><h2 class="font-semibold text-slate-900">Recent leads</h2><p class="mt-0.5 text-sm text-slate-500">Latest captured contacts.</p></div>
                    <div class="divide-y divide-slate-100">
                        @forelse($recentLeads as $lead)
                            <div class="px-5 py-4"><div class="flex items-start justify-between gap-3"><div class="min-w-0"><p class="truncate text-sm font-semibold text-slate-900">{{ $lead->name ?: $lead->email ?: $lead->phone ?: 'New lead' }}</p><p class="mt-1 truncate text-xs text-slate-500">{{ $lead->website?->name ?: 'Omnichannel lead' }}</p></div><span class="rounded-full bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-600">{{ ucfirst($lead->status ?: 'new') }}</span></div></div>
                        @empty
                            <div class="px-5 py-10 text-center text-sm text-slate-500">No leads captured yet.</div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
