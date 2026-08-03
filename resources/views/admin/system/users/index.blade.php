<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900">User Management</h1>
                <p class="mt-1 text-sm text-slate-500">Create, edit, suspend, activate, and delete users.</p>
            </div>
            <a href="{{ route('admin.system.users.create') }}" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Create user</a>
        </div>
    </x-slot>

    <div class="min-h-screen bg-slate-50 py-8">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            @if(session('success'))<div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif

            <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <form method="GET" action="{{ route('admin.system.users.index') }}" class="grid grid-cols-1 gap-3 lg:grid-cols-[minmax(0,1fr)_180px_180px_auto_auto]">
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Search name or email" class="h-11 rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <select name="role" class="h-11 rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"><option value="">All roles</option>@foreach($roles as $value => $label)<option value="{{ $value }}" @selected(request('role') === $value)>{{ $label }}</option>@endforeach</select>
                    <select name="status" class="h-11 rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"><option value="">All statuses</option>@foreach($statuses as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select>
                    <button class="h-11 rounded-xl bg-slate-900 px-5 text-sm font-semibold text-white hover:bg-slate-800">Apply</button>
                    <a href="{{ route('admin.system.users.index') }}" class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-5 text-sm font-semibold text-slate-600 hover:bg-slate-50">Clear</a>
                </form>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full table-auto border-collapse">
                        <thead class="bg-slate-50"><tr class="border-b border-slate-200"><th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">User</th><th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Role</th><th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Tenant</th><th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th><th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Last login</th><th class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th></tr></thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse($users as $user)
                                <tr class="transition hover:bg-slate-50">
                                    <td class="px-5 py-4"><p class="text-sm font-semibold text-slate-900">{{ $user->name }}</p><p class="mt-1 text-xs text-slate-500">{{ $user->email }}</p></td>
                                    <td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $user->isSuperAdmin() ? 'bg-violet-50 text-violet-700 ring-violet-200' : 'bg-blue-50 text-blue-700 ring-blue-200' }}">{{ $roles[$user->role] ?? ucfirst($user->role) }}</span></td>
                                    <td class="px-5 py-4 text-sm text-slate-600">{{ $user->tenant->name ?? 'System' }}</td>
                                    <td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $user->isSuspended() ? 'bg-red-50 text-red-700 ring-red-200' : 'bg-emerald-50 text-emerald-700 ring-emerald-200' }}">{{ $statuses[$user->status] ?? ucfirst($user->status) }}</span></td>
                                    <td class="px-5 py-4 text-sm text-slate-600">{{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never' }}</td>
                                    <td class="px-5 py-4 text-right"><div class="flex justify-end gap-2"><a href="{{ route('admin.system.users.edit', $user) }}" class="inline-flex h-9 items-center rounded-lg border border-slate-200 px-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Edit</a>@if(auth()->id() !== $user->id)@if($user->isSuspended())<form method="POST" action="{{ route('admin.system.users.activate', $user) }}">@csrf<button class="inline-flex h-9 items-center rounded-lg border border-emerald-200 px-3 text-sm font-semibold text-emerald-700 hover:bg-emerald-50">Activate</button></form>@else<form method="POST" action="{{ route('admin.system.users.suspend', $user) }}" onsubmit="return confirm('Suspend this user account?')">@csrf<button class="inline-flex h-9 items-center rounded-lg border border-amber-200 px-3 text-sm font-semibold text-amber-700 hover:bg-amber-50">Suspend</button></form>@endif<form method="POST" action="{{ route('admin.system.users.destroy', $user) }}" onsubmit="return confirm('Delete this user? This soft-deletes the account.')">@csrf @method('DELETE')<button class="inline-flex h-9 items-center rounded-lg border border-red-200 px-3 text-sm font-semibold text-red-600 hover:bg-red-50">Delete</button></form>@else<span class="inline-flex h-9 items-center rounded-lg bg-slate-100 px-3 text-xs font-semibold text-slate-500">Current user</span>@endif</div></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-6 py-16 text-center text-sm text-slate-500">No users found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
            <div class="mt-6">{{ $users->links() }}</div>
        </div>
    </div>
</x-app-layout>
