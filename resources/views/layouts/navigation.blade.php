@php
    extract(require resource_path('views/workspace/context.php'), EXTR_OVERWRITE);
@endphp
@php
    $workspaceGroups = [
        'Daily work' => [
            ['dashboard', $workspaceManager ? 'Overview' : 'My workspace', ['dashboard']],
            ['admin.conversations.index', 'Inbox', ['admin.conversations.*']],
            ['admin.leads.index', 'Leads', ['admin.leads.*']],
        ],
    ];
    if ($workspaceManager) {
        $workspaceGroups['Workspace setup'] = [
            ['admin.channels.index', 'Channels', ['admin.channels.*', 'admin.websites.*', 'admin.knowledge.*']],
            ['admin.ai-agents.index', 'AI assistants', ['admin.ai-agents.*']],
            ['admin.knowledge-hub.index', 'Knowledge', ['admin.knowledge-hub.*']],
        ];
    }
    if ($workspaceSuper) {
        $workspaceGroups['System administration'] = [
            ['admin.system.users.index', 'Users & tenants', ['admin.system.users.*']],
            ['admin.system.websites.index', 'Website channel oversight', ['admin.system.websites.*']],
        ];
    }
@endphp
<aside class="cn-sidebar" aria-label="Workspace navigation">
    <a class="cn-brand" href="{{ route('dashboard') }}"><span class="cn-brand-mark" aria-hidden="true">N</span><span>ChatNivo<small>Customer conversations</small></span></a>
    <div class="cn-context"><span class="cn-eyebrow">{{ $workspaceRole }}</span><strong>{{ $workspaceUser->name }}</strong></div>
    <details class="cn-menu" open>
        <summary>Workspace menu</summary>
        <nav aria-label="Main navigation">
            @foreach($workspaceGroups as $group => $items)
                <div class="cn-nav-group"><p class="cn-eyebrow">{{ $group }}</p>
                    @foreach($items as [$routeName, $label, $patterns])
                        @if(Route::has($routeName))
                            <a href="{{ route($routeName) }}" @if(request()->routeIs(...$patterns)) aria-current="page" @endif>
                                <span>{{ $label }}</span>
                                @if($routeName === 'admin.conversations.index' && ($waitingLiveChatCount ?? 0) > 0)
                                    <span class="cn-count" aria-label="Waiting conversations">{{ $waitingLiveChatCount > 99 ? '99+' : $waitingLiveChatCount }}</span>
                                @endif
                            </a>
                        @endif
                    @endforeach
                </div>
            @endforeach
        </nav>
    </details>
    <div class="cn-account">
        @if(Route::has('profile.edit'))<a href="{{ route('profile.edit') }}">My profile</a>@endif
        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Sign out</button></form>
    </div>
</aside>
<script>
(() => {
    const menu = document.querySelector('.cn-menu');
    const breakpoint = window.matchMedia('(min-width: 1024px)');
    const syncMenu = () => { menu.open = breakpoint.matches; };
    syncMenu();
    breakpoint.addEventListener('change', syncMenu);
})();
</script>
