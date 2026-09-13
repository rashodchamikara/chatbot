<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2 text-sm text-slate-500">
                    <a href="{{ route('admin.channels.index') }}" class="hover:text-emerald-600">Channels</a>
                    <span>/</span>
                    <span>Add WhatsApp</span>
                </div>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Connect WhatsApp Business</h1>
                <p class="mt-1 text-sm text-slate-500">Add a WhatsApp-only customer channel. No website or domain is required.</p>
            </div>

            <a href="{{ route('admin.channels.index') }}"
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
                action="{{ route('admin.channels.whatsapp.store') }}"
                x-data="{ submitting: false, showToken: false }"
                @submit="submitting = true"
                class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_340px]"
            >
                @csrf

                <div class="space-y-6">
                    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-6 py-5">
                            <div class="flex items-start gap-4">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
                                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 11.5a8.1 8.1 0 0 1-8.4 8.1 8.6 8.6 0 0 1-3.7-.9L4 20l1.3-3.6A8 8 0 1 1 20 11.5Z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-base font-semibold text-slate-900">WhatsApp connection details</h2>
                                    <p class="mt-1 text-sm leading-6 text-slate-500">Enter the three Meta API values for the WhatsApp number. We verify them immediately.</p>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-6 p-6">
                            @if(auth()->user()->isSuperAdmin())
                                <div>
                                    <label for="tenant_id" class="block text-sm font-semibold text-slate-700">Tenant</label>
                                    <select
                                        id="tenant_id"
                                        name="tenant_id"
                                        required
                                        class="mt-2 block w-full rounded-xl border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                    >
                                        <option value="">Select tenant</option>
                                        @foreach($tenants as $tenant)
                                            <option value="{{ $tenant->id }}" @selected((string) old('tenant_id') === (string) $tenant->id)>
                                                {{ $tenant->company_name ?: $tenant->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="mt-1.5 text-xs text-slate-500">Tenant admins do not see this field; their own tenant is selected automatically.</p>
                                </div>
                            @endif

                            <div>
                                <label for="name" class="block text-sm font-semibold text-slate-700">Connection name</label>
                                <input
                                    id="name"
                                    type="text"
                                    name="name"
                                    value="{{ old('name') }}"
                                    required
                                    maxlength="191"
                                    placeholder="Example: Main Sales WhatsApp"
                                    class="mt-2 block w-full rounded-xl border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                >
                                <p class="mt-1.5 text-xs text-slate-500">This is the internal label your team will see in Channels and the Inbox.</p>
                            </div>

                            <div class="grid gap-5 md:grid-cols-2">
                                <div>
                                    <label for="business_account_id" class="block text-sm font-semibold text-slate-700">WABA ID</label>
                                    <input
                                        id="business_account_id"
                                        type="text"
                                        inputmode="numeric"
                                        name="business_account_id"
                                        value="{{ old('business_account_id') }}"
                                        required
                                        maxlength="191"
                                        autocomplete="off"
                                        placeholder="123456789012345"
                                        class="mt-2 block w-full rounded-xl border-slate-300 font-mono text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                    >
                                    <p class="mt-1.5 text-xs leading-5 text-slate-500">WhatsApp Business Account ID from Meta.</p>
                                </div>

                                <div>
                                    <label for="phone_number_id" class="block text-sm font-semibold text-slate-700">Phone Number ID</label>
                                    <input
                                        id="phone_number_id"
                                        type="text"
                                        inputmode="numeric"
                                        name="phone_number_id"
                                        value="{{ old('phone_number_id') }}"
                                        required
                                        maxlength="191"
                                        autocomplete="off"
                                        placeholder="109876543210987"
                                        class="mt-2 block w-full rounded-xl border-slate-300 font-mono text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                    >
                                    <p class="mt-1.5 text-xs leading-5 text-slate-500">Meta's Phone Number ID, not the visible +94 / +1 number.</p>
                                </div>
                            </div>

                            <div>
                                <div class="flex items-center justify-between gap-3">
                                    <label for="access_token" class="block text-sm font-semibold text-slate-700">Meta access token</label>
                                    <span class="text-xs font-medium text-emerald-600">Stored encrypted</span>
                                </div>

                                <div class="relative mt-2">
                                    <input
                                        id="access_token"
                                        :type="showToken ? 'text' : 'password'"
                                        name="access_token"
                                        required
                                        minlength="20"
                                        autocomplete="new-password"
                                        placeholder="Paste Meta access token"
                                        class="block w-full rounded-xl border-slate-300 pr-20 font-mono text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                    >
                                    <button
                                        type="button"
                                        @click="showToken = !showToken"
                                        class="absolute inset-y-0 right-0 inline-flex items-center px-4 text-xs font-semibold text-slate-500 hover:text-slate-800"
                                        x-text="showToken ? 'Hide' : 'Show'"
                                    ></button>
                                </div>

                                <div class="mt-2 flex items-start gap-2 rounded-xl bg-amber-50 px-3 py-2.5 text-xs leading-5 text-amber-800 ring-1 ring-amber-100">
                                    <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 4.6 2.8 18a2 2 0 0 0 1.75 3h14.9a2 2 0 0 0 1.75-3L13.7 4.6a2 2 0 0 0-3.4 0Z"/>
                                    </svg>
                                    <span>The token is never shown again after saving. Later we can replace this manual step with Meta Embedded Signup.</span>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-base font-semibold text-slate-900">Automatic setup</h2>
                        <div class="mt-5 grid gap-4 sm:grid-cols-3">
                            <div class="rounded-xl bg-slate-50 p-4">
                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-sm font-bold text-emerald-700">1</div>
                                <p class="mt-3 text-sm font-semibold text-slate-800">AI agent</p>
                                <p class="mt-1 text-xs leading-5 text-slate-500">The tenant's default AI agent is reused or created automatically.</p>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-4">
                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-sm font-bold text-emerald-700">2</div>
                                <p class="mt-3 text-sm font-semibold text-slate-800">Meta verification</p>
                                <p class="mt-1 text-xs leading-5 text-slate-500">WABA, Phone Number ID and token are checked against Meta.</p>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-4">
                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-sm font-bold text-emerald-700">3</div>
                                <p class="mt-3 text-sm font-semibold text-slate-800">Ready for inbox</p>
                                <p class="mt-1 text-xs leading-5 text-slate-500">The verified number becomes an active omnichannel connection.</p>
                            </div>
                        </div>
                    </section>
                </div>

                <aside class="space-y-6 lg:sticky lg:top-6 lg:self-start">
                    <section class="rounded-2xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-white p-5 shadow-sm">
                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-600 text-white shadow-sm">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 11.5a8.1 8.1 0 0 1-8.4 8.1 8.6 8.6 0 0 1-3.7-.9L4 20l1.3-3.6A8 8 0 1 1 20 11.5Z"/>
                            </svg>
                        </div>
                        <h2 class="mt-4 text-base font-semibold text-slate-900">No website needed</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">This creates a standalone WhatsApp channel with <code class="rounded bg-slate-100 px-1 py-0.5 text-xs">website_id = NULL</code>.</p>
                    </section>

                    <section class="rounded-2xl border border-blue-200 bg-blue-50 p-5 shadow-sm">
                        <h2 class="text-sm font-semibold text-blue-950">For WhatsApp-only customers</h2>
                        <p class="mt-2 text-sm leading-6 text-blue-900/80">After connecting, send them to <strong>Knowledge</strong> so they can add products, prices, services, FAQs and policies manually or upload documents.</p>
                    </section>

                    <button
                        type="submit"
                        :disabled="submitting"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-sm shadow-emerald-600/20 transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        <svg x-show="!submitting" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6"/>
                        </svg>
                        <svg x-show="submitting" x-cloak class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3"/>
                            <path class="opacity-75" fill="currentColor" d="M12 3a9 9 0 0 1 9 9h-3a6 6 0 0 0-6-6V3Z"/>
                        </svg>
                        <span x-text="submitting ? 'Verifying with Meta...' : 'Connect WhatsApp'"></span>
                    </button>
                </aside>
            </form>
        </div>
    </div>
</x-app-layout>
