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
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2 text-sm text-slate-500">
                    <a href="{{ route('admin.leads.index') }}" class="hover:text-blue-600">Leads</a>
                    <span>/</span>
                    <span>#{{ $lead->id }}</span>
                </div>

                <div class="mt-2 flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
                        {{ $leadName }}
                    </h1>

                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $statusClasses }}">
                        {{ ucfirst($lead->status) }}
                    </span>
                </div>

                <p class="mt-1 text-sm text-slate-500">
                    {{ $lead->website->name ?? 'Unknown website' }}
                </p>
            </div>

            <a
                href="{{ route('admin.leads.index') }}"
                class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
            >
                Back to leads
            </a>
        </div>
    </x-slot>

    <div class="min-h-screen bg-slate-50 py-8">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                <aside class="space-y-6 xl:col-span-1">
                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h2 class="text-base font-semibold text-slate-900">Lead profile</h2>
                                <p class="mt-1 text-sm text-slate-500">Captured contact and qualification details</p>
                            </div>

                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $scoreClasses }}">
                                Score {{ $score }}
                            </span>
                        </div>

                        <dl class="mt-6 space-y-5 text-sm">
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Name</dt>
                                <dd class="mt-1.5 font-medium text-slate-900">{{ $lead->name ?? '—' }}</dd>
                            </div>

                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Email</dt>
                                <dd class="mt-1.5 break-all font-medium text-slate-900">
                                    @if($lead->email)
                                        <a href="mailto:{{ $lead->email }}" class="text-blue-600 hover:text-blue-700">{{ $lead->email }}</a>
                                    @else
                                        —
                                    @endif
                                </dd>
                            </div>

                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Phone</dt>
                                <dd class="mt-1.5 font-medium text-slate-900">
                                    @if($lead->phone)
                                        <a href="tel:{{ $lead->phone }}" class="text-blue-600 hover:text-blue-700">{{ $lead->phone }}</a>
                                    @else
                                        —
                                    @endif
                                </dd>
                            </div>

                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Country</dt>
                                <dd class="mt-1.5 font-medium text-slate-900">{{ $lead->country ?? '—' }}</dd>
                            </div>

                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Preferred contact time</dt>
                                <dd class="mt-1.5 font-medium text-slate-900">{{ $lead->preferred_contact_time ?? '—' }}</dd>
                            </div>

                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Product interest</dt>
                                <dd class="mt-1.5 font-medium text-slate-900">{{ $lead->product_interest ?? '—' }}</dd>
                            </div>

                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Website</dt>
                                <dd class="mt-1.5 font-medium text-slate-900">{{ $lead->website->name ?? '—' }}</dd>
                            </div>

                            @if(auth()->user()->isSuperAdmin())
                                <div>
                                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Tenant</dt>
                                    <dd class="mt-1.5 font-medium text-slate-900">{{ $lead->tenant->name ?? '—' }}</dd>
                                </div>
                            @endif

                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Created</dt>
                                <dd class="mt-1.5 font-medium text-slate-900">{{ $lead->created_at->format('d M Y, H:i') }}</dd>
                            </div>
                        </dl>
                    </section>

                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-base font-semibold text-slate-900">Update status</h2>
                        <p class="mt-1 text-sm text-slate-500">Move the lead through your sales workflow.</p>

                        <form
                            method="POST"
                            action="{{ route('admin.leads.updateStatus', $lead) }}"
                            class="mt-5"
                            x-data="{ submitting: false }"
                            @submit="submitting = true"
                        >
                            @csrf
                            @method('PATCH')

                            <label for="status" class="block text-sm font-semibold text-slate-700">
                                Lead status
                            </label>

                            <select
                                id="status"
                                name="status"
                                class="mt-2 h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="new" @selected($lead->status === 'new')>New</option>
                                <option value="qualified" @selected($lead->status === 'qualified')>Qualified</option>
                                <option value="contacted" @selected($lead->status === 'contacted')>Contacted</option>
                                <option value="converted" @selected($lead->status === 'converted')>Converted</option>
                                <option value="closed" @selected($lead->status === 'closed')>Closed</option>
                            </select>

                            <button
                                type="submit"
                                :disabled="submitting"
                                class="mt-4 inline-flex h-11 w-full items-center justify-center rounded-xl bg-blue-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                <span x-text="submitting ? 'Saving…' : 'Save status'"></span>
                            </button>
                        </form>
                    </section>

                    <section class="grid grid-cols-2 gap-4">
                        <div class="rounded-2xl border border-slate-200 bg-white p-5 text-center shadow-sm">
                            <p class="text-3xl font-bold tracking-tight text-slate-900">{{ $score }}</p>
                            <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-slate-400">Lead score</p>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-white p-5 text-center shadow-sm">
                            <p class="text-3xl font-bold tracking-tight text-slate-900">
                                {{ $lead->conversation?->messages?->count() ?? 0 }}
                            </p>
                            <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-slate-400">Messages</p>
                        </div>
                    </section>
                </aside>

                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm xl:col-span-2">
                    <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                        <div>
                            <h2 class="text-base font-semibold text-slate-900">Conversation history</h2>
                            <p class="mt-1 text-sm text-slate-500">Messages associated with this lead.</p>
                        </div>

                        @if($lead->conversation)
                            <a
                                href="{{ route('admin.conversations.show', $lead->conversation) }}"
                                class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700"
                            >
                                Open conversation
                            </a>
                        @endif
                    </div>

                    @if($lead->conversation && $lead->conversation->messages->count())
                        <div class="max-h-[760px] space-y-4 overflow-y-auto bg-slate-50 p-4 sm:p-6">
                            @foreach($lead->conversation->messages as $message)
                                @php
                                    $isVisitor = $message->sender === 'visitor';
                                    $isAgent = $message->sender === 'agent';
                                    $isAi = $message->sender === 'ai';
                                    $isSystem = $message->sender === 'system';
                                @endphp

                                <div class="flex {{ $isVisitor ? 'justify-start' : (($isAgent || $isAi) ? 'justify-end' : 'justify-center') }}">
                                    @if($isSystem)
                                        <div class="max-w-xl rounded-full bg-white px-4 py-2 text-center text-xs font-medium text-slate-500 ring-1 ring-slate-200">
                                            {{ $message->message }}
                                        </div>
                                    @else
                                        <div class="max-w-[85%] sm:max-w-[75%]">
                                            <div class="mb-1.5 flex items-center gap-2 text-xs text-slate-400 {{ $isVisitor ? '' : 'justify-end' }}">
                                                <span>
                                                    @if($isVisitor)
                                                        Visitor
                                                    @elseif($isAgent)
                                                        {{ $message->user?->name ?? 'Agent' }}
                                                    @else
                                                        AI Assistant
                                                    @endif
                                                </span>

                                                <span>·</span>
                                                <span>{{ $message->created_at->format('d M Y, H:i') }}</span>
                                            </div>

                                            <div class="rounded-2xl px-4 py-3 text-sm leading-6 shadow-sm
                                                {{ $isVisitor
                                                    ? 'rounded-bl-md bg-white text-slate-800 ring-1 ring-slate-200'
                                                    : ($isAgent
                                                        ? 'rounded-br-md bg-emerald-600 text-white'
                                                        : 'rounded-br-md bg-blue-600 text-white')
                                                }}"
                                            >
                                                <div class="whitespace-pre-wrap break-words">{{ $message->message }}</div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="flex min-h-96 items-center justify-center px-6 py-12">
                            <div class="text-center">
                                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8M8 14h5m-9 5 2.4-2.4A8 8 0 1 1 20 11a8 8 0 0 1-8 8H4Z"/>
                                    </svg>
                                </div>

                                <h3 class="mt-4 text-sm font-semibold text-slate-900">
                                    No conversation found
                                </h3>

                                <p class="mt-1 text-sm text-slate-500">
                                    This lead does not currently have an associated conversation.
                                </p>
                            </div>
                        </div>
                    @endif
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
