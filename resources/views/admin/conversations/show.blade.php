@php
    $canReply = $conversation->mode === 'live'
        && (
            (int) $conversation->assigned_agent_id === (int) auth()->id()
            || auth()->user()->isSuperAdmin()
        );

    $modeLabel = match ($conversation->mode) {
        'live_waiting' => 'Waiting for agent',
        'live' => 'Live agent',
        default => 'AI assistant',
    };

    $modeClasses = match ($conversation->mode) {
        'live_waiting' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'live' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        default => 'bg-blue-50 text-blue-700 ring-blue-200',
    };
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2 text-sm text-slate-500">
                    <a href="{{ route('admin.conversations.index') }}" class="hover:text-blue-600">Conversations</a>
                    <span>/</span>
                    <span>#{{ $conversation->id }}</span>
                </div>

                <div class="mt-2 flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
                        Conversation #{{ $conversation->id }}
                    </h1>

                    <span
                        id="conversation-mode-badge"
                        class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $modeClasses }}"
                    >
                        {{ $modeLabel }}
                    </span>
                </div>

                <p class="mt-1 text-sm text-slate-500">
                    {{ $conversation->website?->name ?? 'Unknown website' }}
                </p>
            </div>

            <a
                href="{{ route('admin.conversations.index') }}"
                class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
            >
                Back to conversations
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

            <div
                id="conversation-page"
                data-conversation-id="{{ $conversation->id }}"
                data-mode="{{ $conversation->mode }}"
                data-assigned-agent-id="{{ $conversation->assigned_agent_id }}"
                data-current-user-id="{{ auth()->id() }}"
                data-realtime-token="{{ $conversation->realtime_token }}"
                class="grid grid-cols-1 gap-6 xl:grid-cols-3"
            >
                <aside class="space-y-6 xl:col-span-1">
                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h2 class="text-base font-semibold text-slate-900">Conversation details</h2>
                                <p class="mt-1 text-sm text-slate-500">Context and ownership</p>
                            </div>

                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-500">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8M8 14h5m-9 5 2.4-2.4A8 8 0 1 1 20 11a8 8 0 0 1-8 8H4Z"/>
                                </svg>
                            </div>
                        </div>

                        <dl class="mt-6 space-y-4 text-sm">
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Visitor ID</dt>
                                <dd class="max-w-48 break-all text-right font-medium text-slate-800">{{ $conversation->visitor_id }}</dd>
                            </div>

                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Website</dt>
                                <dd class="text-right font-medium text-slate-800">{{ $conversation->website?->name ?? '—' }}</dd>
                            </div>

                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Status</dt>
                                <dd class="text-right font-medium text-slate-800">{{ ucfirst($conversation->status) }}</dd>
                            </div>

                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Lead stage</dt>
                                <dd class="text-right font-medium text-slate-800">
                                    {{ ucfirst(str_replace('_', ' ', $conversation->lead_stage ?? 'discovery')) }}
                                </dd>
                            </div>

                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Assigned agent</dt>
                                <dd id="assigned-agent-name" class="text-right font-medium text-slate-800">
                                    {{ $conversation->assignedAgent?->name ?? 'None' }}
                                </dd>
                            </div>

                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Started</dt>
                                <dd class="text-right font-medium text-slate-800">{{ $conversation->created_at->format('d M Y, H:i') }}</dd>
                            </div>

                            @if($conversation->live_requested_at)
                                <div class="flex items-start justify-between gap-4">
                                    <dt class="text-slate-500">Live requested</dt>
                                    <dd class="text-right font-medium text-slate-800">{{ $conversation->live_requested_at->format('d M Y, H:i') }}</dd>
                                </div>
                            @endif
                        </dl>

                        <div id="conversation-actions" class="mt-6 space-y-3">
                            <button
                                type="button"
                                id="take-conversation-button"
                                class="inline-flex h-11 w-full items-center justify-center rounded-xl bg-emerald-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
                                @if($conversation->mode !== 'live_waiting') hidden @endif
                            >
                                Take conversation
                            </button>

                            <button
                                type="button"
                                id="close-live-chat-button"
                                class="inline-flex h-11 w-full items-center justify-center rounded-xl border border-red-200 bg-white px-4 text-sm font-semibold text-red-600 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-60"
                                @if(!$canReply) hidden @endif
                            >
                                End live chat
                            </button>
                        </div>
                    </section>

                    @if($conversation->lead)
                        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h2 class="text-base font-semibold text-slate-900">Lead information</h2>
                                    <p class="mt-1 text-sm text-slate-500">Captured prospect details</p>
                                </div>

                                <span class="rounded-full bg-violet-50 px-2.5 py-1 text-xs font-semibold text-violet-700">
                                    Lead #{{ $conversation->lead->id }}
                                </span>
                            </div>

                            <dl class="mt-6 space-y-4 text-sm">
                                <div>
                                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Name</dt>
                                    <dd class="mt-1 font-medium text-slate-800">{{ $conversation->lead->name ?? '—' }}</dd>
                                </div>

                                <div>
                                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Email</dt>
                                    <dd class="mt-1 break-all font-medium text-slate-800">{{ $conversation->lead->email ?? '—' }}</dd>
                                </div>

                                <div>
                                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Phone</dt>
                                    <dd class="mt-1 font-medium text-slate-800">{{ $conversation->lead->phone ?? '—' }}</dd>
                                </div>

                                <div>
                                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Product interest</dt>
                                    <dd class="mt-1 font-medium text-slate-800">{{ $conversation->lead->product_interest ?? '—' }}</dd>
                                </div>
                            </dl>

                            <a
                                href="{{ route('admin.leads.show', $conversation->lead) }}"
                                class="mt-6 inline-flex h-10 w-full items-center justify-center rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                            >
                                View full lead
                            </a>
                        </section>
                    @endif
                </aside>

                <section class="flex min-h-[680px] flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm xl:col-span-2">
                    <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                        <div>
                            <h2 class="text-base font-semibold text-slate-900">Messages</h2>

                            <p id="realtime-status" class="mt-1 inline-flex items-center gap-2 text-xs text-slate-500">
                                <span class="h-2 w-2 rounded-full bg-slate-300"></span>
                                Connecting to real-time updates...
                            </p>
                        </div>

                        <div class="text-xs text-slate-500">
                            {{ $conversation->messages->count() }} messages
                        </div>
                    </div>

                    <div
                        id="conversation-messages"
                        class="flex-1 space-y-4 overflow-y-auto bg-slate-50 p-4 sm:p-6"
                    >
                        @forelse($conversation->messages as $message)
                            @php
                                $isVisitor = $message->sender === 'visitor';
                                $isAgent = $message->sender === 'agent';
                                $isAi = $message->sender === 'ai';
                                $isSystem = $message->sender === 'system';
                            @endphp

                            <div
                                data-message-id="{{ $message->id }}"
                                class="conversation-message flex {{ $isVisitor ? 'justify-start' : (($isAgent || $isAi) ? 'justify-end' : 'justify-center') }}"
                            >
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
                                            <span>{{ $message->created_at->format('H:i') }}</span>
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
                        @empty
                            <div id="empty-messages" class="flex h-full min-h-80 items-center justify-center">
                                <div class="text-center">
                                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-white text-slate-400 ring-1 ring-slate-200">
                                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8M8 14h5m-9 5 2.4-2.4A8 8 0 1 1 20 11a8 8 0 0 1-8 8H4Z"/>
                                        </svg>
                                    </div>

                                    <p class="mt-4 text-sm font-semibold text-slate-900">No messages yet</p>
                                    <p class="mt-1 text-sm text-slate-500">Messages will appear here in real time.</p>
                                </div>
                            </div>
                        @endforelse
                    </div>

                    <div
                        id="conversation-error"
                        class="mx-4 mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 sm:mx-6"
                        hidden
                    ></div>

                    <div
                        id="live-waiting-notice"
                        class="border-t border-amber-200 bg-amber-50 px-4 py-4 text-center text-sm font-medium text-amber-800"
                        @if($conversation->mode !== 'live_waiting') hidden @endif
                    >
                        The visitor is waiting for a live agent. Take the conversation to reply.
                    </div>

                    <div
                        id="ai-mode-notice"
                        class="border-t border-slate-200 bg-white px-4 py-4 text-center text-sm text-slate-500"
                        @if($conversation->mode !== 'ai') hidden @endif
                    >
                        The AI assistant is currently handling this conversation.
                    </div>

                    <form
                        id="agent-message-form"
                        class="border-t border-slate-200 bg-white p-4 sm:p-5"
                        @if(!$canReply) hidden @endif
                    >
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                            <div class="min-w-0 flex-1">
                                <label for="agent-message-input" class="sr-only">Message</label>

                                <textarea
                                    id="agent-message-input"
                                    rows="3"
                                    maxlength="5000"
                                    placeholder="Type your reply…"
                                    class="block w-full resize-none rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    required
                                ></textarea>

                                <p class="mt-2 text-xs text-slate-400">
                                    Press Enter to send. Use Shift + Enter for a new line.
                                </p>
                            </div>

                            <button
                                type="submit"
                                id="agent-message-submit"
                                class="inline-flex h-11 shrink-0 items-center justify-center rounded-xl bg-blue-600 px-6 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                Send message
                            </button>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const page = document.getElementById('conversation-page');

        if (!page) {
            return;
        }

        const currentUserId = Number(page.dataset.currentUserId);
        const realtimeToken = page.dataset.realtimeToken;

        let mode = page.dataset.mode;
        let assignedAgentId = page.dataset.assignedAgentId
            ? Number(page.dataset.assignedAgentId)
            : null;

        const messagesContainer = document.getElementById('conversation-messages');
        const form = document.getElementById('agent-message-form');
        const input = document.getElementById('agent-message-input');
        const submitButton = document.getElementById('agent-message-submit');
        const takeButton = document.getElementById('take-conversation-button');
        const closeButton = document.getElementById('close-live-chat-button');
        const modeBadge = document.getElementById('conversation-mode-badge');
        const assignedAgentName = document.getElementById('assigned-agent-name');
        const waitingNotice = document.getElementById('live-waiting-notice');
        const aiNotice = document.getElementById('ai-mode-notice');
        const errorBox = document.getElementById('conversation-error');
        const realtimeStatus = document.getElementById('realtime-status');

        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content');

        function escapeHtml(value) {
            const element = document.createElement('div');
            element.textContent = value ?? '';
            return element.innerHTML;
        }

        function showError(message) {
            if (!errorBox) return;
            errorBox.textContent = message;
            errorBox.hidden = false;
        }

        function clearError() {
            if (!errorBox) return;
            errorBox.textContent = '';
            errorBox.hidden = true;
        }

        function messageExists(messageId) {
            if (!messageId || !messagesContainer) {
                return false;
            }

            return Boolean(
                messagesContainer.querySelector(
                    `[data-message-id="${messageId}"]`
                )
            );
        }

        function scrollToBottom() {
            if (!messagesContainer) return;
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        function formatTime(value) {
            if (!value) return '';

            return new Date(value).toLocaleTimeString([], {
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function appendMessage(message) {
            if (!message || messageExists(message.id) || !messagesContainer) {
                return;
            }

            document.getElementById('empty-messages')?.remove();

            const row = document.createElement('div');
            row.dataset.messageId = message.id || '';

            const sender = message.sender || 'system';
            const isVisitor = sender === 'visitor';
            const isAgent = sender === 'agent';
            const isAi = sender === 'ai';
            const isSystem = sender === 'system';

            row.className =
                'conversation-message flex ' +
                (isVisitor
                    ? 'justify-start'
                    : ((isAgent || isAi) ? 'justify-end' : 'justify-center'));

            if (isSystem) {
                row.innerHTML = `
                    <div class="max-w-xl rounded-full bg-white px-4 py-2 text-center text-xs font-medium text-slate-500 ring-1 ring-slate-200">
                        ${escapeHtml(message.message)}
                    </div>
                `;
            } else {
                const senderLabel = isVisitor
                    ? 'Visitor'
                    : (isAgent ? (message.agent_name || 'Agent') : 'AI Assistant');

                const bubbleClass = isVisitor
                    ? 'rounded-bl-md bg-white text-slate-800 ring-1 ring-slate-200'
                    : (isAgent
                        ? 'rounded-br-md bg-emerald-600 text-white'
                        : 'rounded-br-md bg-blue-600 text-white');

                row.innerHTML = `
                    <div class="max-w-[85%] sm:max-w-[75%]">
                        <div class="mb-1.5 flex items-center gap-2 text-xs text-slate-400 ${isVisitor ? '' : 'justify-end'}">
                            <span>${escapeHtml(senderLabel)}</span>
                            <span>·</span>
                            <span>${escapeHtml(formatTime(message.created_at))}</span>
                        </div>

                        <div class="rounded-2xl px-4 py-3 text-sm leading-6 shadow-sm ${bubbleClass}">
                            <div class="whitespace-pre-wrap break-words">
                                ${escapeHtml(message.message)}
                            </div>
                        </div>
                    </div>
                `;
            }

            messagesContainer.appendChild(row);
            scrollToBottom();
        }

        function setModeBadge(newMode) {
            if (!modeBadge) return;

            modeBadge.className =
                'inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1';

            if (newMode === 'live_waiting') {
                modeBadge.textContent = 'Waiting for agent';
                modeBadge.classList.add(
                    'bg-amber-50',
                    'text-amber-700',
                    'ring-amber-200'
                );
            } else if (newMode === 'live') {
                modeBadge.textContent = 'Live agent';
                modeBadge.classList.add(
                    'bg-emerald-50',
                    'text-emerald-700',
                    'ring-emerald-200'
                );
            } else {
                modeBadge.textContent = 'AI assistant';
                modeBadge.classList.add(
                    'bg-blue-50',
                    'text-blue-700',
                    'ring-blue-200'
                );
            }
        }

        function updateModeUi(newMode, payload = {}) {
            mode = newMode;
            page.dataset.mode = newMode;

            if (payload.assigned_agent_id !== undefined) {
                assignedAgentId = payload.assigned_agent_id
                    ? Number(payload.assigned_agent_id)
                    : null;
            }

            setModeBadge(newMode);

            if (newMode === 'live_waiting') {
                if (takeButton) takeButton.hidden = false;
                if (closeButton) closeButton.hidden = true;
                if (form) form.hidden = true;
                if (waitingNotice) waitingNotice.hidden = false;
                if (aiNotice) aiNotice.hidden = true;
            } else if (newMode === 'live') {
                if (takeButton) takeButton.hidden = true;
                if (waitingNotice) waitingNotice.hidden = true;
                if (aiNotice) aiNotice.hidden = true;

                const canReply = assignedAgentId === currentUserId;

                if (form) form.hidden = !canReply;
                if (closeButton) closeButton.hidden = !canReply;
            } else {
                if (takeButton) takeButton.hidden = true;
                if (closeButton) closeButton.hidden = true;
                if (form) form.hidden = true;
                if (waitingNotice) waitingNotice.hidden = true;
                if (aiNotice) aiNotice.hidden = false;

                assignedAgentId = null;

                if (assignedAgentName) {
                    assignedAgentName.textContent = 'None';
                }
            }
        }

        async function postJson(url, body = {}) {
            const response = await fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(body)
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(
                    data.message || `Request failed (${response.status})`
                );
            }

            return data;
        }

        takeButton?.addEventListener('click', async function () {
            clearError();

            takeButton.disabled = true;
            takeButton.textContent = 'Taking conversation…';

            try {
                const result = await postJson(
                    @json(route('admin.conversations.take', $conversation))
                );

                assignedAgentId = Number(result.assigned_agent.id);

                if (assignedAgentName) {
                    assignedAgentName.textContent = result.assigned_agent.name;
                }

                updateModeUi('live', {
                    assigned_agent_id: result.assigned_agent.id
                });

                input?.focus();
            } catch (error) {
                showError(error.message);
            } finally {
                takeButton.disabled = false;
                takeButton.textContent = 'Take conversation';
            }
        });

        form?.addEventListener('submit', async function (event) {
            event.preventDefault();
            clearError();

            const message = input?.value.trim();

            if (!message) {
                return;
            }

            input.disabled = true;
            submitButton.disabled = true;
            submitButton.textContent = 'Sending…';

            try {
                const result = await postJson(
                    @json(route('admin.conversations.messages.store', $conversation)),
                    { message }
                );

                appendMessage(result.message);
                input.value = '';
            } catch (error) {
                showError(error.message);
            } finally {
                input.disabled = false;
                submitButton.disabled = false;
                submitButton.textContent = 'Send message';
                input.focus();
            }
        });

        input?.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                form?.requestSubmit();
            }
        });

        closeButton?.addEventListener('click', async function () {
            const confirmed = window.confirm(
                'End live chat and return control to the AI assistant?'
            );

            if (!confirmed) {
                return;
            }

            clearError();

            closeButton.disabled = true;
            closeButton.textContent = 'Ending live chat…';

            try {
                await postJson(
                    @json(route('admin.conversations.closeLiveChat', $conversation))
                );

                updateModeUi('ai');
            } catch (error) {
                showError(error.message);
            } finally {
                closeButton.disabled = false;
                closeButton.textContent = 'End live chat';
            }
        });

        if (!window.Echo || !realtimeToken) {
            if (realtimeStatus) {
                realtimeStatus.innerHTML =
                    '<span class="h-2 w-2 rounded-full bg-red-500"></span> Real-time connection unavailable';
                realtimeStatus.className =
                    'mt-1 inline-flex items-center gap-2 text-xs text-red-600';
            }

            scrollToBottom();
            return;
        }

        const channel = window.Echo.channel(
            `conversation.${realtimeToken}`
        );

        channel.listen(
            '.conversation.message.created',
            function (event) {
                appendMessage(event);
            }
        );

        channel.listen(
            '.conversation.mode.changed',
            function (event) {
                updateModeUi(event.mode, event);

                if (event.assigned_agent_name && assignedAgentName) {
                    assignedAgentName.textContent =
                        event.assigned_agent_name;
                }
            }
        );

        if (realtimeStatus) {
            realtimeStatus.innerHTML =
                '<span class="h-2 w-2 rounded-full bg-emerald-500"></span> Real-time updates active';
            realtimeStatus.className =
                'mt-1 inline-flex items-center gap-2 text-xs text-emerald-600';
        }

        scrollToBottom();
    });
    </script>
</x-app-layout>
