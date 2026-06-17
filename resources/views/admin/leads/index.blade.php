<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
                    Leads
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Review captured prospects, prioritize high-value opportunities, and track sales progress.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2 text-xs font-medium">
                <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1.5 text-emerald-700 ring-1 ring-emerald-200">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    Qualified
                </span>

                <span class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1.5 text-blue-700 ring-1 ring-blue-200">
                    <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                    New lead
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
                    action="{{ route('admin.leads.index') }}"
                    class="grid grid-cols-1 gap-3 lg:grid-cols-[minmax(0,1fr)_220px_auto_auto]"
                >
                    <div>
                        <label for="search" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Search
                        </label>

                        <div class="flex items-center rounded-xl border border-slate-300 bg-white shadow-sm focus-within:border-blue-500 focus-within:ring-1 focus-within:ring-blue-500">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center text-slate-400">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="11" cy="11" r="7"/>
                                    <path stroke-linecap="round" d="m20 20-3.5-3.5"/>
                                </svg>
                            </div>

                            <input
                                id="search"
                                type="search"
                                name="search"
                                value="{{ request('search') }}"
                                placeholder="Search name, email, phone, product..."
                                class="h-11 w-full border-0 bg-transparent px-0 pr-4 text-sm text-slate-900 placeholder:text-slate-400 focus:ring-0"
                            >
                        </div>
                    </div>

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
                            <option value="new" @selected(request('status') === 'new')>New</option>
                            <option value="qualified" @selected(request('status') === 'qualified')>Qualified</option>
                            <option value="contacted" @selected(request('status') === 'contacted')>Contacted</option>
                            <option value="converted" @selected(request('status') === 'converted')>Converted</option>
                            <option value="closed" @selected(request('status') === 'closed')>Closed</option>
                        </select>
                    </div>

                    <div class="flex items-end">
                        <button
                            type="submit"
                            class="inline-flex h-11 w-full items-center justify-center rounded-xl bg-slate-900 px-5 text-sm font-semibold text-white transition hover:bg-slate-800"
                        >
                            Apply filters
                        </button>
                    </div>

                    <div class="flex items-end">
                        <a
                            href="{{ route('admin.leads.index') }}"
                            class="inline-flex h-11 w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
                        >
                            Clear
                        </a>
                    </div>
                </form>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                {{-- Desktop table --}}
                <div class="hidden overflow-x-auto lg:block">
                    <table class="w-full table-auto border-collapse">
                        <thead class="bg-slate-50">
                            <tr class="border-b border-slate-200">
                                <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Lead</th>
                                <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Contact</th>
                                <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Interest</th>
                                <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Score</th>
                                <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>

                                @if(auth()->user()->isSuperAdmin())
                                    <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Tenant</th>
                                @endif

                                <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Created</th>
                                <th class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse($leads as $lead)
                                @php
                                    $score = (int) ($lead->lead_score ?? 0);

                                    $scoreClasses = match (true) {
                                        $score >= 80 => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                        $score >= 50 => 'bg-amber-50 text-amber-700 ring-amber-200',
                                        default => 'bg-slate-100 text-slate-600 ring-slate-200',
                                    };

                                    $statusClasses = match ($lead->status) {
                                        'qualified' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                        'contacted' => 'bg-violet-50 text-violet-700 ring-violet-200',
                                        'converted' => 'bg-blue-50 text-blue-700 ring-blue-200',
                                        'closed' => 'bg-slate-100 text-slate-600 ring-slate-200',
                                        default => 'bg-amber-50 text-amber-700 ring-amber-200',
                                    };

                                    $leadName = $lead->name ?: 'Unknown lead';
                                    $initial = strtoupper(mb_substr($leadName, 0, 1));
                                @endphp

                                <tr class="transition hover:bg-slate-50">
                                    <td class="px-5 py-4 align-middle">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-blue-100 to-violet-100 text-sm font-bold text-blue-700">
                                                {{ $initial }}
                                            </div>

                                            <div class="min-w-0">
                                                <a
                                                    href="{{ route('admin.leads.show', $lead) }}"
                                                    class="block max-w-56 truncate text-sm font-semibold text-slate-900 hover:text-blue-600"
                                                >
                                                    {{ $leadName }}
                                                </a>

                                                <p class="mt-1 max-w-56 truncate text-xs text-slate-500">
                                                    {{ $lead->website->name ?? 'Unknown website' }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-5 py-4 align-middle">
                                        <div class="space-y-1 text-sm">
                                            <p class="max-w-56 truncate text-slate-700">
                                                {{ $lead->email ?? 'No email captured' }}
                                            </p>

                                            <p class="max-w-56 truncate text-slate-500">
                                                {{ $lead->phone ?? 'No phone captured' }}
                                            </p>
                                        </div>
                                    </td>

                                    <td class="px-5 py-4 align-middle">
                                        <p class="max-w-56 truncate text-sm text-slate-700">
                                            {{ $lead->product_interest ?? 'Not identified' }}
                                        </p>
                                    </td>

                                    <td class="px-5 py-4 align-middle">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $scoreClasses }}">
                                            {{ $score }}
                                        </span>
                                    </td>

                                    <td class="px-5 py-4 align-middle">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $statusClasses }}">
                                            {{ ucfirst($lead->status) }}
                                        </span>
                                    </td>

                                    @if(auth()->user()->isSuperAdmin())
                                        <td class="px-5 py-4 align-middle text-sm text-slate-600">
                                            {{ $lead->tenant->name ?? '—' }}
                                        </td>
                                    @endif

                                    <td class="px-5 py-4 align-middle">
                                        <p class="text-sm text-slate-700">{{ $lead->created_at->format('d M Y') }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ $lead->created_at->format('H:i') }}</p>
                                    </td>

                                    <td class="px-5 py-4 text-right align-middle">
                                        <a
                                            href="{{ route('admin.leads.show', $lead) }}"
                                            class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700"
                                        >
                                            View lead
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="{{ auth()->user()->isSuperAdmin() ? 8 : 7 }}"
                                        class="px-6 py-16 text-center"
                                    >
                                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                                <circle cx="9" cy="7" r="4"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 8v6M22 11h-6"/>
                                            </svg>
                                        </div>

                                        <h3 class="mt-4 text-sm font-semibold text-slate-900">
                                            No leads found
                                        </h3>

                                        <p class="mt-1 text-sm text-slate-500">
                                            Try changing the filters or wait for a new captured lead.
                                        </p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Mobile cards --}}
                <div class="divide-y divide-slate-100 lg:hidden">
                    @forelse($leads as $lead)
                        @php
                            $score = (int) ($lead->lead_score ?? 0);

                            $statusClasses = match ($lead->status) {
                                'qualified' => 'bg-emerald-50 text-emerald-700',
                                'contacted' => 'bg-violet-50 text-violet-700',
                                'converted' => 'bg-blue-50 text-blue-700',
                                'closed' => 'bg-slate-100 text-slate-600',
                                default => 'bg-amber-50 text-amber-700',
                            };

                            $leadName = $lead->name ?: 'Unknown lead';
                        @endphp

                        <article class="p-5">
                            <div class="flex items-start gap-3">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-blue-100 to-violet-100 text-sm font-bold text-blue-700">
                                    {{ strtoupper(mb_substr($leadName, 0, 1)) }}
                                </div>

                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-semibold text-slate-900">{{ $leadName }}</p>
                                    <p class="mt-1 truncate text-sm text-slate-500">{{ $lead->website->name ?? 'Unknown website' }}</p>
                                </div>

                                <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClasses }}">
                                    {{ ucfirst($lead->status) }}
                                </span>
                            </div>

                            <div class="mt-4 space-y-2 rounded-xl bg-slate-50 p-4 text-sm">
                                <div class="flex items-center justify-between gap-4">
                                    <span class="text-slate-500">Email</span>
                                    <span class="max-w-52 truncate font-medium text-slate-800">{{ $lead->email ?? '—' }}</span>
                                </div>

                                <div class="flex items-center justify-between gap-4">
                                    <span class="text-slate-500">Phone</span>
                                    <span class="font-medium text-slate-800">{{ $lead->phone ?? '—' }}</span>
                                </div>

                                <div class="flex items-center justify-between gap-4">
                                    <span class="text-slate-500">Score</span>
                                    <span class="font-semibold text-slate-900">{{ $score }}</span>
                                </div>
                            </div>

                            <a
                                href="{{ route('admin.leads.show', $lead) }}"
                                class="mt-4 inline-flex h-10 w-full items-center justify-center rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                            >
                                View lead
                            </a>
                        </article>
                    @empty
                        <div class="px-6 py-14 text-center text-sm text-slate-500">
                            No leads found.
                        </div>
                    @endforelse
                </div>
            </section>

            <div class="mt-6">
                {{ $leads->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
