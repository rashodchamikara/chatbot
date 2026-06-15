@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto py-6">
    <div class="flex justify-between items-center mb-5">
        <div>
            <h1 class="text-2xl font-bold">Live Chat</h1>
            <p class="text-sm text-gray-500">
                Website: {{ $conversation->website?->name }}
                | Visitor: {{ $conversation->visitor_id }}
            </p>
        </div>

        <div class="flex gap-2">
            @if($conversation->mode === 'live_waiting')
                <form method="POST" action="{{ route('admin.live-chat.take', $conversation) }}">
                    @csrf
                    <button class="px-4 py-2 bg-green-600 text-white rounded">
                        Take Chat
                    </button>
                </form>
            @endif

            <form method="POST" action="{{ route('admin.live-chat.close', $conversation) }}">
                @csrf
                <button class="px-4 py-2 bg-red-600 text-white rounded">
                    Close Live Chat
                </button>
            </form>
        </div>
    </div>

    <div
        id="live-chat-messages"
        class="bg-gray-50 border rounded-lg p-4 h-[520px] overflow-y-auto"
        data-channel="conversation.{{ $conversation->realtime_token }}"
    >
        @foreach($messages as $message)
            <div class="mb-3 {{ $message->sender === 'agent' ? 'text-right' : 'text-left' }}" data-message-id="{{ $message->id }}">
                <div class="inline-block max-w-[75%] px-4 py-2 rounded-lg
                    @if($message->sender === 'agent')
                        bg-blue-600 text-white
                    @elseif($message->sender === 'visitor')
                        bg-white border
                    @elseif($message->sender === 'ai')
                        bg-purple-50 border border-purple-100
                    @else
                        bg-yellow-50 border border-yellow-100 text-yellow-800 text-xs
                    @endif"
                >
                    @if($message->sender === 'agent')
                        <div class="text-xs opacity-80 mb-1">
                            {{ $message->user?->name ?? 'Agent' }}
                        </div>
                    @endif

                    <div class="whitespace-pre-line">
                        {{ $message->message }}
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <form id="agent-message-form" class="mt-4 flex gap-2">
        @csrf

        <input
            id="agent-message-input"
            type="text"
            class="flex-1 border rounded px-4 py-3"
            placeholder="Type your reply..."
            autocomplete="off"
        >

        <button type="submit" class="px-5 py-3 bg-blue-600 text-white rounded">
            Send
        </button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const messagesBox = document.getElementById('live-chat-messages');
    const form = document.getElementById('agent-message-form');
    const input = document.getElementById('agent-message-input');
    const channelName = messagesBox.dataset.channel;

    function scrollBottom() {
        messagesBox.scrollTop = messagesBox.scrollHeight;
    }

    function appendMessage(message) {
        if (document.querySelector(`[data-message-id="${message.id}"]`)) {
            return;
        }

        const wrapper = document.createElement('div');
        wrapper.className = 'mb-3 ' + (message.sender === 'agent' ? 'text-right' : 'text-left');
        wrapper.dataset.messageId = message.id;

        let classes = 'inline-block max-w-[75%] px-4 py-2 rounded-lg ';

        if (message.sender === 'agent') {
            classes += 'bg-blue-600 text-white';
        } else if (message.sender === 'visitor') {
            classes += 'bg-white border';
        } else if (message.sender === 'ai') {
            classes += 'bg-purple-50 border border-purple-100';
        } else {
            classes += 'bg-yellow-50 border border-yellow-100 text-yellow-800 text-xs';
        }

        wrapper.innerHTML = `
            <div class="${classes}">
                ${message.sender === 'agent' && message.agent_name ? `<div class="text-xs opacity-80 mb-1">${escapeHtml(message.agent_name)}</div>` : ''}
                <div class="whitespace-pre-line">${escapeHtml(message.message)}</div>
            </div>
        `;

        messagesBox.appendChild(wrapper);
        scrollBottom();
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&lt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    if (window.Echo) {
        window.Echo.channel(channelName)
            .listen('.conversation.message.created', function (event) {
                appendMessage(event);
            })
            .listen('.conversation.mode.changed', function (event) {
                console.log('Conversation mode changed', event);
            });
    } else {
        console.error('Laravel Echo is not loaded.');
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        const message = input.value.trim();

        if (!message) {
            return;
        }

        input.value = '';

        await fetch("{{ route('admin.live-chat.message', $conversation) }}", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                "Content-Type": "application/json",
                "Accept": "application/json"
            },
            body: JSON.stringify({
                message: message
            })
        });
    });

    scrollBottom();
});
</script>
@endsection