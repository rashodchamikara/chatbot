<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-slate-900">AI Agents</h1>
                <p class="mt-1 text-sm text-slate-500">Configure the AI identity shared by Website, WhatsApp and future channels.</p>
            </div>
            @if(Route::has('admin.knowledge-hub.index'))
                <a href="{{ route('admin.knowledge-hub.index') }}" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                    Train AI knowledge
                </a>
            @endif
        </div>
    </x-slot>

    <div class="min-h-screen bg-slate-50/70 py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</div>
            @endif

            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-5 sm:px-6">
                    <div class="flex items-start gap-3">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 3h6v3H9zM5 9h14v10H5zM9 13h.01M15 13h.01M9 17h6"/></svg>
                        </div>
                        <div>
                            <h2 class="font-semibold text-slate-900">One agent, many channels</h2>
                            <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-500">An agent owns its instructions and knowledge base. Attach the same agent to a website and WhatsApp when both should answer from the same business knowledge.</p>
                        </div>
                    </div>
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse($agents as $agent)
                        <div class="px-5 py-5 sm:px-6">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="truncate text-base font-semibold text-slate-900">{{ $agent->name }}</h3>
                                        @if($agent->is_default)
                                            <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700 ring-1 ring-inset ring-blue-200">Default</span>
                                        @endif
                                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $agent->status === 'active' ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-slate-100 text-slate-600 ring-slate-200' }}">{{ ucfirst($agent->status) }}</span>
                                    </div>
                                    @if(auth()->user()->isSuperAdmin())
                                        <p class="mt-1 text-xs font-medium text-slate-400">{{ $agent->tenant->company_name ?: $agent->tenant->name }}</p>
                                    @endif
                                    <div class="mt-3 flex flex-wrap gap-x-5 gap-y-2 text-sm text-slate-500">
                                        <span><strong class="font-semibold text-slate-700">{{ $agent->channel_connections_count }}</strong> channels</span>
                                        <span><strong class="font-semibold text-slate-700">{{ $agent->knowledge_pages_count + $agent->knowledge_sources_count }}</strong> knowledge sources</span>
                                        <span><strong class="font-semibold text-slate-700">{{ $agent->conversations_count }}</strong> conversations</span>
                                    </div>
                                </div>

                                <div class="flex shrink-0 flex-wrap gap-2">
                                    <a href="{{ route('admin.knowledge-hub.index', ['agent_id' => $agent->id]) }}" class="rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Knowledge</a>
                                    <a href="{{ route('admin.ai-agents.edit', $agent) }}" class="rounded-xl bg-slate-900 px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">Configure</a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-14 text-center">
                            <p class="font-semibold text-slate-900">No AI agents yet</p>
                            <p class="mt-1 text-sm text-slate-500">Connecting a channel or opening the Knowledge Hub will provision the tenant's default agent.</p>
                        </div>
                    @endforelse
                </div>
            </section>

            @if(method_exists($agents, 'links'))
                {{ $agents->links() }}
            @endif
        </div>
    </div>
</x-app-layout>
