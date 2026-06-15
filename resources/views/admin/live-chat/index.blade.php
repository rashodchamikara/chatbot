@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto py-6">
    <h1 class="text-2xl font-bold mb-6">Live Chat Inbox</h1>

    <div id="live-chat-alerts" class="mb-4"></div>

    <div class="bg-white border rounded-lg overflow-hidden">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50 border-b">
                    <th class="text-left p-3">Website</th>
                    <th class="text-left p-3">Visitor</th>
                    <th class="text-left p-3">Status</th>
                    <th class="text-left p-3">Assigned Agent</th>
                    <th class="text-left p-3">Requested</th>
                    <th class="text-right p-3">Action</th>
                </tr>
            </thead>

            <tbody id="live-chat-table-body">
                @forelse($conversations as $conversation)
                    <tr class="border-b" id="conversation-row-{{ $conversation->id }}">
                        <td class="p-3">{{ $conversation->website?->name }}</td>
                        <td class="p-3">{{ $conversation->visitor_id }}</td>
                        <td class="p-3">
                            {{ $conversation->mode === 'live_waiting' ? 'Waiting' : 'Live' }}
                        </td>
                        <td class="p-3">{{ $conversation->assignedAgent?->name ?? '-' }}</td>
                        <td class="p-3">{{ $conversation->live_requested_at?->diffForHumans() }}</td>
                        <td class="p-3 text-right">
                            <a href="{{ route('admin.live-chat.show', $conversation) }}" class="px-3 py-2 bg-blue-600 text-white rounded text-sm">
                                Open
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr id="no-live-chat-row">
                        <td colspan="6" class="p-6 text-center text-gray-500">
                            No live chat conversations right now.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!window.Echo) {
        console.error('Laravel Echo is not loaded.');
        return;
    }

    const tenantId = "{{ auth()->user()->tenant_id }}";
    const isSuperAdmin = {{ auth()->user()->isSuperAdmin() ? 'true' : 'false' }};

    if (!tenantId && !isSuperAdmin) {
        return;
    }

    const channelName = isSuperAdmin
        ? null
        : `tenant.${tenantId}.live`;

    if (!channelName) {
        return;
    }

    window.Echo.private(channelName)
        .listen('.live.agent.requested', function (event) {
            const alerts = document.getElementById('live-chat-alerts');

            alerts.innerHTML = `
                <div class="p-4 rounded bg-yellow-100 text-yellow-800 border border-yellow-200">
                    New live chat requested from ${event.website_name}.
                    <a href="${event.url}" class="underline font-semibold">Open chat</a>
                </div>
            `;

            const emptyRow = document.getElementById('no-live-chat-row');
            if (emptyRow) {
                emptyRow.remove();
            }

            const tbody = document.getElementById('live-chat-table-body');

            if (!document.getElementById('conversation-row-' + event.conversation_id)) {
                tbody.insertAdjacentHTML('afterbegin', `
                    <tr class="border-b" id="conversation-row-${event.conversation_id}">
                        <td class="p-3">${escapeHtml(event.website_name)}</td>
                        <td class="p-3">${escapeHtml(event.visitor_id)}</td>
                        <td class="p-3">Waiting</td>
                        <td class="p-3">-</td>
                        <td class="p-3">Just now</td>
                        <td class="p-3 text-right">
                            <a href="${event.url}" class="px-3 py-2 bg-blue-600 text-white rounded text-sm">
                                Open
                            </a>
                        </td>
                    </tr>
                `);
            }
        });

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
});
</script>
@endsection