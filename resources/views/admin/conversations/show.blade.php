<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Conversation #{{ $conversation->id }}
                </h2>

                <p class="text-sm text-gray-500 mt-1">
                    {{ $conversation->website?->name }}
                </p>
            </div>

            <a
                href="{{ route('admin.conversations.index') }}"
                class="text-sm text-blue-600"
            >
                Back to Conversations
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 rounded bg-green-100 text-green-800 px-4 py-3">
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
                class="grid grid-cols-1 lg:grid-cols-3 gap-6"
            >
                <aside class="bg-white rounded shadow p-6 h-fit">
                    <div class="flex items-center justify-between mb-5">
                        <h3 class="font-semibold text-lg">
                            Conversation
                        </h3>

                        <span
                            id="conversation-mode-badge"
                            class="rounded-full px-3 py-1 text-xs font-semibold
                                @if($conversation->mode === 'live_waiting')
                                    bg-yellow-100 text-yellow-800
                                @elseif($conversation->mode === 'live')
                                    bg-green-100 text-green-800
                                @else
                                    bg-gray-100 text-gray-700
                                @endif
                            "
                        >
                            @if($conversation->mode === 'live_waiting')
                                Waiting for Agent
                            @elseif($conversation->mode === 'live')
                                Live Agent
                            @else
                                AI
                            @endif
                        </span>
                    </div>

                    <div class="space-y-3 text-sm">
                        <div>
                            <strong>Visitor ID:</strong>
                            {{ $conversation->visitor_id }}
                        </div>

                        <div>
                            <strong>Website:</strong>
                            {{ $conversation->website?->name ?? '-' }}
                        </div>

                        <div>
                            <strong>Status:</strong>
                            {{ ucfirst($conversation->status) }}
                        </div>

                        <div>
                            <strong>Lead Stage:</strong>
                            {{ $conversation->lead_stage }}
                        </div>

                        <div>
                            <strong>Assigned Agent:</strong>

                            <span id="assigned-agent-name">
                                {{ $conversation->assignedAgent?->name ?? 'None' }}
                            </span>
                        </div>

                        <div>
                            <strong>Started:</strong>
                            {{ $conversation->created_at->format('Y-m-d H:i') }}
                        </div>

                        @if($conversation->live_requested_at)
                            <div>
                                <strong>Live Requested:</strong>
                                {{ $conversation->live_requested_at->format('Y-m-d H:i') }}
                            </div>
                        @endif
                    </div>

                    <div
                        id="conversation-actions"
                        class="mt-6 space-y-3"
                    >
                        <button
                            type="button"
                            id="take-conversation-button"
                            class="w-full bg-green-600 text-white rounded px-4 py-2 font-semibold"
                            @if($conversation->mode !== 'live_waiting')
                                hidden
                            @endif
                        >
                            Take Conversation
                        </button>

                        <button
                            type="button"
                            id="close-live-chat-button"
                            class="w-full bg-red-600 text-white rounded px-4 py-2 font-semibold"
                            @if(
                                $conversation->mode !== 'live' ||
                                (
                                    (int) $conversation->assigned_agent_id !==
                                    (int) auth()->id() &&
                                    !auth()->user()->isSuperAdmin()
                                )
                            )
                                hidden
                            @endif
                        >
                            End Live Chat
                        </button>
                    </div>

                    @if($conversation->lead)
                        <hr class="my-6">

                        <h3 class="font-semibold text-lg mb-4">
                            Lead Information
                        </h3>

                        <div class="space-y-3 text-sm">
                            <div>
                                <strong>Name:</strong>
                                {{ $conversation->lead->name ?? '-' }}
                            </div>

                            <div>
                                <strong>Email:</strong>
                                {{ $conversation->lead->email ?? '-' }}
                            </div>

                            <div>
                                <strong>Phone:</strong>
                                {{ $conversation->lead->phone ?? '-' }}
                            </div>

                            <div>
                                <strong>Interest:</strong>
                                {{
                                    $conversation
                                        ->lead
                                        ->product_interest
                                    ?? '-'
                                }}
                            </div>

                            <a
                                href="{{ route('admin.leads.show', $conversation->lead) }}"
                                class="inline-block text-blue-600"
                            >
                                View Lead
                            </a>
                        </div>
                    @endif
                </aside>

                <section class="lg:col-span-2 bg-white rounded shadow flex flex-col min-h-[650px]">
                    <div class="border-b px-6 py-4">
                        <h3 class="font-semibold text-lg">
                            Messages
                        </h3>

                        <p
                            id="realtime-status"
                            class="text-xs text-gray-500 mt-1"
                        >
                            Connecting to real-time updates...
                        </p>
                    </div>

                    <div
                        id="conversation-messages"
                        class="flex-1 overflow-y-auto p-6 space-y-4 max-h-[560px] bg-gray-50"
                    >
                        @forelse($conversation->messages as $message)
                            <div
                                data-message-id="{{ $message->id }}"
                                class="conversation-message flex
                                    @if($message->sender === 'visitor')
                                        justify-start
                                    @elseif(in_array($message->sender, ['agent', 'ai']))
                                        justify-end
                                    @else
                                        justify-center
                                    @endif
                                "
                            >
                                <div
                                    class="
                                        @if($message->sender === 'system')
                                            text-center text-gray-500 text-xs px-4 py-2
                                        @else
                                            max-w-[80%] rounded-xl px-4 py-3 shadow-sm
                                            @if($message->sender === 'visitor')
                                                bg-blue-100 text-gray-900
                                            @elseif($message->sender === 'agent')
                                                bg-green-600 text-white
                                            @else
                                                bg-white text-gray-900
                                            @endif
                                        @endif
                                    "
                                >
                                    @if($message->sender !== 'system')
                                        <div class="text-xs opacity-70 mb-1">
                                            @if($message->sender === 'visitor')
                                                Visitor
                                            @elseif($message->sender === 'agent')
                                                {{ $message->user?->name ?? 'Agent' }}
                                            @else
                                                AI Assistant
                                            @endif

                                            ·

                                            {{ $message->created_at->format('H:i') }}
                                        </div>
                                    @endif

                                    <div class="whitespace-pre-wrap break-words">
                                        {{ $message->message }}
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p
                                id="empty-messages"
                                class="text-center text-gray-500"
                            >
                                No messages found.
                            </p>
                        @endforelse
                    </div>

                    <div
                        id="conversation-error"
                        class="mx-6 mt-4 rounded bg-red-100 text-red-800 px-4 py-3"
                        hidden
                    ></div>

                    <form
                        id="agent-message-form"
                        class="border-t p-4 flex gap-3"
                        @if(
                            $conversation->mode !== 'live' ||
                            (
                                (int) $conversation->assigned_agent_id !==
                                (int) auth()->id() &&
                                !auth()->user()->isSuperAdmin()
                            )
                        )
                            hidden
                        @endif
                    >
                        <textarea
                            id="agent-message-input"
                            rows="2"
                            maxlength="5000"
                            placeholder="Type your reply..."
                            class="flex-1 border-gray-300 rounded-lg resize-none"
                            required
                        ></textarea>

                        <button
                            type="submit"
                            id="agent-message-submit"
                            class="bg-blue-600 text-white rounded-lg px-6 font-semibold"
                        >
                            Send
                        </button>
                    </form>

                    <div
                        id="live-waiting-notice"
                        class="border-t p-4 text-center text-sm text-yellow-700 bg-yellow-50"
                        @if($conversation->mode !== 'live_waiting')
                            hidden
                        @endif
                    >
                        The visitor is waiting for a live agent.
                        Take the conversation to reply.
                    </div>

                    <div
                        id="ai-mode-notice"
                        class="border-t p-4 text-center text-sm text-gray-500"
                        @if($conversation->mode !== 'ai')
                            hidden
                        @endif
                    >
                        The AI assistant is currently handling this conversation.
                    </div>
                </section>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const page = document.getElementById('conversation-page');

        const conversationId =
            Number(page.dataset.conversationId);

        const currentUserId =
            Number(page.dataset.currentUserId);

        const realtimeToken =
            page.dataset.realtimeToken;

        let mode = page.dataset.mode;

        let assignedAgentId =
            page.dataset.assignedAgentId
                ? Number(page.dataset.assignedAgentId)
                : null;

        const messagesContainer =
            document.getElementById('conversation-messages');

        const form =
            document.getElementById('agent-message-form');

        const input =
            document.getElementById('agent-message-input');

        const submitButton =
            document.getElementById('agent-message-submit');

        const takeButton =
            document.getElementById('take-conversation-button');

        const closeButton =
            document.getElementById('close-live-chat-button');

        const modeBadge =
            document.getElementById('conversation-mode-badge');

        const assignedAgentName =
            document.getElementById('assigned-agent-name');

        const waitingNotice =
            document.getElementById('live-waiting-notice');

        const aiNotice =
            document.getElementById('ai-mode-notice');

        const errorBox =
            document.getElementById('conversation-error');

        const realtimeStatus =
            document.getElementById('realtime-status');

        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content');

        function escapeHtml(value) {
            const element = document.createElement('div');
            element.textContent = value ?? '';
            return element.innerHTML;
        }

        function showError(message) {
            errorBox.textContent = message;
            errorBox.hidden = false;
        }

        function clearError() {
            errorBox.textContent = '';
            errorBox.hidden = true;
        }

        function messageExists(messageId) {
            if (!messageId) {
                return false;
            }

            return Boolean(
                messagesContainer.querySelector(
                    `[data-message-id="${messageId}"]`
                )
            );
        }

        function scrollToBottom() {
            messagesContainer.scrollTop =
                messagesContainer.scrollHeight;
        }

        function formatTime(value) {
            if (!value) {
                return '';
            }

            return new Date(value).toLocaleTimeString([], {
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function appendMessage(message) {
            if (
                !message ||
                messageExists(message.id)
            ) {
                return;
            }

            document
                .getElementById('empty-messages')
                ?.remove();

            const row = document.createElement('div');

            row.dataset.messageId = message.id || '';

            let alignment = 'justify-center';
            let bubbleClass =
                'text-center text-gray-500 text-xs px-4 py-2';

            if (message.sender === 'visitor') {
                alignment = 'justify-start';
                bubbleClass =
                    'max-w-[80%] rounded-xl px-4 py-3 shadow-sm bg-blue-100 text-gray-900';
            } else if (message.sender === 'agent') {
                alignment = 'justify-end';
                bubbleClass =
                    'max-w-[80%] rounded-xl px-4 py-3 shadow-sm bg-green-600 text-white';
            } else if (message.sender === 'ai') {
                alignment = 'justify-end';
                bubbleClass =
                    'max-w-[80%] rounded-xl px-4 py-3 shadow-sm bg-white text-gray-900';
            }

            row.className =
                `conversation-message flex ${alignment}`;

            const senderLabel =
                message.sender === 'visitor'
                    ? 'Visitor'
                    : message.sender === 'agent'
                        ? message.agent_name || 'Agent'
                        : message.sender === 'ai'
                            ? 'AI Assistant'
                            : 'System';

            const meta =
                message.sender === 'system'
                    ? ''
                    : `
                        <div class="text-xs opacity-70 mb-1">
                            ${escapeHtml(senderLabel)}
                            ·
                            ${escapeHtml(formatTime(message.created_at))}
                        </div>
                    `;

            row.innerHTML = `
                <div class="${bubbleClass}">
                    ${meta}

                    <div class="whitespace-pre-wrap break-words">
                        ${escapeHtml(message.message)}
                    </div>
                </div>
            `;

            messagesContainer.appendChild(row);

            scrollToBottom();
        }

        function updateModeUi(newMode, payload = {}) {
            mode = newMode;
            page.dataset.mode = newMode;

            if (payload.assigned_agent_id !== undefined) {
                assignedAgentId =
                    payload.assigned_agent_id
                        ? Number(payload.assigned_agent_id)
                        : null;
            }

            modeBadge.className =
                'rounded-full px-3 py-1 text-xs font-semibold';

            if (newMode === 'live_waiting') {
                modeBadge.textContent = 'Waiting for Agent';

                modeBadge.classList.add(
                    'bg-yellow-100',
                    'text-yellow-800'
                );

                takeButton.hidden = false;
                closeButton.hidden = true;
                form.hidden = true;
                waitingNotice.hidden = false;
                aiNotice.hidden = true;
            } else if (newMode === 'live') {
                modeBadge.textContent = 'Live Agent';

                modeBadge.classList.add(
                    'bg-green-100',
                    'text-green-800'
                );

                takeButton.hidden = true;
                waitingNotice.hidden = true;
                aiNotice.hidden = true;

                const canReply =
                    assignedAgentId === currentUserId;

                form.hidden = !canReply;
                closeButton.hidden = !canReply;
            } else {
                modeBadge.textContent = 'AI';

                modeBadge.classList.add(
                    'bg-gray-100',
                    'text-gray-700'
                );

                takeButton.hidden = true;
                closeButton.hidden = true;
                form.hidden = true;
                waitingNotice.hidden = true;
                aiNotice.hidden = false;

                assignedAgentId = null;
                assignedAgentName.textContent = 'None';
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

            const data = await response
                .json()
                .catch(() => ({}));

            if (!response.ok) {
                throw new Error(
                    data.message ||
                    `Request failed (${response.status})`
                );
            }

            return data;
        }

        takeButton.addEventListener('click', async function () {
            clearError();

            takeButton.disabled = true;
            takeButton.textContent = 'Taking...';

            try {
                const result = await postJson(
                    @json(route('admin.conversations.take', $conversation))
                );

                assignedAgentId =
                    Number(result.assigned_agent.id);

                assignedAgentName.textContent =
                    result.assigned_agent.name;

                updateModeUi('live', {
                    assigned_agent_id:
                        result.assigned_agent.id
                });

                input.focus();
            } catch (error) {
                showError(error.message);
            } finally {
                takeButton.disabled = false;
                takeButton.textContent =
                    'Take Conversation';
            }
        });

        form.addEventListener('submit', async function (event) {
            event.preventDefault();

            clearError();

            const message = input.value.trim();

            if (!message) {
                return;
            }

            input.disabled = true;
            submitButton.disabled = true;
            submitButton.textContent = 'Sending...';

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
                submitButton.textContent = 'Send';
                input.focus();
            }
        });

        input.addEventListener('keydown', function (event) {
            if (
                event.key === 'Enter' &&
                !event.shiftKey
            ) {
                event.preventDefault();
                form.requestSubmit();
            }
        });

        closeButton.addEventListener('click', async function () {
            const confirmed = window.confirm(
                'End live chat and return control to the AI assistant?'
            );

            if (!confirmed) {
                return;
            }

            clearError();

            closeButton.disabled = true;
            closeButton.textContent = 'Ending...';

            try {
                await postJson(
                    @json(route('admin.conversations.closeLiveChat', $conversation))
                );

                updateModeUi('ai');
            } catch (error) {
                showError(error.message);
            } finally {
                closeButton.disabled = false;
                closeButton.textContent = 'End Live Chat';
            }
        });

        if (!window.Echo) {
            realtimeStatus.textContent =
                'Real-time connection unavailable.';

            realtimeStatus.className =
                'text-xs text-red-600 mt-1';

            console.error(
                'window.Echo is not initialized.'
            );

            scrollToBottom();
            return;
        }

        const channelName =
            `conversation.${realtimeToken}`;

        const channel =
            window.Echo.channel(channelName);

        channel.listen(
            '.conversation.message.created',
            function (event) {
                appendMessage(event);
            }
        );

        channel.listen(
            '.conversation.mode.changed',
            function (event) {
                updateModeUi(
                    event.mode,
                    event
                );

                if (event.assigned_agent_name) {
                    assignedAgentName.textContent =
                        event.assigned_agent_name;
                }
            }
        );

        realtimeStatus.textContent =
            'Real-time updates active.';

        realtimeStatus.className =
            'text-xs text-green-600 mt-1';

        scrollToBottom();
    });
    </script>
</x-app-layout>