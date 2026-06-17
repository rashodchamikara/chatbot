<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2 text-sm text-slate-500">
                    <a href="{{ route('admin.websites.index') }}" class="hover:text-blue-600">Websites</a>
                    <span>/</span>
                    <a href="{{ route('admin.websites.show', $website) }}" class="hover:text-blue-600">{{ $website->name }}</a>
                    <span>/</span>
                    <span>Edit</span>
                </div>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Edit website</h1>
                <p class="mt-1 text-sm text-slate-500">Update website access, assistant identity, and operating instructions.</p>
            </div>

            <a href="{{ route('admin.websites.show', $website) }}"
               class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                Cancel
            </a>
        </div>
    </x-slot>

    <div class="min-h-screen bg-slate-50/70 py-8">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            @if($errors->any())
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-red-800 shadow-sm">
                    <p class="font-semibold">Please correct the highlighted fields.</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form
                method="POST"
                action="{{ route('admin.websites.update', $website) }}"
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
                @method('PATCH')

                <div class="space-y-6">
                    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-6 py-5">
                            <h2 class="text-base font-semibold text-slate-900">Website information</h2>
                            <p class="mt-1 text-sm text-slate-500">Changing the domain may require updating the embed installation.</p>
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
                                        @foreach($tenants as $tenant)
                                            <option value="{{ $tenant->id }}" @selected(old('tenant_id', $website->tenant_id) == $tenant->id)>
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
                                    value="{{ old('name', $website->name) }}"
                                    required
                                    maxlength="255"
                                    class="mt-2 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                @error('name')
                                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="domain" class="block text-sm font-semibold text-slate-700">Website URL</label>
                                <input
                                    id="domain"
                                    type="url"
                                    name="domain"
                                    value="{{ old('domain', $website->domain) }}"
                                    required
                                    class="mt-2 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                <p class="mt-1.5 text-xs text-amber-700">Changing this value can affect domain verification and indexing.</p>
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
                                    @checked(old('verify_domain', $website->verify_domain))
                                >
                                <span>
                                    <span class="block text-sm font-semibold text-slate-800">Enable domain verification</span>
                                    <span class="mt-1 block text-xs leading-5 text-slate-500">Restricts widget requests to the configured domain.</span>
                                </span>
                            </label>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-6 py-5">
                            <h2 class="text-base font-semibold text-slate-900">Assistant identity</h2>
                            <p class="mt-1 text-sm text-slate-500">Update what visitors see and how the assistant behaves.</p>
                        </div>

                        <div class="space-y-6 p-6">
                            <div>
                                <label for="chatbot_name" class="block text-sm font-semibold text-slate-700">Assistant name</label>
                                <input
                                    id="chatbot_name"
                                    type="text"
                                    name="chatbot_name"
                                    value="{{ old('chatbot_name', $website->chatbot_name) }}"
                                    maxlength="100"
                                    placeholder="Example: Sales Assistant"
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
                                                @checked(old('chatbot_theme', $website->chatbot_theme ?? config('chatbot.default_theme')) === $themeKey)
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
                                            @if($website->chatbot_avatar)
                                                <img src="{{ asset('storage/' . $website->chatbot_avatar) }}" alt="Current avatar" class="h-full w-full object-cover">
                                            @else
                                                <span>🤖</span>
                                            @endif
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
                                        <p class="mt-2 text-xs text-slate-500">Uploading a new image replaces the current avatar.</p>
                                    </div>
                                </div>
                                @error('chatbot_avatar')
                                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="chatbot_instructions" class="block text-sm font-semibold text-slate-700">Assistant instructions</label>
                                <textarea
                                    id="chatbot_instructions"
                                    name="chatbot_instructions"
                                    rows="8"
                                    maxlength="5000"
                                    class="mt-2 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >{{ old('chatbot_instructions', $website->chatbot_instructions) }}</textarea>
                                <p class="mt-1.5 text-xs leading-5 text-slate-500">These instructions are added to the AI system prompt.</p>
                                @error('chatbot_instructions')
                                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </section>
                </div>

                <aside class="space-y-6 lg:sticky lg:top-6 lg:self-start">
                    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <h2 class="text-sm font-semibold text-slate-900">Website status</h2>
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $website->is_active ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-slate-100 text-slate-600 ring-1 ring-slate-200' }}">
                                {{ $website->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>

                        <label class="mt-4 flex cursor-pointer items-start gap-3">
                            <input
                                type="checkbox"
                                name="is_active"
                                value="1"
                                class="mt-0.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                @checked(old('is_active', $website->is_active))
                            >
                            <span>
                                <span class="block text-sm font-semibold text-slate-800">Website active</span>
                                <span class="mt-1 block text-xs leading-5 text-slate-500">Disable access without deleting configuration or history.</span>
                            </span>
                        </label>
                    </section>

                    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 class="text-sm font-semibold text-slate-900">Current assistant</h2>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-500">Name</dt>
                                <dd class="text-right font-medium text-slate-800">{{ $website->chatbot_name ?: $website->name . ' Assistant' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-500">Theme</dt>
                                <dd class="font-medium text-slate-800">{{ config('chatbot.themes.' . $website->chatbot_theme . '.label') ?? ucfirst($website->chatbot_theme) }}</dd>
                            </div>
                        </dl>
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
                            <span x-text="submitting ? 'Saving changes…' : 'Save changes'"></span>
                        </button>

                        <a href="{{ route('admin.websites.show', $website) }}"
                           class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Cancel
                        </a>
                    </div>
                </aside>
            </form>
        </div>
    </div>
</x-app-layout>
