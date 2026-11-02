<x-app-layout>
<x-slot name="header"><h1 class="text-xl font-semibold">Connect Facebook Messenger</h1></x-slot>
<div class="mx-auto max-w-3xl space-y-6 px-4 py-8">
@include('admin.channels.facebook.messages')
<section class="cn-panel"><h2>One Page, one shared inbox</h2><p>Choose your AI assistant, continue to Facebook, then select the Page you want to connect. Repeat to add another Page.</p><p>Use the same assistant as your website or WhatsApp channel to share its existing knowledge.</p></section>
@if(auth()->user()->isSuperAdmin())
<form method="GET" class="cn-panel"><label for="tenant">Tenant workspace</label><select id="tenant" name="tenant_id" required><option value="">Choose tenant</option>@foreach($tenants as $tenant)<option value="{{ $tenant->id }}" @selected($tenantId === $tenant->id)>{{ $tenant->company_name ?: $tenant->name }}</option>@endforeach</select><button class="cn-button">Load assistants</button></form>
@endif
<form method="POST" action="{{ route('admin.channels.facebook.start') }}" class="cn-panel space-y-4">@csrf
<input type="hidden" name="tenant_id" value="{{ $tenantId }}">
<label for="assistant">AI assistant and shared knowledge</label><select id="assistant" name="ai_agent_id" required class="block w-full">@foreach($agents as $agent)<option value="{{ $agent->id }}">{{ $agent->name }}</option>@endforeach</select>
@if($agents->isEmpty())<p>Create an AI assistant in this workspace before connecting a Page.</p>@endif
@if(!config('messenger.enabled'))<p>The system administrator needs to configure Messenger before connections can be created.</p>@endif
<button class="cn-button" @disabled($agents->isEmpty() || !config('messenger.enabled'))>Continue with Facebook</button>
<p>Facebook will ask you to approve access. Only the Page you select in the next step is connected here. You need permission to manage its messages.</p>
</form><a class="cn-muted-link" href="{{ route('admin.channels.index') }}">Back to channels</a></div>
</x-app-layout>
