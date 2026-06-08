<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit Knowledge Page
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

                <form method="POST" action="{{ route('admin.knowledge.update', $knowledgePage) }}">
                    @csrf
                    @method('PATCH')

                    <div class="mb-4">
                        <label class="block text-sm font-semibold mb-2">Title</label>

                        <input
                            type="text"
                            name="title"
                            value="{{ old('title', $knowledgePage->title) }}"
                            class="border rounded px-3 py-2 w-full"
                            required
                        >
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-semibold mb-2">URL or Reference</label>

                        <input
                            type="text"
                            name="url"
                            value="{{ old('url', $knowledgePage->url) }}"
                            class="border rounded px-3 py-2 w-full"
                            required
                        >
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-semibold mb-2">Type</label>

                        <select name="type" class="border rounded px-3 py-2 w-full" required>
                            @foreach(['page', 'product', 'blog', 'whitepaper', 'faq', 'service', 'pricing', 'other'] as $type)
                                <option value="{{ $type }}" @selected(old('type', $knowledgePage->type) === $type)>
                                    {{ ucfirst($type) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-semibold mb-2">Content</label>

                        <textarea
                            name="content"
                            rows="18"
                            class="border rounded px-3 py-2 w-full"
                            required
                        >{{ old('content', $knowledgePage->content) }}</textarea>

                        <p class="text-xs text-gray-500 mt-1">
                            If content changes, the page will be marked as not indexed and existing chunks will be deleted.
                        </p>
                    </div>

                    <div class="mb-6">
                        <label class="inline-flex items-center">
                            <input
                                type="checkbox"
                                name="is_active"
                                value="1"
                                class="rounded"
                                @checked(old('is_active', $knowledgePage->is_active))
                            >

                            <span class="ml-2 text-sm">
                                Active
                            </span>
                        </label>
                    </div>

                    <div class="flex justify-between">
                        <a href="{{ route('admin.knowledge.show', $knowledgePage) }}" class="px-4 py-2 border rounded">
                            Cancel
                        </a>

                        <button class="bg-blue-600 text-white px-4 py-2 rounded">
                            Save Changes
                        </button>
                    </div>

                </form>

            </div>

        </div>
    </div>
</x-app-layout>