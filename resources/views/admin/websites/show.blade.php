@php
    $embedCode = '<script src="' . url('/widget/widget.js') . '"></script>' . "\n" .
        '<script>' . "\n" .
        'ChatAgent.init({' . "\n" .
        '    token: "' . $website->embed_token . '",' . "\n" .
        '    server: "' . url('/') . '",' . "\n" .
        '    public_server: "' . url('/widget') . '"' . "\n" .
        '});' . "\n" .
        '</script>';

    $indexingStatus = $website->indexing_status ?: 'pending';

    $indexingClasses = match ($indexingStatus) {
        'completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'processing' => 'bg-blue-50 text-blue-700 ring-blue-200',
        'failed' => 'bg-red-50 text-red-700 ring-red-200',
        default => 'bg-amber-50 text-amber-700 ring-amber-200',
    };
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="flex items-center gap-2 text-sm text-slate-500">
                    <a href="{{ route('admin.websites.index') }}" class="hover:text-blue-600">Websites</a>
                    <span>/</span>
                    <span>{{ $website->name }}</span>
                </div>
                <div class="mt-2 flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-semibold tracking-tight text-slate-900">{{ $website->name }}</h1>
                    @if($website->is_active)
                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">Active</span>
                    @else
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600 ring-1 ring-slate-200">Inactive</span>
                    @endif
                </div>
                <a href="{{ $website->domain }}" target="_blank" rel="noopener noreferrer" class="mt-1 inline-flex items-center gap-1.5 text-sm text-blue-600 hover:text-blue-700">
                    {{ $website->domain }}
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 3h7v7M10 14 21 3M21 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5"/>
                    </svg>
                </a>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.websites.index') }}"
                   class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                    Back
                </a>
                <a href="{{ route('admin.websites.edit', $website) }}"
                   class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-blue-600/20 hover:bg-blue-700">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m14 4 6 6M4 20l4.5-1 11-11a2.1 2.1 0 0 0-3-3l-11 11L4 20Z"/>
                    </svg>
                    Edit website
                </a>
            </div>
        </div>
    </x-slot>

    <div
        class="min-h-screen bg-slate-50/70 py-8"
        x-data="{
            copied: false,
            copyText(text) {
                navigator.clipboard.writeText(text).then(() => {
                    this.copied = true;
                    setTimeout(() => this.copied = false, 1800);
                });
            }
        }"
    >
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            <section class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Indexed pages</p>
                    <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900">{{ number_format($website->knowledge_pages_count) }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Knowledge chunks</p>
                    <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900">{{ number_format($website->knowledge_chunks_count) }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Conversations</p>
                    <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900">{{ number_format($website->conversations_count) }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Leads</p>
                    <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900">{{ number_format($website->leads_count) }}</p>
                </div>
            </section>

            <section class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
                <div class="space-y-6">
                    <article class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-6 py-5">
                            <h2 class="text-base font-semibold text-slate-900">Website overview</h2>
                        </div>
                        <dl class="grid grid-cols-1 gap-x-8 gap-y-5 p-6 sm:grid-cols-2">
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Website name</dt>
                                <dd class="mt-1.5 text-sm font-medium text-slate-900">{{ $website->name }}</dd>
                            </div>
                            @if(auth()->user()->isSuperAdmin())
                                <div>
                                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Tenant</dt>
                                    <dd class="mt-1.5 text-sm font-medium text-slate-900">{{ $website->tenant->name ?? '—' }}</dd>
                                </div>
                            @endif
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Domain verification</dt>
                                <dd class="mt-1.5 text-sm font-medium text-slate-900">{{ $website->verify_domain ? 'Enabled' : 'Disabled' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Created</dt>
                                <dd class="mt-1.5 text-sm font-medium text-slate-900">{{ $website->created_at->format('d M Y, H:i') }}</dd>
                            </div>
                        </dl>
                    </article>

                    <article class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="flex flex-col gap-3 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 class="text-base font-semibold text-slate-900">Knowledge indexing</h2>
                                <p class="mt-1 text-sm text-slate-500">Crawl website content and refresh the assistant knowledge base.</p>
                            </div>
                            <span class="inline-flex w-fit rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $indexingClasses }}">
                                {{ ucfirst($indexingStatus) }}
                            </span>
                        </div>

                        <div class="p-6">
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                @if($website->indexing_started_at)
                                    <div class="rounded-xl bg-slate-50 p-4">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Started</p>
                                        <p class="mt-1.5 text-sm font-medium text-slate-800">{{ $website->indexing_started_at->format('d M Y, H:i') }}</p>
                                    </div>
                                @endif
                                @if($website->indexing_completed_at)
                                    <div class="rounded-xl bg-slate-50 p-4">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Completed</p>
                                        <p class="mt-1.5 text-sm font-medium text-slate-800">{{ $website->indexing_completed_at->format('d M Y, H:i') }}</p>
                                    </div>
                                @endif
                            </div>

                            @if($website->indexing_error)
                                <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                                    <p class="font-semibold">Indexing failed</p>
                                    <p class="mt-1 break-words">{{ $website->indexing_error }}</p>
                                </div>
                            @endif

                            <form method="POST" action="{{ route('admin.websites.indexKnowledge', $website) }}"
                                  class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-[180px_minmax(0,1fr)]"
                                  onsubmit="return confirm('Start indexing this website now?')">
                                @csrf
                                <div>
                                    <label for="limit" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Crawl limit</label>
                                    <input id="limit" type="number" name="limit" value="100" min="1" max="1000"
                                           class="mt-2 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                                <div class="flex items-end gap-3">
                                    <button class="inline-flex flex-1 items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">
                                        {{ $indexingStatus === 'processing' ? 'Restart indexing' : 'Index / re-index website' }}
                                    </button>
                                    <a href="{{ route('websites.knowledge-sources.index', $website) }}"
                                       class="inline-flex items-center justify-center rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                        Knowledge Sources
                                    </a>
                                </div>
                            </form>
                        </div>
                    </article>

                    <article class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-6 py-5">
                            <h2 class="text-base font-semibold text-slate-900">Embed code</h2>
                            <p class="mt-1 text-sm text-slate-500">Place this immediately before the closing <code class="rounded bg-slate-100 px-1.5 py-0.5">&lt;/body&gt;</code> tag.</p>
                        </div>
                        <div class="p-6">
                            <div class="relative">
                                <pre class="max-h-72 overflow-auto rounded-xl bg-slate-950 p-5 text-sm leading-6 text-slate-100"><code>{{ $embedCode }}</code></pre>
                                <button
                                    type="button"
                                    @click="copyText(@js($embedCode))"
                                    class="absolute right-3 top-3 rounded-lg bg-white/10 px-3 py-1.5 text-xs font-semibold text-white backdrop-blur hover:bg-white/20"
                                >
                                    <span x-text="copied ? 'Copied' : 'Copy code'"></span>
                                </button>
                            </div>
                        </div>
                    </article>

                    <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
                            <div>
                                <h2 class="text-base font-semibold text-slate-900">Recently indexed pages</h2>
                                <p class="mt-1 text-sm text-slate-500">Most recent content discovered by the crawler.</p>
                            </div>
                            <a href="{{ route('admin.websites.knowledge.index', $website) }}" class="text-sm font-semibold text-blue-600 hover:text-blue-700">View all</a>
                        </div>

                        @if($website->knowledgePages->count())
                            <div class="divide-y divide-slate-100">
                                @foreach($website->knowledgePages as $page)
                                    <div class="flex items-start gap-4 px-6 py-4">
                                        <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M6 3h9l3 3v15H6zM14 3v4h4"/>
                                            </svg>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-sm font-semibold text-slate-900">{{ $page->title ?? 'Untitled page' }}</p>
                                            <a href="{{ $page->url }}" target="_blank" rel="noopener noreferrer" class="mt-1 block truncate text-xs text-blue-600 hover:text-blue-700">{{ $page->url }}</a>
                                        </div>
                                        @if($page->is_indexed)
                                            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Indexed</span>
                                        @else
                                            <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">Pending</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="px-6 py-12 text-center">
                                <p class="text-sm font-semibold text-slate-900">No indexed pages yet</p>
                                <p class="mt-1 text-sm text-slate-500">Run indexing to populate the knowledge base.</p>
                            </div>
                        @endif
                    </article>
                </div>

                <aside class="space-y-6">
                    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-base font-semibold text-slate-900">Assistant profile</h2>
                        <div class="mt-5 flex items-center gap-4">
                            <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-full border border-slate-200 bg-slate-50 text-2xl">
                                @if($website->chatbot_avatar)
                                    <img src="{{ asset('storage/' . $website->chatbot_avatar) }}" alt="Chatbot avatar" class="h-full w-full object-cover">
                                @else
                                    🤖
                                @endif
                            </div>
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-slate-900">{{ $website->chatbot_name ?: $website->name . ' Assistant' }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ config('chatbot.themes.' . $website->chatbot_theme . '.label') ?? ucfirst($website->chatbot_theme) }} theme</p>
                            </div>
                        </div>

                        <div class="mt-5 rounded-xl bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Instructions</p>
                            <p class="mt-2 max-h-48 overflow-y-auto whitespace-pre-line text-sm leading-6 text-slate-600">{{ $website->chatbot_instructions ?: 'No custom instructions provided.' }}</p>
                        </div>
                    </article>

                    <article class="rounded-2xl border border-amber-200 bg-amber-50 p-6">
                        <h2 class="text-base font-semibold text-amber-900">Embed token</h2>
                        <p class="mt-1 text-sm text-amber-800">Treat this token as a public installation credential.</p>

                        <div class="mt-4 flex items-center gap-2 rounded-xl border border-amber-200 bg-white p-3">
                            <code class="min-w-0 flex-1 truncate text-xs text-slate-700">{{ $website->embed_token }}</code>
                            <button type="button" @click="copyText(@js($website->embed_token))" class="shrink-0 text-xs font-semibold text-blue-600 hover:text-blue-700">
                                Copy
                            </button>
                        </div>

                        <form
                            method="POST"
                            action="{{ route('admin.websites.regenerateToken', $website) }}"
                            class="mt-4"
                            onsubmit="return confirm('Regenerating the token immediately invalidates the existing widget installation. Continue?')"
                        >
                            @csrf
                            <button class="w-full rounded-xl border border-red-200 bg-white px-4 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-50">
                                Regenerate token
                            </button>
                        </form>
                    </article>
                </aside>
            </section>
        </div>
    </div>

    @if(in_array($website->indexing_status, ['pending', 'processing']))
        <script>
            window.setTimeout(() => window.location.reload(), 10000);
        </script>
    @endif
</x-app-layout>
