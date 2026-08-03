<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->with(['tenant', 'suspendedBy'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->search);
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('role'), fn ($query) => $query->where('role', $request->role))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.system.users.index', [
            'users' => $users,
            'roles' => $this->roles(),
            'statuses' => $this->statuses(),
        ]);
    }

    public function create(): View
    {
        return view('admin.system.users.create', [
            'tenants' => Tenant::query()->orderBy('name')->get(),
            'roles' => $this->roles(),
            'statuses' => $this->statuses(),
        ]);
    }

    public function store(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_id' => [
                'nullable',
                'integer',
                Rule::exists('tenants', 'id'),
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],

            'role' => [
                'required',
                Rule::in(array_keys($this->roles())),
            ],

            'password' => [
                'required',
                'confirmed',
                Password::defaults(),
            ],

            'status' => [
                'required',
                Rule::in(array_keys($this->statuses())),
            ],
        ]);

        if (
            $validated['role'] === User::ROLE_AGENT &&
            empty($validated['tenant_id'])
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'tenant_id' => 'Tenant is required when creating an agent.',
                ]);
        }

        $user = User::create([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'name' => $validated['name'],
            'email' => strtolower(trim($validated['email'])),
            'password' => $validated['password'],
            'role' => $validated['role'],
            'status' => $validated['status'],
            'suspended_at' => $validated['status'] === User::STATUS_SUSPENDED
                ? now()
                : null,
            'suspended_by' => $validated['status'] === User::STATUS_SUSPENDED
                ? $request->user()->id
                : null,
        ]);

        $auditLogger->log(
            $request,
            $user->isSuperAdmin() ? 'super_admin.created' : 'user.created',
            $user,
            [],
            $user->only([
                'tenant_id',
                'name',
                'email',
                'role',
                'status',
            ])
        );

        return redirect()
            ->route('admin.system.users.index')
            ->with(
                'success',
                "User {$user->name} was created successfully."
            );
    }

    public function edit(User $user): View
    {
        return view('admin.system.users.edit', [
            'user' => $user,
            'tenants' => Tenant::query()->orderBy('name')->get(),
            'roles' => $this->roles(),
            'statuses' => $this->statuses(),
        ]);
    }

    public function update(Request $request, User $user, AuditLogger $auditLogger): RedirectResponse {
        $validated = $request->validate([
            'tenant_id' => [
                'nullable',
                'integer',
                Rule::exists('tenants', 'id'),
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->ignore($user->id),
            ],

            'role' => [
                'required',
                Rule::in(array_keys($this->roles())),
            ],

            'password' => [
                'nullable',
                'confirmed',
                Password::defaults(),
            ],
        ]);

        if (
            $validated['role'] === User::ROLE_AGENT &&
            empty($validated['tenant_id'])
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'tenant_id' => 'Tenant is required when assigning the agent role.',
                ]);
        }

        $this->guardLastActiveSuperAdminRoleChange(
            $user,
            $validated['role']
        );

        $oldValues = $user->only([
            'tenant_id',
            'name',
            'email',
            'role',
            'status',
        ]);

        $payload = [
            'tenant_id' => $validated['tenant_id'] ?? null,
            'name' => $validated['name'],
            'email' => strtolower(trim($validated['email'])),
            'role' => $validated['role'],
        ];

        if (filled($validated['password'] ?? null)) {
            $payload['password'] = $validated['password'];
        }

        $user->update($payload);

        $auditLogger->log(
            $request,
            'user.updated',
            $user,
            $oldValues,
            $user->fresh()->only([
                'tenant_id',
                'name',
                'email',
                'role',
                'status',
            ])
        );

        return redirect()
            ->route('admin.system.users.index')
            ->with(
                'success',
                "User {$user->name} was updated successfully."
            );
    }

    public function suspend(Request $request, User $user, AuditLogger $auditLogger): RedirectResponse
    {
        $this->guardSelfAction($request, $user, 'suspend');
        $this->guardLastActiveSuperAdminSuspension($user);
        $oldValues = $user->only(['status','suspended_at','suspended_by']);
        $user->update([
            'status' => User::STATUS_SUSPENDED,
            'suspended_at' => now(),
            'suspended_by' => $request->user()->id,
            'agent_status' => 'offline',
        ]);
        $auditLogger->log($request, 'user.suspended', $user, $oldValues, $user->fresh()->only(['status','suspended_at','suspended_by']));
        return back()->with('success', "{$user->name} has been suspended.");
    }

    public function activate(Request $request, User $user, AuditLogger $auditLogger): RedirectResponse
    {
        $oldValues = $user->only(['status','suspended_at','suspended_by']);
        $user->update([
            'status' => User::STATUS_ACTIVE,
            'suspended_at' => null,
            'suspended_by' => null,
        ]);
        $auditLogger->log($request, 'user.activated', $user, $oldValues, $user->fresh()->only(['status','suspended_at','suspended_by']));
        return back()->with('success', "{$user->name} has been activated.");
    }

    public function destroy(Request $request, User $user, AuditLogger $auditLogger): RedirectResponse
    {
        $this->guardSelfAction($request, $user, 'delete');
        $this->guardLastActiveSuperAdminDeletion($user);
        $oldValues = $user->only(['tenant_id','name','email','role','status']);
        DB::transaction(function () use ($user, $request) {
            $user->update([
                'status' => User::STATUS_SUSPENDED,
                'suspended_at' => now(),
                'suspended_by' => $request->user()->id,
                'agent_status' => 'offline',
            ]);
            $user->delete();
        });
        $auditLogger->log($request, 'user.deleted', $user, $oldValues, ['deleted_at' => now()->toDateTimeString()]);
        return back()->with('success', "{$user->name} has been deleted.");
    }

    private function roles(): array
    {
        return [
            User::ROLE_SUPER_ADMIN => 'Super Admin',
            User::ROLE_TENANT_ADMIN => 'Tenant Admin',
            User::ROLE_AGENT => 'Agent',
        ];
    }

    private function statuses(): array
    {
        return [
            User::STATUS_ACTIVE => 'Active',
            User::STATUS_SUSPENDED => 'Suspended',
        ];
    }

    private function guardSelfAction(Request $request, User $targetUser, string $action): void
    {
        abort_if((int) $request->user()->id === (int) $targetUser->id, 422, "You cannot {$action} your own account.");
    }

    private function guardLastActiveSuperAdminSuspension(User $user): void
    {
        if (!$user->isSuperAdmin()) return;
        $count = User::query()->where('role', User::ROLE_SUPER_ADMIN)->where('status', User::STATUS_ACTIVE)->count();
        abort_if($count <= 1, 422, 'At least one active super admin account is required.');
    }

    private function guardLastActiveSuperAdminDeletion(User $user): void
    {
        $this->guardLastActiveSuperAdminSuspension($user);
    }

    private function guardLastActiveSuperAdminRoleChange(User $user, string $newRole): void
    {
        if (!$user->isSuperAdmin() || $newRole === User::ROLE_SUPER_ADMIN) return;
        $count = User::query()->where('role', User::ROLE_SUPER_ADMIN)->where('status', User::STATUS_ACTIVE)->count();
        abort_if($count <= 1, 422, 'You cannot remove the last active super admin.');
    }
}
