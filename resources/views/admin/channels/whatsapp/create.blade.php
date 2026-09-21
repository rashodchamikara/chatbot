@php
    $metaAppId = trim((string) config('services.meta.app_id'));
    $metaConfigId = trim((string) config('services.meta.embedded_signup_config_id'));
    $metaPartnerSolutionId = trim((string) config('services.meta.partner_solution_id'));
    $metaGraphVersion = trim((string) config('services.meta.graph_version', 'v26.0'));

    $embeddedSignupReady = $metaAppId !== ''
        && $metaConfigId !== ''
        && trim((string) config('services.meta.app_secret')) !== '';
@endphp
<style>
    #meta_connect_button_text {
        display: inline-flex;
        align-items: center;
        justify-content: center;

        background-color: #1877F2 !important;
        color: #ffffff !important;

        border: 1px solid #1877F2 !important;
        border-radius: 6px;

        padding: 12px 24px;

        font-size: 15px;
        font-weight: 600;

        cursor: pointer;
        text-decoration: none !important;

        transition: background-color 0.2s ease;
    }

    #meta_connect_button_text:hover {
        background-color: #166FE5 !important;
        color: #ffffff !important;
        border-color: #166FE5 !important;
    }

    #meta_connect_button_text:focus,
    #meta_connect_button_text:active {
        background-color: #1464D2 !important;
        color: #ffffff !important;
    }

    #meta_connect_button_text:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
</style>
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
                <p class="mt-1 text-sm text-slate-500">Connect a customer's WhatsApp Business account securely through Meta.</p>
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

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
                <div class="space-y-6">
                    <section class="overflow-hidden rounded-2xl border border-emerald-200 bg-white shadow-sm">
                        <div class="border-b border-emerald-100 bg-gradient-to-r from-emerald-50 to-white px-6 py-5">
                            <div class="flex items-start gap-4">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-600 text-white shadow-sm">
                                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 11.5a8.1 8.1 0 0 1-8.4 8.1 8.6 8.6 0 0 1-3.7-.9L4 20l1.3-3.6A8 8 0 1 1 20 11.5Z"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h2 class="text-lg font-semibold text-slate-900">Connect with Meta</h2>
                                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Recommended</span>
                                    </div>
                                    <p class="mt-1 text-sm leading-6 text-slate-600">
                                        The customer signs in to Meta and connects the WhatsApp Business number they already use. WhatsApp Business App Coexistence keeps the number available in the mobile app while also connecting it to ChatNivo through Meta Cloud API.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-6 p-6">
                            @if(auth()->user()->isSuperAdmin())
                                <div>
                                    <label for="embedded_tenant_id" class="block text-sm font-semibold text-slate-700">Tenant</label>
                                    <select
                                        id="embedded_tenant_id"
                                        class="mt-2 block w-full rounded-xl border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                    >
                                        <option value="">Select tenant</option>
                                        @foreach($tenants as $tenant)
                                            <option value="{{ $tenant->id }}" @selected((string) old('tenant_id') === (string) $tenant->id)>
                                                {{ $tenant->company_name ?: $tenant->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="mt-1.5 text-xs text-slate-500">Required for super admins. Tenant admins are assigned automatically.</p>
                                </div>
                            @endif

                            <div>
                                <label for="embedded_connection_name" class="block text-sm font-semibold text-slate-700">
                                    Connection name <span class="font-normal text-slate-400">(optional)</span>
                                </label>
                                <input
                                    id="embedded_connection_name"
                                    type="text"
                                    maxlength="191"
                                    placeholder="Example: Main Sales WhatsApp"
                                    class="mt-2 block w-full rounded-xl border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                >
                                <p class="mt-1.5 text-xs text-slate-500">If left empty, ChatNivo uses the WhatsApp verified business name or phone number.</p>
                            </div>

                            @if(!$embeddedSignupReady)
                                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-900">
                                    Meta Embedded Signup is not configured on this server yet. Add <code class="font-semibold">META_APP_ID</code>, <code class="font-semibold">META_APP_SECRET</code> and <code class="font-semibold">META_EMBEDDED_SIGNUP_CONFIG_ID</code>, then clear Laravel's config cache.
                                </div>
                            @endif

                            <div id="embedded_signup_message" class="hidden rounded-xl border px-4 py-3 text-sm leading-6"></div>

                            <button
                                id="meta_connect_button"
                                type="button"
                                @disabled(!$embeddedSignupReady)
                                class="inline-flex w-full items-center justify-center gap-3 rounded-xl bg-[#1877F2] px-5 py-3.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#166fe5] focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto"
                            >
                                <svg id="meta_connect_icon" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.435H7.078v-3.492h3.047V9.413c0-3.028 1.792-4.7 4.533-4.7 1.312 0 2.686.236 2.686.236v2.974h-1.513c-1.49 0-1.956.931-1.956 1.887v2.263h3.328l-.532 3.492h-2.796V24C19.612 23.094 24 18.1 24 12.073Z"/>
                                </svg>
                                <svg id="meta_connect_spinner" class="hidden h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3"/>
                                    <path class="opacity-75" fill="currentColor" d="M12 3a9 9 0 0 1 9 9h-3a6 6 0 0 0-6-6V3Z"/>
                                </svg>
                                <span id="meta_connect_button_text">Connect Existing WhatsApp Business Number</span>
                            </button>

                            <div class="grid gap-3 sm:grid-cols-3">
                                <div class="rounded-xl bg-slate-50 p-4">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-sm font-bold text-emerald-700">1</div>
                                    <p class="mt-3 text-sm font-semibold text-slate-800">Authorize</p>
                                    <p class="mt-1 text-xs leading-5 text-slate-500">Customer signs in and approves ChatNivo through Meta.</p>
                                </div>
                                <div class="rounded-xl bg-slate-50 p-4">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-sm font-bold text-emerald-700">2</div>
                                    <p class="mt-3 text-sm font-semibold text-slate-800">Verify number</p>
                                    <p class="mt-1 text-xs leading-5 text-slate-500">Meta connects and verifies the existing WhatsApp Business App number.</p>
                                </div>
                                <div class="rounded-xl bg-slate-50 p-4">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-sm font-bold text-emerald-700">3</div>
                                    <p class="mt-3 text-sm font-semibold text-slate-800">Activate</p>
                                    <p class="mt-1 text-xs leading-5 text-slate-500">ChatNivo registers the number, subscribes webhooks and activates the AI channel.</p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <details class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-6 py-5">
                            <div>
                                <h2 class="text-base font-semibold text-slate-900">Advanced: connect manually</h2>
                                <p class="mt-1 text-sm text-slate-500">Keep the existing WABA ID, Phone Number ID and access-token setup as a support fallback.</p>
                            </div>
                            <svg class="h-5 w-5 shrink-0 text-slate-400 transition group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/>
                            </svg>
                        </summary>

                        <form
                            method="POST"
                            action="{{ route('admin.channels.whatsapp.store') }}"
                            x-data="{ submitting: false, showToken: false }"
                            @submit="submitting = true"
                            class="border-t border-slate-100 p-6"
                        >
                            @csrf

                            <div class="space-y-6">
                                @if(auth()->user()->isSuperAdmin())
                                    <div>
                                        <label for="manual_tenant_id" class="block text-sm font-semibold text-slate-700">Tenant</label>
                                        <select
                                            id="manual_tenant_id"
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
                                    </div>
                                @endif

                                <div>
                                    <label for="manual_name" class="block text-sm font-semibold text-slate-700">Connection name</label>
                                    <input
                                        id="manual_name"
                                        type="text"
                                        name="name"
                                        value="{{ old('name') }}"
                                        required
                                        maxlength="191"
                                        placeholder="Example: Main Sales WhatsApp"
                                        class="mt-2 block w-full rounded-xl border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                    >
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
                                </div>

                                <button
                                    type="submit"
                                    :disabled="submitting"
                                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    <span x-text="submitting ? 'Verifying with Meta...' : 'Connect manually'"></span>
                                </button>
                            </div>
                        </form>
                    </details>
                </div>

                <aside class="space-y-6 lg:sticky lg:top-6 lg:self-start">
                    <section class="rounded-2xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-white p-5 shadow-sm">
                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-600 text-white shadow-sm">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 11.5a8.1 8.1 0 0 1-8.4 8.1 8.6 8.6 0 0 1-3.7-.9L4 20l1.3-3.6A8 8 0 1 1 20 11.5Z"/>
                            </svg>
                        </div>
                        <h2 class="mt-4 text-base font-semibold text-slate-900">Meta Embedded Signup v4 + Coexistence</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">The customer connects an existing WhatsApp Business App number without giving up the mobile app. ChatNivo stores the returned business token encrypted and reuses your existing omnichannel pipeline.</p>
                    </section>

                    <section class="rounded-2xl border border-blue-200 bg-blue-50 p-5 shadow-sm">
                        <h2 class="text-sm font-semibold text-blue-950">What happens automatically</h2>
                        <ul class="mt-3 space-y-2 text-sm leading-6 text-blue-900/80">
                            <li>• WABA and phone ownership validation</li>
                            <li>• Cloud API phone registration</li>
                            <li>• Six-digit 2FA PIN generation and encrypted storage</li>
                            <li>• WABA webhook subscription</li>
                            <li>• AI-agent assignment</li>
                            <li>• Existing WhatsApp health check</li>
                        </ul>
                    </section>

                    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 class="text-sm font-semibold text-slate-900">No website required</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">The connection remains a standalone WhatsApp channel with <code class="rounded bg-slate-100 px-1 py-0.5 text-xs">website_id = NULL</code>.</p>
                    </section>
                </aside>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const metaAppId = @json($metaAppId);
            const configId = @json($metaConfigId);
            const partnerSolutionId = @json($metaPartnerSolutionId);
            const graphVersion = @json($metaGraphVersion);
            const completeUrl = @json(route('admin.channels.whatsapp.embedded.complete'));
            const csrfToken = @json(csrf_token());
            const isSuperAdmin = @json(auth()->user()->isSuperAdmin());
            const embeddedReady = @json($embeddedSignupReady);

            const button = document.getElementById('meta_connect_button');
            const buttonText = document.getElementById('meta_connect_button_text');
            const buttonIcon = document.getElementById('meta_connect_icon');
            const spinner = document.getElementById('meta_connect_spinner');
            const messageBox = document.getElementById('embedded_signup_message');

            let signupState = freshState();

            function freshState() {
                return {
                    code: null,
                    wabaId: null,
                    phoneNumberId: null,
                    businessId: null,
                    submitted: false,
                };
            }

            function setBusy(busy, text = null) {
                if (!button) return;

                button.disabled = busy || !embeddedReady;
                buttonIcon?.classList.toggle('hidden', busy);
                spinner?.classList.toggle('hidden', !busy);

                if (buttonText) {
                    buttonText.textContent = text || (busy
                        ? 'Completing Meta setup...'
                        : 'Connect Existing WhatsApp Business Number');
                }
            }

            function showMessage(message, type = 'info') {
                if (!messageBox) return;

                const classes = {
                    info: ['border-blue-200', 'bg-blue-50', 'text-blue-900'],
                    success: ['border-emerald-200', 'bg-emerald-50', 'text-emerald-900'],
                    error: ['border-red-200', 'bg-red-50', 'text-red-900'],
                    warning: ['border-amber-200', 'bg-amber-50', 'text-amber-900'],
                };

                messageBox.className = 'rounded-xl border px-4 py-3 text-sm leading-6';
                (classes[type] || classes.info).forEach((className) => {
                    messageBox.classList.add(className);
                });
                messageBox.textContent = message;
                messageBox.classList.remove('hidden');
            }

            function firstValidationMessage(payload) {
                if (payload?.errors && typeof payload.errors === 'object') {
                    for (const messages of Object.values(payload.errors)) {
                        if (Array.isArray(messages) && messages.length) {
                            return messages[0];
                        }
                    }
                }

                return payload?.message || 'Meta onboarding could not be completed.';
            }

            function isTrustedFacebookOrigin(origin) {
                try {
                    const hostname = new URL(origin).hostname.toLowerCase();
                    return hostname === 'facebook.com' || hostname.endsWith('.facebook.com');
                } catch (error) {
                    return false;
                }
            }

            async function tryCompleteSignup() {
                if (
                    signupState.submitted
                    || !signupState.code
                    || !signupState.wabaId
                    || !signupState.phoneNumberId
                ) {
                    return;
                }

                const tenantId = isSuperAdmin
                    ? document.getElementById('embedded_tenant_id')?.value
                    : null;

                if (isSuperAdmin && !tenantId) {
                    signupState.submitted = false;
                    setBusy(false);
                    showMessage('Select the tenant before connecting WhatsApp.', 'error');
                    return;
                }

                signupState.submitted = true;
                setBusy(true, 'Registering WhatsApp...');
                showMessage('Meta authorization completed. ChatNivo is registering the number and enabling webhooks.', 'info');

                const payload = {
                    code: signupState.code,
                    waba_id: signupState.wabaId,
                    phone_number_id: signupState.phoneNumberId,
                    business_id: signupState.businessId || null,
                    name: document.getElementById('embedded_connection_name')?.value || null,
                };

                if (isSuperAdmin) {
                    payload.tenant_id = tenantId;
                }

                try {
                    const response = await fetch(completeUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify(payload),
                    });

                    let result = {};

                    try {
                        result = await response.json();
                    } catch (error) {
                        result = {};
                    }

                    if (!response.ok) {
                        throw new Error(firstValidationMessage(result));
                    }

                    if (result.success) {
                        showMessage(result.message || 'WhatsApp connected successfully.', 'success');
                    } else {
                        showMessage(result.message || 'Meta signup completed, but provisioning needs attention.', 'warning');
                    }

                    if (result.redirect_url) {
                        window.location.assign(result.redirect_url);
                        return;
                    }

                    setBusy(false);
                } catch (error) {
                    signupState = freshState();
                    setBusy(false);
                    showMessage(
                        error instanceof Error
                            ? error.message
                            : 'Meta onboarding could not be completed. Please retry.',
                        'error'
                    );
                }
            }

            window.addEventListener('message', function (event) {
                if (!isTrustedFacebookOrigin(event.origin)) {
                    return;
                }

                let data = event.data;

                if (typeof data === 'string') {
                    try {
                        data = JSON.parse(data);
                    } catch (error) {
                        return;
                    }
                }

                if (!data || data.type !== 'WA_EMBEDDED_SIGNUP') {
                    return;
                }

                if (
                    data.event === 'FINISH'
                    || data.event === 'FINISH_WHATSAPP_BUSINESS_APP_ONBOARDING'
                ) {
                    signupState.wabaId = data.data?.waba_id || null;
                    signupState.phoneNumberId =
                        data.data?.phone_number_id
                        || data.data?.phoneNumberId
                        || null;
                    signupState.businessId =
                        data.data?.business_id
                        || data.data?.businessId
                        || null;

                    if (!signupState.wabaId) {
                        setBusy(false);
                        showMessage(
                            'Meta completed signup but did not return the WhatsApp Business Account ID. Please retry the signup flow.',
                            'error'
                        );
                        return;
                    }

                    /*
                     * Coexistence onboarding can return only the WABA ID in the
                     * browser completion event. When Meta also returns the Phone
                     * Number ID, the existing backend can complete immediately.
                     *
                     * If phone_number_id is omitted, the backend must discover
                     * the number from the WABA after exchanging the OAuth code.
                     * Until that backend compatibility update is installed, show
                     * an explicit message instead of submitting an invalid request.
                     */
                    if (!signupState.phoneNumberId) {
                        setBusy(false);
                        showMessage(
                            'Meta connected the existing WhatsApp Business account successfully, but did not return a Phone Number ID in the browser event. Coexistence onboarding can work this way. ChatNivo now needs the backend WABA phone-discovery update before final activation.',
                            'warning'
                        );
                        return;
                    }

                    tryCompleteSignup();
                    return;
                }

                if (data.event === 'CANCEL') {
                    signupState = freshState();
                    setBusy(false);
                    showMessage(
                        'Meta signup was cancelled' +
                        (data.data?.current_step ? ' at ' + data.data.current_step + '.' : '.'),
                        'warning'
                    );
                    return;
                }

                if (data.event === 'ERROR') {
                    signupState = freshState();
                    setBusy(false);
                    showMessage(
                        data.data?.error_message || 'Meta reported an error during WhatsApp signup.',
                        'error'
                    );
                }
            });

            window.fbAsyncInit = function () {
                if (!metaAppId) return;

                FB.init({
                    appId: metaAppId,
                    autoLogAppEvents: true,
                    xfbml: true,
                    version: graphVersion,
                });
            };

            function loadFacebookSdk() {
                if (document.getElementById('facebook-jssdk')) {
                    return;
                }

                const script = document.createElement('script');
                script.id = 'facebook-jssdk';
                script.async = true;
                script.defer = true;
                script.crossOrigin = 'anonymous';
                script.src = 'https://connect.facebook.net/en_US/sdk.js';
                document.body.appendChild(script);
            }

            function launchEmbeddedSignup() {
                if (!embeddedReady) {
                    showMessage('Meta Embedded Signup is not configured on this server.', 'error');
                    return;
                }

                if (isSuperAdmin && !document.getElementById('embedded_tenant_id')?.value) {
                    showMessage('Select the tenant before connecting WhatsApp.', 'error');
                    return;
                }

                if (typeof FB === 'undefined') {
                    showMessage('Meta login is still loading. Please click the button again.', 'warning');
                    return;
                }

                signupState = freshState();
                setBusy(true, 'Waiting for Meta...');
                showMessage('Complete the WhatsApp Business App connection in the Meta window. Keep the business number active in the WhatsApp Business mobile app.', 'info');

                const extras = {
                    setup: {},

                    /*
                     * Required for WhatsApp Business App Coexistence.
                     * This tells Meta to offer onboarding of a number that is
                     * already being used in the WhatsApp Business mobile app.
                     */
                    featureType: 'whatsapp_business_app_onboarding',

                    /*
                     * Meta's Coexistence completion messages currently use the
                     * v3 session-information payload. Keeping this explicit
                     * improves compatibility across Embedded Signup versions.
                     */
                    sessionInfoVersion: '3',
                };

                /*
                 * Partner Solution ID is required only when your Meta setup
                 * uses a multi-partner/solution relationship. Direct Tech
                 * Provider setups can leave META_PARTNER_SOLUTION_ID empty.
                 */
                if (partnerSolutionId) {
                    extras.setup.solutionID = partnerSolutionId;
                }

                FB.login(function (response) {
                    const code = response?.authResponse?.code;

                    if (!code) {
                        signupState = freshState();
                        setBusy(false);
                        showMessage(
                            'Meta authorization was not completed. Please retry and finish the Meta signup window.',
                            'warning'
                        );
                        return;
                    }

                    signupState.code = code;
                    setBusy(true, 'Finalizing Meta signup...');
                    tryCompleteSignup();
                }, {
                    config_id: configId,
                    auth_type: 'rerequest',
                    response_type: 'code',
                    override_default_response_type: true,
                    extras: extras,
                });
            }

            button?.addEventListener('click', launchEmbeddedSignup);

            if (embeddedReady) {
                loadFacebookSdk();
            }
        })();
    </script>
</x-app-layout>