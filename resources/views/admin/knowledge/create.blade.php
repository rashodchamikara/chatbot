<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Add Manual Knowledge
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

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

                <form method="POST" action="{{ route('admin.websites.knowledge.store', $website) }}">
                    @csrf

                    <div class="mb-4">
                        <label class="block text-sm font-semibold mb-2">Title</label>

                        <input
                            type="text"
                            name="title"
                            value="{{ old('title') }}"
                            class="border rounded px-3 py-2 w-full"
                            required
                        >
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-semibold mb-2">URL or Reference</label>

                        <input
                            type="text"
                            name="url"
                            value="{{ old('url') }}"
                            class="border rounded px-3 py-2 w-full"
                            placeholder="https://example.com/page or leave empty for manual reference"
                        >

                        <p class="text-xs text-gray-500 mt-1">
                            You may leave this empty for manually added knowledge.
                        </p>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-semibold mb-2">Type</label>

                        <select name="type" class="border rounded px-3 py-2 w-full" required>
                            @foreach(['page', 'product', 'blog', 'whitepaper', 'faq', 'service', 'pricing', 'other'] as $type)
                                <option value="{{ $type }}" @selected(old('type') === $type)>
                                    {{ ucfirst($type) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-semibold mb-2">Content</label>

                        <textarea
                            name="content"
                            rows="16"
                            class="border rounded px-3 py-2 w-full"
                            required
                            placeholder="Add product information, FAQ answer, pricing explanation, service details, or any approved sales knowledge here..."
                        >{{ old('content') }}</textarea>

                        <p class="text-xs text-gray-500 mt-1">
                            Minimum 50 characters. This text will be chunked and embedded for AI retrieval.
                        </p>
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
                                Active
                            </span>
                        </label>
                    </div>

                    <div class="flex justify-between">
                        <a href="{{ route('admin.websites.knowledge.index', $website) }}" class="px-4 py-2 border rounded">
                            Cancel
                        </a>

                        <button class="bg-blue-600 text-white px-4 py-2 rounded">
                            Save Knowledge Page
                        </button>
                    </div>
                </form>

            </div>

        </div>
    </div>
</x-app-layout>