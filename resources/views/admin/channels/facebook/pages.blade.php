<x-app-layout><x-slot name="header"><h1 class="text-xl font-semibold">Choose a Facebook Page</h1></x-slot>
<div class="mx-auto max-w-3xl space-y-6 px-4 py-8">@include('admin.channels.facebook.messages')
<form class="cn-panel space-y-4" method="POST" action="{{ route('admin.channels.facebook.connect', $grant->id) }}">@csrf
<fieldset><legend>Which Page should receive messages in this workspace?</legend>
@foreach($pages as $page)<label class="block rounded-xl border p-4 my-3"><input type="radio" name="page_id" value="{{ $page['id'] }}" required @checked($loop->first)> <strong>{{ $page['name'] }}</strong><span class="block text-sm text-slate-500">Page ID: {{ $page['id'] }}</span></label>@endforeach
</fieldset><button class="cn-button">Connect selected Page</button><p>Not seeing your Page? Start again and grant access to it in Facebook. This connection session expires after 15 minutes.</p>
<a href="{{ route('admin.channels.facebook.create', ['tenant_id' => $grant->tenant_id]) }}">Start again</a></form></div></x-app-layout>
