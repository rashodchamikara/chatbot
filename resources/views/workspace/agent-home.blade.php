<x-slot name="header">
    <h1 class="text-2xl font-semibold text-slate-900">My workspace</h1>
    <p class="mt-1 text-sm text-slate-500">Conversations and customer follow-up in one place.</p>
</x-slot>
<div class="cn-work-area">
    <section class="cn-panel"><span class="cn-eyebrow" style="color:#126555">Daily work</span><h2>Welcome back, {{ auth()->user()->name }}</h2><p>Start with customers waiting for a human reply, then continue live conversations and follow up with leads.</p><div class="cn-quicklinks"><a class="cn-button" href="{{ route('admin.conversations.index', ['mode' => 'live_waiting']) }}">View waiting conversations</a><a class="cn-muted-link" href="{{ route('admin.conversations.index') }}">Open full inbox</a></div></section>
    <section class="cn-card-grid">
        <article class="cn-channel-card"><span class="cn-channel-mark">01</span><h3>Waiting for a reply</h3><p>Review conversations waiting for an agent and take over when needed.</p><a class="cn-button" href="{{ route('admin.conversations.index', ['mode' => 'live_waiting']) }}">View waiting</a></article>
        <article class="cn-channel-card"><span class="cn-channel-mark">02</span><h3>Live conversations</h3><p>Continue conversations being handled by your team.</p><a class="cn-button" href="{{ route('admin.conversations.index', ['mode' => 'live']) }}">View live</a></article>
        <article class="cn-channel-card"><span class="cn-channel-mark">03</span><h3>Customer leads</h3><p>Review captured customer details and update follow-up status.</p><a class="cn-button" href="{{ route('admin.leads.index') }}">Open leads</a></article>
    </section>
</div>
