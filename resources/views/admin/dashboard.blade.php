<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-semibold tracking-tight text-slate-900">
                        Dashboard
                    </h1>

                    @if(auth()->user()->isSuperAdmin())
                        <span class="inline-flex items-center rounded-full bg-violet-50 px-2.5 py-1 text-xs font-semibold text-violet-700 ring-1 ring-inset ring-violet-200">
                            System Admin
                        </span>
                    @else
                        <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700 ring-1 ring-inset ring-blue-200">
                            Tenant
                        </span>
                    @endif
                </div>

                <p class="mt-1 text-sm text-slate-500">
                    Monitor websites, leads, conversations, and sales activity.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if(Route::has('admin.websites.create'))
                    <a
                        href="{{ route('admin.websites.create') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                    >
                        <svg
                            class="h-4 w-4"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            aria-hidden="true"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
                        </svg>

                        Add Website
                    </a>
                @endif

                @if(Route::has('admin.conversations.index'))
                    <a
                        href="{{ route('admin.conversations.index') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-blue-600/20 transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                    >
                        <svg
                            class="h-4 w-4"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            aria-hidden="true"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M8 10h8M8 14h5m-9 5 2.4-2.4A8 8 0 1 1 20 11a8 8 0 0 1-8 8H4Z"
                            />
                        </svg>

                        Open Conversations
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="min-h-screen bg-slate-50/70 py-8">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">

            @if(session('success'))
                <div
                    class="flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-4 text-emerald-800 shadow-sm"
                    role="alert"
                >
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-100">
                        <svg
                            class="h-4 w-4"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.5"
                            aria-hidden="true"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6"/>
                        </svg>
                    </div>

                    <div class="min-w-0">
                        <p class="text-sm font-semibold">
                            Success
                        </p>

                        <p class="mt-0.5 text-sm text-emerald-700">
                            {{ session('success') }}
                        </p>
                    </div>
                </div>
            @endif

            <section
                class="overflow-hidden rounded-3xl bg-gradient-to-br from-slate-950 via-slate-900 to-blue-950 px-6 py-7 text-white shadow-xl shadow-slate-900/10 sm:px-8 sm:py-9"
            >
                <div class="relative">
                    <div
                        class="pointer-events-none absolute -right-20 -top-24 h-64 w-64 rounded-full bg-blue-500/20 blur-3xl"
                    ></div>

                    <div
                        class="pointer-events-none absolute -bottom-28 left-1/3 h-56 w-56 rounded-full bg-violet-500/10 blur-3xl"
                    ></div>

                    <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                        <div class="max-w-2xl">
                            <p class="text-sm font-medium text-blue-200">
                                Welcome back
                            </p>

                            <h2 class="mt-2 text-2xl font-semibold tracking-tight sm:text-3xl">
                                {{ auth()->user()->name }}
                            </h2>

                            <p class="mt-3 max-w-xl text-sm leading-6 text-slate-300 sm:text-base">
                                Your AI sales operation is active. Review recent leads,
                                monitor conversations, and manage website performance from
                                one place.
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-3 sm:flex sm:items-center">
                            <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 backdrop-blur">
                                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                                    Today
                                </p>

                                <p class="mt-1 text-sm font-semibold text-white">
                                    {{ now()->format('d M Y') }}
                                </p>
                            </div>

                            <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 backdrop-blur">
                                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                                    Account
                                </p>

                                <p class="mt-1 text-sm font-semibold text-white">
                                    {{ auth()->user()->isSuperAdmin() ? 'System-wide' : 'Tenant workspace' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section aria-labelledby="overview-heading">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h2
                            id="overview-heading"
                            class="text-base font-semibold text-slate-900"
                        >
                            Platform overview
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Current totals across your accessible workspace.
                        </p>
                    </div>
                </div>

                <div
                    class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-{{ auth()->user()->isSuperAdmin() ? '5' : '4' }}"
                >
                    <article class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-lg hover:shadow-slate-200/60">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-medium text-slate-500">
                                    Websites
                                </p>

                                <p class="mt-3 text-3xl font-bold tracking-tight text-slate-900">
                                    {{ number_format($totalWebsites) }}
                                </p>
                            </div>

                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-600 ring-1 ring-inset ring-blue-100">
                                <svg
                                    class="h-5 w-5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    aria-hidden="true"
                                >
                                    <circle cx="12" cy="12" r="9"/>
                                    <path stroke-linecap="round" d="M3 12h18M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18"/>
                                </svg>
                            </div>
                        </div>

                        <div class="mt-5 flex items-center gap-2 text-xs text-slate-500">
                            <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                            Connected sales channels
                        </div>
                    </article>

                    <article class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-lg hover:shadow-slate-200/60">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-medium text-slate-500">
                                    Leads
                                </p>

                                <p class="mt-3 text-3xl font-bold tracking-tight text-slate-900">
                                    {{ number_format($totalLeads) }}
                                </p>
                            </div>

                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-inset ring-emerald-100">
                                <svg
                                    class="h-5 w-5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    aria-hidden="true"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                    <circle cx="9" cy="7" r="4"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 8v6M22 11h-6"/>
                                </svg>
                            </div>
                        </div>

                        <div class="mt-5 flex items-center gap-2 text-xs text-slate-500">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                            Captured prospects
                        </div>
                    </article>

                    <article class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-violet-200 hover:shadow-lg hover:shadow-slate-200/60">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-medium text-slate-500">
                                    Qualified Leads
                                </p>

                                <p class="mt-3 text-3xl font-bold tracking-tight text-slate-900">
                                    {{ number_format($qualifiedLeads) }}
                                </p>
                            </div>

                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-violet-50 text-violet-600 ring-1 ring-inset ring-violet-100">
                                <svg
                                    class="h-5 w-5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    aria-hidden="true"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m9 12 2 2 4-4"/>
                                    <circle cx="12" cy="12" r="9"/>
                                </svg>
                            </div>
                        </div>

                        <div class="mt-5 flex items-center gap-2 text-xs text-slate-500">
                            <span class="h-1.5 w-1.5 rounded-full bg-violet-500"></span>
                            Sales-ready opportunities
                        </div>
                    </article>

                    <article class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-amber-200 hover:shadow-lg hover:shadow-slate-200/60">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-medium text-slate-500">
                                    Conversations
                                </p>

                                <p class="mt-3 text-3xl font-bold tracking-tight text-slate-900">
                                    {{ number_format($totalConversations) }}
                                </p>
                            </div>

                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-amber-600 ring-1 ring-inset ring-amber-100">
                                <svg
                                    class="h-5 w-5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    aria-hidden="true"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M8 10h8M8 14h5m-9 5 2.4-2.4A8 8 0 1 1 20 11a8 8 0 0 1-8 8H4Z"
                                    />
                                </svg>
                            </div>
                        </div>

                        <div class="mt-5 flex items-center gap-2 text-xs text-slate-500">
                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                            AI and live-agent sessions
                        </div>
                    </article>

                    @if(auth()->user()->isSuperAdmin())
                        <article class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-cyan-200 hover:shadow-lg hover:shadow-slate-200/60">
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="text-sm font-medium text-slate-500">
                                        Tenants
                                    </p>

                                    <p class="mt-3 text-3xl font-bold tracking-tight text-slate-900">
                                        {{ number_format($totalTenants) }}
                                    </p>
                                </div>

                                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-cyan-50 text-cyan-600 ring-1 ring-inset ring-cyan-100">
                                    <svg
                                        class="h-5 w-5"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        aria-hidden="true"
                                    >
                                        <rect x="3" y="4" width="18" height="16" rx="2"/>
                                        <path stroke-linecap="round" d="M8 8h8M8 12h8M8 16h5"/>
                                    </svg>
                                </div>
                            </div>

                            <div class="mt-5 flex items-center gap-2 text-xs text-slate-500">
                                <span class="h-1.5 w-1.5 rounded-full bg-cyan-500"></span>
                                Active customer workspaces
                            </div>
                        </article>
                    @endif
                </div>
            </section>

            <section class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 sm:px-6">
                        <div>
                            <h2 class="text-base font-semibold text-slate-900">
                                Recent Leads
                            </h2>

                            <p class="mt-1 text-sm text-slate-500">
                                Latest prospects captured by your sales agents.
                            </p>
                        </div>

                        <a
                            href="{{ route('admin.leads.index') }}"
                            class="inline-flex items-center gap-1 text-sm font-semibold text-blue-600 transition hover:text-blue-700"
                        >
                            View all

                            <svg
                                class="h-4 w-4"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                aria-hidden="true"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/>
                            </svg>
                        </a>
                    </div>

                    <div class="divide-y divide-slate-100">
                        @forelse($recentLeads as $lead)
                            @php
                                $score = (int) ($lead->lead_score ?? 0);

                                $scoreClasses = match (true) {
                                    $score >= 80 => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                    $score >= 50 => 'bg-amber-50 text-amber-700 ring-amber-200',
                                    default => 'bg-slate-100 text-slate-600 ring-slate-200',
                                };
                            @endphp

                            <div class="group flex items-start gap-4 px-5 py-4 transition hover:bg-slate-50/80 sm:px-6">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-blue-100 to-violet-100 text-sm font-bold text-blue-700">
                                    {{ strtoupper(substr($lead->name ?? $lead->email ?? 'U', 0, 1)) }}
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-slate-900">
                                                {{ $lead->name ?? 'Unknown visitor' }}
                                            </p>

                                            <p class="mt-0.5 truncate text-sm text-slate-500">
                                                {{ $lead->email ?? 'No email captured' }}
                                            </p>
                                        </div>

                                        <span class="inline-flex w-fit items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $scoreClasses }}">
                                            Score {{ $score }}
                                        </span>
                                    </div>

                                    <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-slate-500">
                                        <span class="inline-flex items-center gap-1.5">
                                            <svg
                                                class="h-3.5 w-3.5"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                aria-hidden="true"
                                            >
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 12v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-7"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m8 8 4-4 4 4M12 4v12"/>
                                            </svg>

                                            {{ $lead->product_interest ?? 'Interest not identified' }}
                                        </span>

                                        @if($lead->created_at)
                                            <span>
                                                {{ $lead->created_at->diffForHumans() }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="px-6 py-12 text-center">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                    <svg
                                        class="h-6 w-6"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        aria-hidden="true"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                        <circle cx="9" cy="7" r="4"/>
                                    </svg>
                                </div>

                                <h3 class="mt-4 text-sm font-semibold text-slate-900">
                                    No leads yet
                                </h3>

                                <p class="mt-1 text-sm text-slate-500">
                                    New captured leads will appear here.
                                </p>
                            </div>
                        @endforelse
                    </div>
                </article>

                <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 sm:px-6">
                        <div>
                            <h2 class="text-base font-semibold text-slate-900">
                                Recent Conversations
                            </h2>

                            <p class="mt-1 text-sm text-slate-500">
                                Latest AI and live-agent interactions.
                            </p>
                        </div>

                        <a
                            href="{{ route('admin.conversations.index') }}"
                            class="inline-flex items-center gap-1 text-sm font-semibold text-blue-600 transition hover:text-blue-700"
                        >
                            View all

                            <svg
                                class="h-4 w-4"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                aria-hidden="true"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/>
                            </svg>
                        </a>
                    </div>

                    <div class="divide-y divide-slate-100">
                        @forelse($recentConversations as $conversation)
                            @php
                                $mode = $conversation->mode ?? 'ai';

                                $modeLabel = match ($mode) {
                                    'live_waiting' => 'Waiting',
                                    'live' => 'Live Agent',
                                    default => 'AI',
                                };

                                $modeClasses = match ($mode) {
                                    'live_waiting' => 'bg-amber-50 text-amber-700 ring-amber-200',
                                    'live' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                    default => 'bg-blue-50 text-blue-700 ring-blue-200',
                                };
                            @endphp

                            <a
                                href="{{ route('admin.conversations.show', $conversation) }}"
                                class="group flex items-start gap-4 px-5 py-4 transition hover:bg-slate-50/80 sm:px-6"
                            >
                                <div class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                                    <svg
                                        class="h-5 w-5"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        aria-hidden="true"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M8 10h8M8 14h5m-9 5 2.4-2.4A8 8 0 1 1 20 11a8 8 0 0 1-8 8H4Z"
                                        />
                                    </svg>

                                    @if($mode === 'live')
                                        <span class="absolute -right-1 -top-1 h-3 w-3 rounded-full border-2 border-white bg-emerald-500"></span>
                                    @elseif($mode === 'live_waiting')
                                        <span class="absolute -right-1 -top-1 h-3 w-3 rounded-full border-2 border-white bg-amber-500"></span>
                                    @endif
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-slate-900">
                                                Visitor {{ $conversation->visitor_id }}
                                            </p>

                                            <p class="mt-0.5 truncate text-sm text-slate-500">
                                                {{ $conversation->website->name ?? 'Unknown website' }}
                                            </p>
                                        </div>

                                        <span class="inline-flex w-fit items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $modeClasses }}">
                                            {{ $modeLabel }}
                                        </span>
                                    </div>

                                    <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-slate-500">
                                        <span>
                                            Stage:
                                            <span class="font-medium text-slate-600">
                                                {{ ucfirst(str_replace('_', ' ', $conversation->lead_stage ?? 'discovery')) }}
                                            </span>
                                        </span>

                                        @if($conversation->updated_at)
                                            <span>
                                                Updated {{ $conversation->updated_at->diffForHumans() }}
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <svg
                                    class="mt-3 h-4 w-4 shrink-0 text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-blue-500"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    aria-hidden="true"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/>
                                </svg>
                            </a>
                        @empty
                            <div class="px-6 py-12 text-center">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                    <svg
                                        class="h-6 w-6"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        aria-hidden="true"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M8 10h8M8 14h5m-9 5 2.4-2.4A8 8 0 1 1 20 11a8 8 0 0 1-8 8H4Z"
                                        />
                                    </svg>
                                </div>

                                <h3 class="mt-4 text-sm font-semibold text-slate-900">
                                    No conversations yet
                                </h3>

                                <p class="mt-1 text-sm text-slate-500">
                                    New visitor conversations will appear here.
                                </p>
                            </div>
                        @endforelse
                    </div>
                </article>
            </section>
        </div>
    </div>
</x-app-layout>