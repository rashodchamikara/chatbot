<section id="channel-catalog" class="cn-panel" aria-labelledby="channel-catalog-title">
    <h2 id="channel-catalog-title">Add a channel</h2>
    <p>Choose where customers can reach you. Assign an AI assistant to each connection and manage conversations in the inbox.</p>
    <div class="cn-card-grid" style="margin-top:20px">
        @foreach($workspaceCatalog as $type => $channelInfo)
            <article class="cn-channel-card">
                <span class="cn-channel-mark" aria-hidden="true">{{ $channelInfo['mark'] }}</span>
                <h3>{{ $channelInfo['label'] }}</h3><p>{{ $channelInfo['description'] }}</p>
                @if($channelInfo['route'] && Route::has($channelInfo['route']))
                    <a class="cn-button" href="{{ route($channelInfo['route']) }}">Connect {{ $channelInfo['label'] }}</a>
                @else
                    <span class="cn-planned">Planned · not available yet</span>
                @endif
            </article>
        @endforeach
    </div>
    <p style="margin-top:16px">More channel integrations will be added here as they become available.</p>
</section>
