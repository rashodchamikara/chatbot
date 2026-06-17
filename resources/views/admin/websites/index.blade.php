<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Websites</h1>
                <p class="mt-1 text-sm text-slate-500">Manage connected websites, indexing health, and assistant configuration.</p>
            </div>

            <a
                href="{{ route('admin.websites.create') }}"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-blue-600/20 hover:bg-blue-700"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" d="M12 5v14M5 12h14"/>
                </svg>
                Add website
            </a>
        </div>
    </x-slot>

    <div class="min-h-screen bg-slate-50/70 py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <form method="GET" action="{{ route('admin.websites.index') }}" class="grid grid-cols-1 gap-3 md:grid-cols-[minmax(0,1fr)_220px_auto_auto]">
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="7"/>
                                <path stroke-linecap="round" d="m20 20-3.5-3.5"/>
                            </svg>
                        </span>
                        <input
                            type="search"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Search by website name or domain"
                            class="block w-full rounded-xl border-slate-300 pl-10 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                    </div>

                    <select name="status" class="rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All statuses</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                    </select>

                    <button class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                        Apply filters
                    </button>

                    @if(request()->filled('search') || request()->filled('status'))
                        <a
                            href="{{ route('admin.websites.index') }}"
                            class="inline-flex items-center justify-center rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50"
                        >
                            Clear
                        </a>
                    @endif
                </form>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="hidden overflow-x-auto lg:block">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Website</th>
                                @if(auth()->user()->isSuperAdmin())
                                    <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Tenant</th>
                                @endif
                                <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Knowledge</th>
                                <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Activity</th>
                                <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                                <th class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse($websites as $website)
                                @php
                                    $host = parse_url($website->domain, PHP_URL_HOST) ?: $website->domain;
                                @endphp
                                <tr class="transition hover:bg-slate-50/80">
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600 ring-1 ring-blue-100">
                                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <circle cx="12" cy="12" r="9"/>
                                                    <path d="M3 12h18M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18"/>
                                                </svg>
                                            </div>
                                            <div class="min-w-0">
                                                <a href="{{ route('admin.websites.show', $website) }}" class="block truncate text-sm font-semibold text-slate-900 hover:text-blue-600">
                                                    {{ $website->name }}
                                                </a>
                                                <a href="{{ $website->domain }}" target="_blank" rel="noopener noreferrer" class="mt-0.5 block truncate text-xs text-slate-500 hover:text-blue-600">
                                                    {{ $host }}
                                                </a>
                                            </div>
                                        </div>
                                    </td>

                                    @if(auth()->user()->isSuperAdmin())
                                        <td class="px-5 py-4 text-sm text-slate-600">{{ $website->tenant->name ?? '—' }}</td>
                                    @endif

                                    <td class="px-5 py-4">
                                        <div class="text-sm font-semibold text-slate-800">{{ number_format($website->knowledge_pages_count) }} pages</div>
                                        <div class="mt-1 text-xs text-slate-500">Indexed knowledge</div>
                                    </td>

                                    <td class="px-5 py-4">
                                        <div class="flex flex-wrap gap-2">
                                            <span class="rounded-full bg-violet-50 px-2.5 py-1 text-xs font-medium text-violet-700">{{ number_format($website->leads_count) }} leads</span>
                                            <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">{{ number_format($website->conversations_count) }} chats</span>
                                        </div>
                                    </td>

                                    <td class="px-5 py-4">
                                        @if($website->is_active)
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">
                                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Active
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600 ring-1 ring-slate-200">
                                                <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span> Inactive
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-5 py-4 text-right">
                                        <a href="{{ route('admin.websites.show', $website) }}"
                                           class="inline-flex items-center rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">
                                            Manage
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ auth()->user()->isSuperAdmin() ? 6 : 5 }}" class="px-6 py-16 text-center">
                                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="9"/>
                                                <path d="M3 12h18M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18"/>
                                            </svg>
                                        </div>
                                        <h3 class="mt-4 text-sm font-semibold text-slate-900">No websites found</h3>
                                        <p class="mt-1 text-sm text-slate-500">Add a website or adjust your filters.</p>
                                        <a href="{{ route('admin.websites.create') }}" class="mt-4 inline-flex rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                                            Add website
                                        </a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="divide-y divide-slate-100 lg:hidden">
                    @forelse($websites as $website)
                        <a href="{{ route('admin.websites.show', $website) }}" class="block p-5 hover:bg-slate-50">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-slate-900">{{ $website->name }}</p>
                                    <p class="mt-1 truncate text-sm text-slate-500">{{ $website->domain }}</p>
                                </div>
                                @if($website->is_active)
                                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Active</span>
                                @else
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">Inactive</span>
                                @endif
                            </div>

                            <div class="mt-4 grid grid-cols-3 gap-2 text-center">
                                <div class="rounded-xl bg-slate-50 p-3">
                                    <p class="font-semibold text-slate-900">{{ $website->knowledge_pages_count }}</p>
                                    <p class="mt-1 text-[11px] text-slate-500">Pages</p>
                                </div>
                                <div class="rounded-xl bg-slate-50 p-3">
                                    <p class="font-semibold text-slate-900">{{ $website->leads_count }}</p>
                                    <p class="mt-1 text-[11px] text-slate-500">Leads</p>
                                </div>
                                <div class="rounded-xl bg-slate-50 p-3">
                                    <p class="font-semibold text-slate-900">{{ $website->conversations_count }}</p>
                                    <p class="mt-1 text-[11px] text-slate-500">Chats</p>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="px-6 py-14 text-center text-sm text-slate-500">No websites found.</div>
                    @endforelse
                </div>
            </section>

            <div>
                {{ $websites->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
