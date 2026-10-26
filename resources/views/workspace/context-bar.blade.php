@php
    extract(require resource_path('views/workspace/context.php'), EXTR_OVERWRITE);
@endphp
<div class="cn-topline"><strong>{{ $workspaceSuper ? 'System workspace' : 'Customer workspace' }}</strong><span>{{ $workspaceRole }} · {{ $workspaceUser->name }}</span></div>
@if($workspaceManager && request()->routeIs('admin.channels.*', 'admin.websites.*', 'admin.knowledge.*'))
    <nav class="cn-subnav" aria-label="Channel navigation">
        <a href="{{ route('admin.channels.index') }}" @if(request()->routeIs('admin.channels.index')) aria-current="page" @endif>All channels</a>
        <a href="{{ route('admin.websites.index') }}" @if(request()->routeIs('admin.websites.*', 'admin.knowledge.*')) aria-current="page" @endif>Website chat</a>
        <a href="{{ route('admin.channels.index', ['type' => 'whatsapp']) }}" @if(request()->routeIs('admin.channels.whatsapp.*')) aria-current="page" @endif>WhatsApp</a>
        <a href="{{ route('admin.channels.index') }}#channel-catalog">Add a channel</a>
    </nav>
@elseif($workspaceManager && request()->routeIs('admin.ai-agents.*', 'admin.knowledge-hub.*'))
    <nav class="cn-subnav" aria-label="AI configuration navigation">
        <a href="{{ route('admin.ai-agents.index') }}" @if(request()->routeIs('admin.ai-agents.*')) aria-current="page" @endif>AI assistants</a>
        <a href="{{ route('admin.knowledge-hub.index') }}" @if(request()->routeIs('admin.knowledge-hub.*')) aria-current="page" @endif>Knowledge</a>
        <a href="{{ route('admin.channels.index') }}">Channel connections</a>
    </nav>
@endif
@php($workspaceUpdate = config('app.latest_update', []))
@if(is_array($workspaceUpdate) && !empty($workspaceUpdate['enabled']) && !empty($workspaceUpdate['title']))
    <details class="cn-subnav"><summary>{{ $workspaceUpdate['title'] }}</summary>
        <p>{{ $workspaceUpdate['description'] ?? '' }}</p>
        @if(!empty($workspaceUpdate['url']) && preg_match('~^(https?://|/(?!/))~i', $workspaceUpdate['url']))
            <a href="{{ $workspaceUpdate['url'] }}">{{ $workspaceUpdate['link_text'] ?? 'Learn more' }}</a>
        @endif
    </details>
@endif
