<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit Website
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

                <form method="POST" action="{{ route('admin.websites.update', $website) }}">
                    @csrf
                    @method('PATCH')

                    @if(auth()->user()->isSuperAdmin())
                        <div class="mb-4">
                            <label class="block text-sm font-semibold mb-2">Tenant</label>

                            <select name="tenant_id" class="border rounded px-3 py-2 w-full" required>
                                @foreach($tenants as $tenant)
                                    <option value="{{ $tenant->id }}" @selected(old('tenant_id', $website->tenant_id) == $tenant->id)>
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
                            value="{{ old('name', $website->name) }}"
                            class="border rounded px-3 py-2 w-full"
                            required
                        >
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-semibold mb-2">Domain</label>

                        <input
                            type="text"
                            name="domain"
                            value="{{ old('domain', $website->domain) }}"
                            class="border rounded px-3 py-2 w-full"
                            required
                        >
                    </div>

                    <div class="mb-4">
                        <label class="inline-flex items-center">
                            <input
                                type="checkbox"
                                name="verify_domain"
                                value="1"
                                class="rounded"
                                @checked(old('verify_domain', $website->verify_domain))
                            >

                            <span class="ml-2 text-sm">
                                Enable domain verification
                            </span>
                        </label>
                    </div>

                    <div class="mb-6">
                        <label class="inline-flex items-center">
                            <input
                                type="checkbox"
                                name="is_active"
                                value="1"
                                class="rounded"
                                @checked(old('is_active', $website->is_active))
                            >

                            <span class="ml-2 text-sm">
                                Website active
                            </span>
                        </label>
                    </div>

                    <div class="flex justify-between">
                        <a href="{{ route('admin.websites.show', $website) }}" class="px-4 py-2 border rounded">
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