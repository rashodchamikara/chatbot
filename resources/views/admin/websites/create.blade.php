<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Add Website
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white rounded shadow p-6">

                @if($errors->any())
                    <div class="mb-4 p-4 bg-red-100 text-red-800 rounded">
                        <ul class="list-disc ml-5">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.websites.store') }}" enctype="multipart/form-data">
                    @csrf

                    @if(auth()->user()->isSuperAdmin())
                        <div class="mb-4">
                            <label class="block text-sm font-semibold mb-2">Tenant</label>

                            <select name="tenant_id" class="border rounded px-3 py-2 w-full" required>
                                <option value="">Select tenant</option>
                                @foreach($tenants as $tenant)
                                    <option value="{{ $tenant->id }}" @selected(old('tenant_id') == $tenant->id)>
                                        {{ $tenant->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="mb-4">
                        <label class="block text-sm font-semibold mb-2">Website Name</label>

                        <input
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            class="border rounded px-3 py-2 w-full"
                            placeholder="Example: ABC Company Website"
                            required
                        >
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-semibold mb-2">Domain</label>

                        <input
                            type="text"
                            name="domain"
                            value="{{ old('domain') }}"
                            class="border rounded px-3 py-2 w-full"
                            placeholder="https://example.com"
                            required
                        >

                        <p class="text-xs text-gray-500 mt-1">
                            Include https:// if possible. Example: https://example.com
                        </p>
                    </div>

                    <div class="mb-4">
                        <label class="inline-flex items-center">
                            <input
                                type="checkbox"
                                name="verify_domain"
                                value="1"
                                class="rounded"
                                @checked(old('verify_domain', true))
                            >

                            <span class="ml-2 text-sm">
                                Enable domain verification
                            </span>
                        </label>

                        <p class="text-xs text-gray-500 mt-1">
                            For local testing, you may disable this. For production websites, keep it enabled.
                        </p>
                    </div>
                    <div class="mt-8 border-t pt-6">
                    <h3 class="text-lg font-semibold mb-4">Chatbot Settings</h3>

                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-1">
                            Chatbot Name
                        </label>

                        <input
                            type="text"
                            name="chatbot_name"
                            value="{{ old('chatbot_name') }}"
                            placeholder="Eg: Sales Assistant"
                            class="border rounded px-3 py-2 w-full"
                        >

                        @error('chatbot_name')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2">
                            Color Theme
                        </label>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            @foreach($themes as $themeKey => $theme)
                                <label
                                    class="relative border rounded-xl p-3 cursor-pointer hover:border-gray-400 transition"
                                >
                                    <input
                                        type="radio"
                                        name="chatbot_theme"
                                        value="{{ $themeKey }}"
                                        class="absolute top-3 right-3"
                                        @checked(old('chatbot_theme', config('chatbot.default_theme')) === $themeKey)
                                    >

                                    <div class="flex items-center gap-2">
                                        <span
                                            class="inline-block w-8 h-8 rounded-full border"
                                            style="background: {{ $theme['primary'] }}"
                                        ></span>

                                        <div>
                                            <div class="font-semibold text-sm">
                                                {{ $theme['label'] }}
                                            </div>

                                            <div class="text-xs text-gray-500">
                                                {{ $theme['primary'] }}
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>

                        @error('chatbot_theme')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-1">
                            Chatbot Avatar
                        </label>

                        <input
                            type="file"
                            name="chatbot_avatar"
                            accept="image/*"
                            class="border rounded px-3 py-2 w-full"
                        >

                        <p class="text-xs text-gray-500 mt-1">
                            Recommended size: 200x200px. Supported: JPG, PNG, WEBP, SVG. Max: 2MB.
                        </p>

                        @error('chatbot_avatar')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-1">
                            Initial Chatbot Instructions
                        </label>

                        <textarea
                            name="chatbot_instructions"
                            rows="6"
                            placeholder="Example: Use a friendly professional tone. If user asks for pricing, explain that pricing depends on requirements and collect their contact details. Always encourage booking a consultation."
                            class="border rounded px-3 py-2 w-full"
                        >{{ old('chatbot_instructions') }}</textarea>

                        <p class="text-xs text-gray-500 mt-1">
                            These instructions will be included in the AI system prompt for this website.
                        </p>

                        @error('chatbot_instructions')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                    <div class="mb-6">
                        <label class="inline-flex items-center">
                            <input
                                type="checkbox"
                                name="is_active"
                                value="1"
                                class="rounded"
                                @checked(old('is_active', true))
                            >

                            <span class="ml-2 text-sm">
                                Website active
                            </span>
                        </label>
                    </div>

                    <div class="flex justify-between">
                        <a href="{{ route('admin.websites.index') }}" class="px-4 py-2 border rounded">
                            Cancel
                        </a>

                        <button class="bg-blue-600 text-white px-4 py-2 rounded">
                            Create Website
                        </button>
                    </div>

                </form>

            </div>

        </div>
    </div>
</x-app-layout>