<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('admin.ai-agents.index') }}" class="text-sm font-semibold text-blue-600 hover:text-blue-700">← AI Agents</a>
                <h1 class="mt-1 text-xl font-semibold tracking-tight text-slate-900">Configure {{ $aiAgent->name }}</h1>
                <p class="mt-1 text-sm text-slate-500">Instructions here apply to every channel connected to this AI agent.</p>
            </div>
            <a href="{{ route('admin.knowledge-hub.index', ['agent_id' => $aiAgent->id]) }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">Open Knowledge Hub</a>
        </div>
    </x-slot>

    <div class="min-h-screen bg-slate-50/70 py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('admin.ai-agents.update', $aiAgent) }}" class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_300px]">
                @csrf
                @method('PUT')

                <div class="space-y-6">
                    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                        <h2 class="text-base font-semibold text-slate-900">Agent identity</h2>
                        <div class="mt-5 grid gap-5 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label for="name" class="text-sm font-semibold text-slate-700">Agent name</label>
                                <input id="name" name="name" value="{{ old('name', $aiAgent->name) }}" required class="mt-2 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @error('name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="status" class="text-sm font-semibold text-slate-700">Status</label>
                                <select id="status" name="status" class="mt-2 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="active" @selected(old('status', $aiAgent->status) === 'active')>Active</option>
                                    <option value="suspended" @selected(old('status', $aiAgent->status) === 'suspended')>Suspended</option>
                                </select>
                            </div>
                            <div>
                                <label for="default_language" class="text-sm font-semibold text-slate-700">Default language</label>
                                <input id="default_language" name="default_language" value="{{ old('default_language', $aiAgent->default_language ?: 'en') }}" required class="mt-2 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="en">
                            </div>
                        </div>
                    </section>

                    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                        <h2 class="text-base font-semibold text-slate-900">AI behaviour</h2>
                        <p class="mt-1 text-sm text-slate-500">Describe the role, tone, qualification rules, business boundaries and when the AI should hand over.</p>
                        <textarea name="instructions" rows="13" class="mt-5 w-full rounded-2xl border-slate-300 text-sm leading-6 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Example: You are the sales and support assistant for...">{{ old('instructions', $aiAgent->instructions) }}</textarea>
                        @error('instructions')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </section>
                </div>

                <aside class="space-y-5">
                    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 class="font-semibold text-slate-900">Agent settings</h2>
                        <label class="mt-4 flex items-start gap-3 rounded-2xl border border-slate-200 p-3.5">
                            <input type="checkbox" name="is_default" value="1" @checked(old('is_default', $aiAgent->is_default)) class="mt-0.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                            <span>
                                <span class="block text-sm font-semibold text-slate-800">Default agent</span>
                                <span class="mt-0.5 block text-xs leading-5 text-slate-500">Used automatically when a new channel does not explicitly choose an agent.</span>
                            </span>
                        </label>

                        <label class="mt-3 flex items-start gap-3 rounded-2xl border border-slate-200 p-3.5">
                            <input type="checkbox" name="handover_enabled" value="1" @checked(old('handover_enabled', (bool) data_get($aiAgent->handover_settings, 'enabled', false))) class="mt-0.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                            <span>
                                <span class="block text-sm font-semibold text-slate-800">Human handover</span>
                                <span class="mt-0.5 block text-xs leading-5 text-slate-500">Allow this agent to move conversations to a human when the workflow requests it.</span>
                            </span>
                        </label>
                    </section>

                    <section class="rounded-3xl bg-slate-950 p-5 text-white shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wider text-blue-300">Scope</p>
                        <p class="mt-2 text-sm font-semibold">{{ $aiAgent->tenant->company_name ?: $aiAgent->tenant->name }}</p>
                        <p class="mt-2 text-xs leading-5 text-slate-400">Knowledge is isolated by this AI agent, not by website. This lets WhatsApp-only customers work without a website.</p>
                    </section>

                    <button type="submit" class="w-full rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">Save agent settings</button>
                </aside>
            </form>
        </div>
    </div>
</x-app-layout>
