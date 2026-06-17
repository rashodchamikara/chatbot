<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2 text-sm text-slate-500">
                    <a href="{{ route('admin.websites.index') }}" class="hover:text-blue-600">Websites</a>
                    <span>/</span>
                    <span>New website</span>
                </div>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Add website</h1>
                <p class="mt-1 text-sm text-slate-500">Connect a website, configure its AI assistant, and prepare it for indexing.</p>
            </div>

            <a href="{{ route('admin.websites.index') }}"
               class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                Cancel
            </a>
        </div>
    </x-slot>

    <div class="min-h-screen bg-slate-50/70 py-8">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            @if($errors->any())
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-red-800 shadow-sm">
                    <div class="flex gap-3">
                        <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-red-100">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 4.6 2.8 18a2 2 0 0 0 1.75 3h14.9a2 2 0 0 0 1.75-3L13.7 4.6a2 2 0 0 0-3.4 0Z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold">Please correct the following:</p>
                            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <form
                method="POST"
                action="{{ route('admin.websites.store') }}"
                enctype="multipart/form-data"
                x-data="{
                    submitting: false,
                    avatarPreview: null,
                    handleAvatar(event) {
                        const file = event.target.files[0];
                        if (!file) return;
                        if (this.avatarPreview) URL.revokeObjectURL(this.avatarPreview);
                        this.avatarPreview = URL.createObjectURL(file);
                    }
                }"
                @submit="submitting = true"
                class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_320px]"
            >
                @csrf

                <div class="space-y-6">
                    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-6 py-5">
                            <h2 class="text-base font-semibold text-slate-900">Website information</h2>
                            <p class="mt-1 text-sm text-slate-500">Basic ownership and domain settings.</p>
                        </div>

                        <div class="space-y-5 p-6">
                            @if(auth()->user()->isSuperAdmin())
                                <div>
                                    <label for="tenant_id" class="block text-sm font-semibold text-slate-700">Tenant</label>
                                    <select
                                        id="tenant_id"
                                        name="tenant_id"
                                        required
                                        class="mt-2 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    >
                                        <option value="">Select tenant</option>
                                        @foreach($tenants as $tenant)
                                            <option value="{{ $tenant->id }}" @selected(old('tenant_id') == $tenant->id)>
                                                {{ $tenant->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('tenant_id')
                                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endif

                            <div>
                                <label for="name" class="block text-sm font-semibold text-slate-700">Website name</label>
                                <input
                                    id="name"
                                    type="text"
                                    name="name"
                                    value="{{ old('name') }}"
                                    required
                                    maxlength="255"
                                    placeholder="Example: ABC Company"
                                    class="mt-2 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                <p class="mt-1.5 text-xs text-slate-500">Use a recognizable internal name.</p>
                                @error('name')
                                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="domain" class="block text-sm font-semibold text-slate-700">Website URL</label>
                                <div class="relative mt-2">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="9"/>
                                            <path d="M3 12h18M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18"/>
                                        </svg>
                                    </span>
                                    <input
                                        id="domain"
                                        type="url"
                                        name="domain"
                                        value="{{ old('domain') }}"
                                        required
                                        placeholder="https://example.com"
                                        class="block w-full rounded-xl border-slate-300 pl-10 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    >
                                </div>
                                <p class="mt-1.5 text-xs text-slate-500">Enter the full canonical URL including HTTPS.</p>
                                @error('domain')
                                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4 hover:bg-slate-50">
                                <input
                                    type="checkbox"
                                    name="verify_domain"
                                    value="1"
                                    class="mt-0.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                    @checked(old('verify_domain', true))
                                >
                                <span>
                                    <span class="block text-sm font-semibold text-slate-800">Enable domain verification</span>
                                    <span class="mt-1 block text-xs leading-5 text-slate-500">Restricts the embed token to requests originating from this domain. Keep enabled in production.</span>
                                </span>
                            </label>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-6 py-5">
                            <h2 class="text-base font-semibold text-slate-900">Assistant identity</h2>
                            <p class="mt-1 text-sm text-slate-500">Control how the assistant appears to visitors.</p>
                        </div>

                        <div class="space-y-6 p-6">
                            <div>
                                <label for="chatbot_name" class="block text-sm font-semibold text-slate-700">Assistant name</label>
                                <input
                                    id="chatbot_name"
                                    type="text"
                                    name="chatbot_name"
                                    value="{{ old('chatbot_name') }}"
                                    placeholder="Example: Sales Assistant"
                                    maxlength="100"
                                    class="mt-2 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                @error('chatbot_name')
                                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <fieldset>
                                <legend class="text-sm font-semibold text-slate-700">Color theme</legend>
                                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                    @foreach($themes as $themeKey => $theme)
                                        <label class="relative cursor-pointer rounded-xl border border-slate-200 p-4 transition hover:border-slate-300 hover:shadow-sm has-[:checked]:border-blue-500 has-[:checked]:ring-2 has-[:checked]:ring-blue-100">
                                            <input
                                                type="radio"
                                                name="chatbot_theme"
                                                value="{{ $themeKey }}"
                                                class="absolute right-3 top-3 text-blue-600 focus:ring-blue-500"
                                                @checked(old('chatbot_theme', config('chatbot.default_theme')) === $themeKey)
                                            >
                                            <div class="flex items-center gap-3">
                                                <span class="h-10 w-10 rounded-full border-4 border-white shadow ring-1 ring-slate-200"
                                                      style="background: {{ $theme['primary'] }}"></span>
                                                <div>
                                                    <p class="text-sm font-semibold text-slate-800">{{ $theme['label'] }}</p>
                                                    <p class="mt-0.5 text-xs text-slate-500">{{ $theme['primary'] }}</p>
                                                </div>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                                @error('chatbot_theme')
                                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </fieldset>

                            <div>
                                <label for="chatbot_avatar" class="block text-sm font-semibold text-slate-700">Assistant avatar</label>
                                <div class="mt-3 flex flex-col gap-4 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 sm:flex-row sm:items-center">
                                    <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-full border border-slate-200 bg-white text-2xl">
                                        <template x-if="avatarPreview">
                                            <img :src="avatarPreview" alt="Avatar preview" class="h-full w-full object-cover">
                                        </template>
                                        <template x-if="!avatarPreview">
                                            <span>🤖</span>
                                        </template>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <input
                                            id="chatbot_avatar"
                                            type="file"
                                            name="chatbot_avatar"
                                            accept=".jpg,.jpeg,.png,.webp,.svg,image/*"
                                            @change="handleAvatar($event)"
                                            class="block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:font-semibold file:text-blue-700 hover:file:bg-blue-100"
                                        >
                                        <p class="mt-2 text-xs text-slate-500">Square image recommended. JPG, PNG, WEBP or SVG. Maximum 2 MB.</p>
                                    </div>
                                </div>
                                @error('chatbot_avatar')
                                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <div class="flex items-center justify-between gap-3">
                                    <label for="chatbot_instructions" class="block text-sm font-semibold text-slate-700">Assistant instructions</label>
                                    <span class="text-xs text-slate-400">Used in the system prompt</span>
                                </div>
                                <textarea
                                    id="chatbot_instructions"
                                    name="chatbot_instructions"
                                    rows="8"
                                    maxlength="5000"
                                    placeholder="Describe tone, product priorities, lead-capture goals, restrictions, and escalation rules."
                                    class="mt-2 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >{{ old('chatbot_instructions') }}</textarea>
                                <p class="mt-1.5 text-xs leading-5 text-slate-500">Keep instructions specific and operational. Avoid adding large amounts of website content here; use the knowledge base for that.</p>
                                @error('chatbot_instructions')
                                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </section>
                </div>

                <aside class="space-y-6 lg:sticky lg:top-6 lg:self-start">
                    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 class="text-sm font-semibold text-slate-900">Publication status</h2>
                        <label class="mt-4 flex cursor-pointer items-start gap-3">
                            <input
                                type="checkbox"
                                name="is_active"
                                value="1"
                                class="mt-0.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                @checked(old('is_active', true))
                            >
                            <span>
                                <span class="block text-sm font-semibold text-slate-800">Website active</span>
                                <span class="mt-1 block text-xs leading-5 text-slate-500">The widget and API will be available immediately after creation.</span>
                            </span>
                        </label>
                    </section>

                    <section class="rounded-2xl border border-blue-200 bg-blue-50 p-5">
                        <h2 class="text-sm font-semibold text-blue-900">What happens next?</h2>
                        <ol class="mt-3 space-y-3 text-sm text-blue-800">
                            <li class="flex gap-3"><span class="font-bold">1.</span><span>An embed token is generated.</span></li>
                            <li class="flex gap-3"><span class="font-bold">2.</span><span>The website enters the indexing queue.</span></li>
                            <li class="flex gap-3"><span class="font-bold">3.</span><span>You install the widget code on the website.</span></li>
                        </ol>
                    </section>

                    <div class="flex flex-col gap-3">
                        <button
                            type="submit"
                            :disabled="submitting"
                            class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow-sm shadow-blue-600/20 hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            <svg x-show="submitting" class="mr-2 h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"/>
                            </svg>
                            <span x-text="submitting ? 'Creating website…' : 'Create website'"></span>
                        </button>

                        <a href="{{ route('admin.websites.index') }}"
                           class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Cancel
                        </a>
                    </div>
                </aside>
            </form>
        </div>
    </div>
</x-app-layout>
