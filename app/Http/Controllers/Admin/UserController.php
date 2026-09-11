<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use App\Support\TwoFactorTrust;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::with(['role', 'roles', 'department'])->latest();

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }
        if ($request->role_id) {
            $query->withRoleId($request->role_id);
        }
        if ($request->has('is_active') && $request->is_active !== '') {
            $query->where('is_active', (bool) $request->is_active);
        }

        $users = $query->paginate(25)->withQueryString();
        $roles = Role::orderBy('display_name')->get();

        return view('admin.users.index', compact('users', 'roles'));
    }

    public function create(): View
    {
        $roles       = Role::orderBy('display_name')->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        return view('admin.users.create', compact('roles', 'departments'));
    }

    public function store(Request $request): RedirectResponse
    {
        $domain = config('kathford.allowed_email_domain', 'kathford.edu.np');

        $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'email'         => [
                'required', 'email', 'unique:users,email',
                function ($attribute, $value, $fail) use ($domain) {
                    if (! str_ends_with(strtolower($value), '@' . strtolower($domain))) {
                        $fail("Email must be from the {$domain} domain.");
                    }
                },
            ],
            'roles'         => ['required', 'array', 'min:1'],
            'roles.*'       => ['distinct', 'exists:roles,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'phone'         => ['nullable', 'string', 'max:20'],
            'designation'   => ['nullable', 'string', 'max:100'],
            'password'      => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        $primaryRoleId     = $request->roles[0];
        $additionalRoleIds = array_slice($request->roles, 1);

        $user = User::create([
            'name'          => $request->name,
            'email'         => $request->email,
            'role_id'       => $primaryRoleId,
            'department_id' => $request->department_id,
            'phone'         => $request->phone,
            'designation'   => $request->designation,
            'password'      => Hash::make($request->password),
            'is_active'     => true,
        ]);
        $user->roles()->sync($additionalRoleIds);

        AuditLog::record(Auth::user(), 'user.created', $user, $user->email);

        return redirect()->route('admin.users.index')
            ->with('success', "User {$user->name} created. Share their initial password securely.");
    }

    public function edit(User $user): View
    {
        $roles       = Role::orderBy('display_name')->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        return view('admin.users.edit', compact('user', 'roles', 'departments'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'roles'         => ['required', 'array', 'min:1'],
            'roles.*'       => ['distinct', 'exists:roles,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'phone'         => ['nullable', 'string', 'max:20'],
            'designation'   => ['nullable', 'string', 'max:100'],
            'is_active'     => ['boolean'],
            'password'      => ['nullable', 'string', 'min:12', 'confirmed'],
        ]);

        // Prevent disabling yourself
        if ($user->id === Auth::id() && ! $request->boolean('is_active')) {
            return back()->withErrors(['is_active' => 'You cannot deactivate your own account.']);
        }

        $old = array_merge(
            $user->only(['name', 'role_id', 'is_active']),
            ['roles' => $user->allRoles()->pluck('name')->all()],
        );

        $primaryRoleId     = $request->roles[0];
        $additionalRoleIds = array_slice($request->roles, 1);

        $changes = [
            'name'          => $request->name,
            'role_id'       => $primaryRoleId,
            'department_id' => $request->department_id,
            'phone'         => $request->phone,
            'designation'   => $request->designation,
            'is_active'     => $request->boolean('is_active'),
        ];

        if ($request->filled('password')) {
            $changes['password'] = Hash::make($request->password);
        }

        $user->update($changes);
        $user->roles()->sync($additionalRoleIds);

        $new = array_merge(
            $user->only(['name', 'role_id', 'is_active']),
            ['roles' => $user->fresh()->allRoles()->pluck('name')->all()],
        );

        AuditLog::record(Auth::user(), 'user.updated', $user, $user->email, $old, $new);

        return redirect()->route('admin.users.index')
            ->with('success', "User {$user->name} updated.");
    }

    /**
     * Reset 2FA — forces user to set up 2FA again on next login
     */
    public function reset2fa(User $user): RedirectResponse
    {
        $user->update([
            'two_factor_secret'        => null,
            'two_factor_recovery_codes'=> null,
            'two_factor_confirmed_at'  => null,
        ]);
        TwoFactorTrust::forget($user);

        AuditLog::record(Auth::user(), 'user.2fa_reset', $user, $user->email);

        return back()->with('success', "2FA has been reset for {$user->name}. They will set it up on next login.");
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return back()->withErrors(['error' => 'You cannot delete your own account.']);
        }

        if ($user->isSuperAdmin() && User::withRole('super_admin')->count() <= 1) {
            return back()->withErrors(['error' => 'The last Super Admin account cannot be deleted. Create another Super Admin first.']);
        }

        AuditLog::record(Auth::user(), 'user.deleted', $user, $user->email);
        $user->delete(); // Soft delete

        return redirect()->route('admin.users.index')
            ->with('success', "User {$user->name} has been removed.");
    }
}
