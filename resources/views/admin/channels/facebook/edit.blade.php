<x-app-layout><x-slot name="header"><h1 class="text-xl font-semibold">{{ $channel->name }} · Messenger</h1></x-slot>
<div class="mx-auto max-w-3xl space-y-6 px-4 py-8">@include('admin.channels.facebook.messages')
<section class="cn-panel"><h2>{{ ucfirst($channel->status) }}</h2><p>Page ID: {{ $channel->external_sender_id }}</p><p>Last message received: {{ $channel->last_webhook_at?->diffForHumans() ?? 'No messages yet' }}</p>@if($channel->last_error)<p role="alert">{{ $channel->last_error }}</p>@endif
<a class="cn-button" href="{{ route('admin.conversations.index', ['channel_type' => 'facebook']) }}">Open shared inbox</a></section>
<form class="cn-panel space-y-4" method="POST" action="{{ route('admin.channels.facebook.update', $channel) }}">@csrf @method('PATCH')
<label for="name">Connection name</label><input class="block w-full" id="name" name="name" required maxlength="191" value="{{ old('name', $channel->name) }}">
<label for="assistant">AI assistant and shared knowledge</label><select class="block w-full" name="ai_agent_id" id="assistant" required>@foreach($agents as $agent)<option value="{{ $agent->id }}" @selected((int) old('ai_agent_id', $channel->ai_agent_id) === $agent->id)>{{ $agent->name }}</option>@endforeach</select><p>Select the same assistant used by another channel to share its trained knowledge. New inbound messages use your selection.</p><button class="cn-button">Save settings</button></form>
<section class="cn-panel space-y-4"><h2>Connection controls</h2>
<form method="POST" action="{{ route('admin.channels.facebook.health', $channel) }}">@csrf<button class="cn-button">Check connection</button></form>
<a class="cn-muted-link" href="{{ route('admin.channels.facebook.create', ['tenant_id' => $channel->tenant_id]) }}">Reconnect with Facebook</a>
<form method="POST" action="{{ route('admin.channels.facebook.disconnect', $channel) }}">@csrf @method('DELETE')<button class="cn-button">Disconnect Page</button><p>Stops processing and sending here; conversation history and workspace ownership remain. Reconnect the same Page to restore service.</p></form></section>
<section class="cn-panel"><h2>Messaging limits</h2><p>Text replies are available for 24 hours after the customer’s latest message. Ask customers to send a new message to reopen this window. Customers can type “talk to agent” to enter the waiting queue. Attachment-only messages also go to the waiting queue.</p></section>
<a class="cn-muted-link" href="{{ route('admin.channels.index') }}">Back to channels</a></div></x-app-layout>
