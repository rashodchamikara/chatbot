<x-app-layout>
    @php
        $statusClasses = match($channelConnection->status) {
            'active' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'pending' => 'bg-amber-50 text-amber-700 ring-amber-200',
            'error' => 'bg-red-50 text-red-700 ring-red-200',
            default => 'bg-slate-100 text-slate-600 ring-slate-200',
        };
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2 text-sm text-slate-500">
                    <a href="{{ route('admin.channels.index') }}" class="hover:text-emerald-600">Channels</a>
                    <span>/</span>
                    <span>{{ $channelConnection->name }}</span>
                </div>
                <div class="mt-1 flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Manage WhatsApp</h1>
                    <span class="inline-flex rounded-full px-3 py-1.5 text-xs font-semibold ring-1 ring-inset {{ $statusClasses }}">
                        {{ ucfirst($channelConnection->status) }}
                    </span>
                </div>
                <p class="mt-1 text-sm text-slate-500">{{ data_get($channelConnection->settings, 'display_phone_number') ?: $channelConnection->name }}</p>
            </div>
        </div>
    </x-slot>

    <div class="min-h-screen bg-slate-50/70 py-8">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 shadow-sm">{{ session('success') }}</div>
            @endif

            @if(session('warning'))
                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800 shadow-sm">{{ session('warning') }}</div>
            @endif

            @if($errors->any())
                <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 shadow-sm">
                    <ul class="list-disc space-y-1 pl-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
                <form
                    method="POST"
                    action="{{ route('admin.channels.whatsapp.update', $channelConnection) }}"
                    x-data="{ submitting: false, showToken: false }"
                    @submit="submitting = true"
                    class="space-y-6"
                >
                    @csrf
                    @method('PUT')

                    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-6 py-5">
                            <h2 class="text-base font-semibold text-slate-900">Connection settings</h2>
                            <p class="mt-1 text-sm text-slate-500">Only replace the token when it changes or expires.</p>
                        </div>

                        <div class="space-y-6 p-6">
                            <div>
                                <label for="name" class="block text-sm font-semibold text-slate-700">Connection name</label>
                                <input
                                    id="name"
                                    name="name"
                                    value="{{ old('name', $channelConnection->name) }}"
                                    required
                                    class="mt-2 block w-full rounded-xl border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                >
                            </div>

                            <div class="grid gap-5 md:grid-cols-2">
                                <div>
                                    <label for="business_account_id" class="block text-sm font-semibold text-slate-700">WABA ID</label>
                                    <input
                                        id="business_account_id"
                                        name="business_account_id"
                                        inputmode="numeric"
                                        value="{{ old('business_account_id', $channelConnection->external_account_id) }}"
                                        required
                                        class="mt-2 block w-full rounded-xl border-slate-300 font-mono text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                    >
                                </div>

                                <div>
                                    <label for="phone_number_id" class="block text-sm font-semibold text-slate-700">Phone Number ID</label>
                                    <input
                                        id="phone_number_id"
                                        name="phone_number_id"
                                        inputmode="numeric"
                                        value="{{ old('phone_number_id', $channelConnection->external_sender_id) }}"
                                        required
                                        class="mt-2 block w-full rounded-xl border-slate-300 font-mono text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                    >
                                </div>
                            </div>

                            <div>
                                <div class="flex items-center justify-between gap-3">
                                    <label for="access_token" class="block text-sm font-semibold text-slate-700">Replace access token</label>
                                    <span class="text-xs font-medium text-slate-400">Optional</span>
                                </div>
                                <div class="relative mt-2">
                                    <input
                                        id="access_token"
                                        :type="showToken ? 'text' : 'password'"
                                        name="access_token"
                                        autocomplete="new-password"
                                        placeholder="Leave blank to keep current encrypted token"
                                        class="block w-full rounded-xl border-slate-300 pr-20 font-mono text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                    >
                                    <button
                                        type="button"
                                        @click="showToken = !showToken"
                                        class="absolute inset-y-0 right-0 inline-flex items-center px-4 text-xs font-semibold text-slate-500 hover:text-slate-800"
                                        x-text="showToken ? 'Hide' : 'Show'"
                                    ></button>
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <button
                                    :disabled="submitting"
                                    class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-60"
                                >
                                    <span x-text="submitting ? 'Verifying...' : 'Save & verify'"></span>
                                </button>
                            </div>
                        </div>
                    </section>
                </form>

                <aside class="space-y-5">
                    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 class="text-sm font-semibold text-slate-900">Verified by Meta</h2>
                        <dl class="mt-4 space-y-4 text-sm">
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Business name</dt>
                                <dd class="mt-1 font-semibold text-slate-800">{{ data_get($channelConnection->settings, 'verified_name') ?: 'Not available' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">WhatsApp number</dt>
                                <dd class="mt-1 font-mono font-semibold text-slate-800">{{ data_get($channelConnection->settings, 'display_phone_number') ?: 'Not available' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Quality rating</dt>
                                <dd class="mt-1 font-semibold text-slate-800">{{ data_get($channelConnection->settings, 'quality_rating') ?: 'Not available' }}</dd>
                            </div>
                        </dl>
                    </section>

                    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 class="text-sm font-semibold text-slate-900">Connection health</h2>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div class="flex justify-between gap-4"><dt class="text-slate-500">Connected</dt><dd class="text-right font-medium text-slate-700">{{ $channelConnection->connected_at?->format('d M Y H:i') ?? 'Not verified' }}</dd></div>
                            <div class="flex justify-between gap-4"><dt class="text-slate-500">Last health check</dt><dd class="text-right font-medium text-slate-700">{{ $channelConnection->last_health_check_at?->diffForHumans() ?? 'Never' }}</dd></div>
                            <div class="flex justify-between gap-4"><dt class="text-slate-500">Last inbound</dt><dd class="text-right font-medium text-slate-700">{{ $channelConnection->last_webhook_at?->diffForHumans() ?? 'None yet' }}</dd></div>
                        </dl>

                        @if($channelConnection->last_error)
                            <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-xs leading-5 text-red-700">{{ $channelConnection->last_error }}</div>
                        @endif

                        <form method="POST" action="{{ route('admin.channels.whatsapp.health', $channelConnection) }}" class="mt-5">
                            @csrf
                            <button class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Run health check</button>
                        </form>
                    </section>

                    <section class="rounded-2xl border border-blue-200 bg-blue-50 p-5 shadow-sm">
                        <h2 class="text-sm font-semibold text-blue-950">AI training</h2>
                        <p class="mt-2 text-sm leading-6 text-blue-900/80">This number uses <strong>{{ $channelConnection->aiAgent?->name }}</strong>. For WhatsApp-only customers, train it with manual knowledge and uploaded documents.</p>
                        @if(Route::has('admin.knowledge-hub.index'))
                            <a href="{{ route('admin.knowledge-hub.index', ['agent_id' => $channelConnection->ai_agent_id]) }}" class="mt-4 inline-flex rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Open Knowledge Hub</a>
                        @else
                            <p class="mt-3 text-xs font-medium text-blue-800">The WhatsApp connection is ready; add the Knowledge Hub routes/screens when enabling standalone manual training.</p>
                        @endif
                    </section>

                    <section class="rounded-2xl border border-red-200 bg-white p-5 shadow-sm">
                        <h2 class="text-sm font-semibold text-red-700">Disconnect WhatsApp</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-500">Stops new traffic and removes the stored access token while preserving conversation history.</p>
                        <form method="POST" action="{{ route('admin.channels.whatsapp.destroy', $channelConnection) }}" class="mt-4" onsubmit="return confirm('Disconnect this WhatsApp channel?');">
                            @csrf
                            @method('DELETE')
                            <button class="w-full rounded-xl border border-red-200 px-4 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-50">Disconnect</button>
                        </form>
                    </section>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
