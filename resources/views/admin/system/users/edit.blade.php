<x-app-layout>
    <x-slot name="header"><div class="flex items-center justify-between"><div><h1 class="text-2xl font-semibold tracking-tight text-slate-900">Edit user</h1><p class="mt-1 text-sm text-slate-500">Update user identity, role, tenant assignment, or password.</p></div><a href="{{ route('admin.system.users.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Back</a></div></x-slot>
    <div class="min-h-screen bg-slate-50 py-8"><div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        @if($errors->any())<div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @php
            $currentTenantMode = old(
                'tenant_mode',
                $user->tenant_id ? 'existing' : 'none'
            );
        @endphp
        <form method="POST" action="{{ route('admin.system.users.update', $user) }}" x-data="{ submitting: false }" @submit="submitting = true" class="rounded-2xl border border-slate-200 bg-white shadow-sm">@csrf @method('PATCH')
            <div class="space-y-6 p-6">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Name</label>
                        <input name="name" value="{{ old('name', $user->name) }}" required class="mt-2 block w-full rounded-xl border-slate-300">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Email</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="mt-2 block w-full rounded-xl border-slate-300">
                    </div>
                </div>
                <div
                    class="grid grid-cols-1 gap-5 sm:grid-cols-2"
                    x-data="{
                        role: @js(old('role', $user->role)),
                        tenantMode: @js($currentTenantMode),

                        normalizeTenantMode() {
                            if (this.role === 'agent') {
                                this.tenantMode = 'existing';
                            }
                        }
                    }"
                    x-init="normalizeTenantMode()"
                    x-effect="normalizeTenantMode()"
                >
                    <div>
                        <label for="role" class="block text-sm font-semibold text-slate-700">
                            Role
                        </label>

                        <select
                            id="role"
                            name="role"
                            x-model="role"
                            required
                            class="mt-2 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            @foreach($roles as $value => $label)
                                <option value="{{ $value }}">
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="tenant_mode" class="block text-sm font-semibold text-slate-700">
                            Tenant assignment
                        </label>

                        <select
                            id="tenant_mode"
                            name="tenant_mode"
                            x-model="tenantMode"
                            class="mt-2 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="none" x-show="role !== 'agent'">
                                No tenant
                            </option>

                            <option value="existing">
                                Select existing tenant
                            </option>

                            <option value="new" x-show="role !== 'agent'">
                                Create new tenant
                            </option>
                        </select>

                        <p class="mt-1.5 text-xs leading-5 text-slate-500">
                            Agents require an existing tenant. Tenant admins can be unassigned, assigned to an existing tenant, or assigned to a new tenant.
                        </p>

                        @error('tenant_mode')
                            <p class="mt-1.5 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div x-show="tenantMode === 'existing'" x-cloak>
                        <label for="tenant_id" class="block text-sm font-semibold text-slate-700">
                            Existing tenant
                        </label>

                        <select
                            id="tenant_id"
                            name="tenant_id"
                            class="mt-2 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="">
                                Select tenant
                            </option>

                            @foreach($tenants as $tenant)
                                <option
                                    value="{{ $tenant->id }}"
                                    @selected(old('tenant_id', $user->tenant_id) == $tenant->id)
                                >
                                    {{ $tenant->name }}
                                </option>
                            @endforeach
                        </select>

                        @error('tenant_id')
                            <p class="mt-1.5 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div x-show="tenantMode === 'new'" x-cloak>
                        <label for="new_tenant_name" class="block text-sm font-semibold text-slate-700">
                            New tenant name
                        </label>

                        <input
                            id="new_tenant_name"
                            name="new_tenant_name"
                            type="text"
                            value="{{ old('new_tenant_name') }}"
                            maxlength="255"
                            placeholder="Example: ABC Holdings Pvt Ltd"
                            class="mt-2 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >

                        @error('new_tenant_name')
                            <p class="mt-1.5 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div
                        x-show="role === 'super_admin'"
                        x-cloak
                        class="rounded-xl border border-violet-200 bg-violet-50 p-4 text-sm text-violet-800"
                    >
                        Super admins have system-wide access. Tenant assignment is optional.
                    </div>

                    <div
                        x-show="role === 'tenant_admin'"
                        x-cloak
                        class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800"
                    >
                        Tenant admins can remain unassigned, or they can be attached to an existing or newly created tenant.
                    </div>

                    <div
                        x-show="role === 'agent'"
                        x-cloak
                        class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800"
                    >
                        Agents must belong to an existing tenant.
                    </div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                    Current status: <strong>{{ $statuses[$user->status] ?? ucfirst($user->status) }}</strong>. Use Suspend / Activate from the user list.
                </div>
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">New password</label>
                        <input type="password" name="password" class="mt-2 block w-full rounded-xl border-slate-300">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Confirm new password</label>
                        <input type="password" name="password_confirmation" class="mt-2 block w-full rounded-xl border-slate-300">
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-3 border-t border-slate-100 bg-slate-50 px-6 py-5">
                <a href="{{ route('admin.system.users.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Cancel</a>
                <button :disabled="submitting" class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                    <span x-text="submitting ? 'Saving…' : 'Save changes'"></span>
                </button>
            </div>
            
        </form>
    </div></div>
</x-app-layout>
