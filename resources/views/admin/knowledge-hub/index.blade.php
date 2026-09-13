<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-semibold tracking-tight text-slate-900">Knowledge Hub</h1>
                    <span class="inline-flex rounded-full bg-violet-50 px-2.5 py-1 text-xs font-semibold text-violet-700 ring-1 ring-inset ring-violet-200">AI Training</span>
                </div>
                <p class="mt-1 text-sm text-slate-500">Train your AI without needing a website. Add information manually or upload business documents.</p>
            </div>

            @if($selectedAgent)
                <a href="{{ route('admin.knowledge-hub.create', ['agent_id' => $selectedAgent->id]) }}"
                   class="inline-flex items-center justify-center gap-2 rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-violet-700">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                    Add manual knowledge
                </a>
            @endif
        </div>
    </x-slot>

    <div class="min-h-screen bg-slate-50/70 py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
            @endif
            @if(session('warning'))
                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ session('warning') }}</div>
            @endif
            @if($errors->any())
                <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800"><ul class="list-disc space-y-1 pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif

            @if(!$selectedAgent)
                <section class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center shadow-sm">
                    <h2 class="text-lg font-semibold text-slate-900">No AI agent is available yet</h2>
                    <p class="mx-auto mt-2 max-w-lg text-sm text-slate-500">Connect WhatsApp or add a website first. For tenant users, the system will normally create the default agent automatically.</p>
                    <a href="{{ route('admin.channels.whatsapp.create') }}" class="mt-5 inline-flex rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Connect WhatsApp</a>
                </section>
            @else
                <section class="overflow-hidden rounded-3xl bg-gradient-to-br from-violet-950 via-slate-950 to-blue-950 p-6 text-white shadow-xl sm:p-8">
                    <div class="grid gap-6 lg:grid-cols-[1.3fr_.7fr] lg:items-center">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.22em] text-violet-300">Active knowledge base</p>
                            <h2 class="mt-3 text-2xl font-semibold">{{ $selectedAgent->name }}</h2>
                            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300">Everything below is available to every connected channel assigned to this AI agent — including WhatsApp-only businesses.</p>
                        </div>
                        <form method="GET" class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <label class="text-xs font-bold uppercase tracking-wide text-slate-400">Switch AI agent</label>
                            <select name="agent_id" onchange="this.form.submit()" class="mt-2 w-full rounded-xl border-white/10 bg-slate-900 text-sm text-white focus:border-violet-400 focus:ring-violet-400">
                                @foreach($agents as $agent)
                                    <option value="{{ $agent->id }}" @selected($selectedAgent->id === $agent->id)>
                                        {{ $agent->name }}@if(auth()->user()->isSuperAdmin()) — {{ $agent->tenant?->company_name ?: $agent->tenant?->name }}@endif
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                </section>

                <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach([
                        ['label' => 'Manual entries', 'value' => $stats['manual'], 'tone' => 'violet'],
                        ['label' => 'Uploaded files', 'value' => $stats['files'], 'tone' => 'blue'],
                        ['label' => 'Searchable chunks', 'value' => $stats['chunks'], 'tone' => 'emerald'],
                        ['label' => 'Ready sources', 'value' => $stats['ready'], 'tone' => 'amber'],
                    ] as $stat)
                        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <p class="text-sm text-slate-500">{{ $stat['label'] }}</p>
                            <p class="mt-3 text-3xl font-bold tracking-tight text-slate-900">{{ number_format($stat['value']) }}</p>
                        </article>
                    @endforeach
                </section>

                <section class="grid gap-6 lg:grid-cols-[.8fr_1.2fr]">
                    <form method="POST" action="{{ route('admin.knowledge-hub.files.store') }}" enctype="multipart/form-data" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                        @csrf
                        <input type="hidden" name="ai_agent_id" value="{{ $selectedAgent->id }}">

                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 16V4m0 0L8 8m4-4 4 4M5 14v5h14v-5"/></svg>
                        </div>
                        <h2 class="mt-4 font-semibold text-slate-900">Upload business documents</h2>
                        <p class="mt-1 text-sm leading-6 text-slate-500">PDF, DOCX, TXT, CSV, XLSX and supported images. Great for brochures, menus, price lists, policies and FAQ documents.</p>

                        <label class="mt-5 block cursor-pointer rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50 p-6 text-center transition hover:border-blue-300 hover:bg-blue-50/40">
                            <input type="file" name="files[]" multiple required class="sr-only" accept=".pdf,.docx,.txt,.csv,.xlsx,.jpg,.jpeg,.png,.webp">
                            <span class="text-sm font-semibold text-slate-700">Choose files</span>
                            <span class="mt-1 block text-xs text-slate-500">You can select multiple files at once</span>
                        </label>

                        <button class="mt-4 w-full rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Upload & train</button>
                    </form>

                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h2 class="font-semibold text-slate-900">What should I teach the AI?</h2>
                                <p class="mt-1 text-sm text-slate-500">For WhatsApp-only businesses, manual knowledge is often the fastest way to become useful.</p>
                            </div>
                        </div>

                        <div class="mt-5 grid gap-3 sm:grid-cols-2">
                            @foreach([
                                ['FAQ', 'Opening hours, delivery, returns, booking rules'],
                                ['Products', 'Names, features, variants and what each product is for'],
                                ['Pricing', 'Approved prices, packages, fees and quotation rules'],
                                ['Services', 'What you offer, service areas and expected process'],
                                ['Policies', 'Refunds, warranties, cancellations and privacy details'],
                                ['Business info', 'Locations, contact details and important company facts'],
                            ] as [$title, $text])
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                    <p class="text-sm font-semibold text-slate-800">{{ $title }}</p>
                                    <p class="mt-1 text-xs leading-5 text-slate-500">{{ $text }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>

                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 sm:px-6">
                        <div>
                            <h2 class="font-semibold text-slate-900">Manual knowledge</h2>
                            <p class="mt-1 text-sm text-slate-500">Short, structured business facts are ideal for WhatsApp sales and support.</p>
                        </div>
                        <a href="{{ route('admin.knowledge-hub.create', ['agent_id' => $selectedAgent->id]) }}" class="text-sm font-semibold text-violet-600 hover:text-violet-700">Add entry</a>
                    </div>

                    <div class="divide-y divide-slate-100">
                        @forelse($pages as $page)
                            <div class="flex flex-col gap-4 px-5 py-4 sm:px-6 lg:flex-row lg:items-center lg:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="truncate text-sm font-semibold text-slate-900">{{ $page->title }}</p>
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold uppercase text-slate-600">{{ $page->type }}</span>
                                        @if($page->is_indexed && $page->is_active)
                                            <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700">Ready</span>
                                        @elseif(!$page->is_indexed)
                                            <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700">Needs indexing</span>
                                        @else
                                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600">Paused</span>
                                        @endif
                                    </div>
                                    <p class="mt-1 text-xs text-slate-500">{{ number_format($page->chunks_count) }} chunks · updated {{ $page->updated_at->diffForHumans() }}</p>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    @if(!$page->is_indexed)
                                        <form method="POST" action="{{ route('admin.knowledge-hub.pages.index', $page) }}">@csrf<button class="rounded-lg border border-amber-200 px-3 py-1.5 text-xs font-semibold text-amber-700 hover:bg-amber-50">Index now</button></form>
                                    @endif
                                    <form method="POST" action="{{ route('admin.knowledge-hub.pages.toggle', $page) }}">@csrf @method('PATCH')<button class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">{{ $page->is_active ? 'Pause' : 'Enable' }}</button></form>
                                    <form method="POST" action="{{ route('admin.knowledge-hub.pages.destroy', $page) }}" onsubmit="return confirm('Delete this knowledge entry?');">@csrf @method('DELETE')<button class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50">Delete</button></form>
                                </div>
                            </div>
                        @empty
                            <div class="px-6 py-10 text-center text-sm text-slate-500">No manual knowledge yet. Add FAQs, products or business information to start training the AI.</div>
                        @endforelse
                    </div>
                </section>

                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                        <h2 class="font-semibold text-slate-900">Uploaded documents</h2>
                        <p class="mt-1 text-sm text-slate-500">Processing runs through your existing extraction, chunking and embedding queues.</p>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @forelse($sources as $source)
                            @php
                                $sourceStatus = match($source->status) {
                                    'ready' => 'bg-emerald-50 text-emerald-700',
                                    'failed' => 'bg-red-50 text-red-700',
                                    default => 'bg-amber-50 text-amber-700',
                                };
                            @endphp
                            <div class="flex flex-col gap-4 px-5 py-4 sm:px-6 lg:flex-row lg:items-center lg:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2"><p class="truncate text-sm font-semibold text-slate-900">{{ $source->original_name ?: $source->name }}</p><span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $sourceStatus }}">{{ ucfirst($source->status) }}</span></div>
                                    <p class="mt-1 text-xs text-slate-500">{{ number_format((int) $source->chunk_count) }} chunks · {{ number_format(((int) $source->size_bytes) / 1024, 1) }} KB</p>
                                    @if($source->processing_error)<p class="mt-2 text-xs text-red-600">{{ $source->processing_error }}</p>@endif
                                </div>
                                <form method="POST" action="{{ route('admin.knowledge-hub.sources.destroy', $source) }}" onsubmit="return confirm('Delete this knowledge document?');">@csrf @method('DELETE')<button class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50">Delete</button></form>
                            </div>
                        @empty
                            <div class="px-6 py-10 text-center text-sm text-slate-500">No documents uploaded for this AI agent yet.</div>
                        @endforelse
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
