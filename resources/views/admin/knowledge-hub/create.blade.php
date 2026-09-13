<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('admin.knowledge-hub.index', ['agent_id' => $selectedAgentId]) }}" class="text-sm font-semibold text-violet-600 hover:text-violet-700">← Knowledge Hub</a>
            <h1 class="mt-2 text-xl font-semibold tracking-tight text-slate-900">Add manual knowledge</h1>
            <p class="mt-1 text-sm text-slate-500">Add approved information that the AI may use when answering customers.</p>
        </div>
    </x-slot>

    <div class="min-h-screen bg-slate-50/70 py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            @if($errors->any())
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800"><ul class="list-disc space-y-1 pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif

            <form method="POST" action="{{ route('admin.knowledge-hub.store') }}" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                @csrf
                <div class="space-y-6 p-5 sm:p-7">
                    <div>
                        <label class="text-sm font-semibold text-slate-700">AI agent</label>
                        <select name="ai_agent_id" required class="mt-2 w-full rounded-xl border-slate-300 text-sm focus:border-violet-500 focus:ring-violet-500">
                            @foreach($agents as $agent)
                                <option value="{{ $agent->id }}" @selected((string) old('ai_agent_id', $selectedAgentId) === (string) $agent->id)>
                                    {{ $agent->name }}@if(auth()->user()->isSuperAdmin()) — {{ $agent->tenant?->company_name ?: $agent->tenant?->name }}@endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label class="text-sm font-semibold text-slate-700">Title</label>
                            <input name="title" value="{{ old('title') }}" required placeholder="e.g. Delivery policy"
                                   class="mt-2 w-full rounded-xl border-slate-300 text-sm focus:border-violet-500 focus:ring-violet-500">
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-slate-700">Knowledge type</label>
                            <select name="type" required class="mt-2 w-full rounded-xl border-slate-300 text-sm focus:border-violet-500 focus:ring-violet-500">
                                @foreach(['business_info' => 'Business information', 'faq' => 'FAQ', 'product' => 'Product', 'service' => 'Service', 'pricing' => 'Pricing', 'policy' => 'Policy', 'page' => 'General page', 'blog' => 'Blog/article', 'whitepaper' => 'Whitepaper', 'other' => 'Other'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('type', 'faq') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-slate-700">Reference URL <span class="font-normal text-slate-400">optional</span></label>
                        <input name="url" value="{{ old('url') }}" placeholder="https://... or leave blank"
                               class="mt-2 w-full rounded-xl border-slate-300 text-sm focus:border-violet-500 focus:ring-violet-500">
                    </div>

                    <div>
                        <div class="flex items-center justify-between gap-4"><label class="text-sm font-semibold text-slate-700">Knowledge content</label><span class="text-xs text-slate-400">Be factual and specific</span></div>
                        <textarea name="content" rows="16" required placeholder="Example:\nDelivery is available within Colombo. Standard delivery takes 1–2 business days. Orders above Rs. 10,000 qualify for free delivery..."
                                  class="mt-2 w-full rounded-xl border-slate-300 text-sm leading-6 focus:border-violet-500 focus:ring-violet-500">{{ old('content') }}</textarea>
                        <p class="mt-2 text-xs leading-5 text-slate-500">The content is indexed immediately after saving, so the AI can use it without a second indexing step.</p>
                    </div>

                    <label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) class="mt-0.5 rounded border-slate-300 text-violet-600 focus:ring-violet-500">
                        <span><span class="block text-sm font-semibold text-slate-800">Make available to the AI immediately</span><span class="mt-1 block text-xs text-slate-500">Turn this off when preparing content that should not be used yet.</span></span>
                    </label>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 bg-slate-50 px-5 py-4 sm:flex-row sm:justify-end sm:px-7">
                    <a href="{{ route('admin.knowledge-hub.index', ['agent_id' => $selectedAgentId]) }}" class="inline-flex justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
                    <button class="inline-flex justify-center rounded-xl bg-violet-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-violet-700">Save & train AI</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
