@extends('layouts.app')
@section('title', 'User Management')
@section('page-title', 'User Management')

@section('content')
<div class="space-y-4">
    <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
        <form method="GET" class="flex gap-2 flex-1">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search users…"
                   class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
            <select name="role_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                <option value="">All Roles</option>
                @foreach($roles as $role)
                    <option value="{{ $role->id }}" {{ request('role_id') == $role->id ? 'selected' : '' }}>{{ $role->display_name }}</option>
                @endforeach
            </select>
            <button type="submit" class="bg-teal-500 hover:bg-teal-600 text-white px-4 py-2 rounded-lg text-sm font-medium">Filter</button>
        </form>
        <a href="{{ route('admin.users.create') }}" class="bg-navy-500 hover:bg-navy-600 text-white px-5 py-2 rounded-lg text-sm font-medium flex items-center gap-2 whitespace-nowrap" style="background-color:#0B1E3D;">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
            Add User
        </a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Name</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Password</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Email</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Role</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Department</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">2FA</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="relative px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($users as $user)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-3">
                                @if($user->avatar)
                                    <img src="{{ $user->avatar }}" class="w-7 h-7 rounded-full" alt="">
                                @else
                                    <div class="w-7 h-7 rounded-full bg-teal-100 text-teal-700 flex items-center justify-center text-xs font-bold">{{ substr($user->name,0,1) }}</div>
                                @endif
                                <span class="font-medium text-gray-900">{{ $user->name }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-3">
                            @if($user->password)
                                <span class="text-green-600 text-xs font-medium">Set</span>
                            @else
                                <span class="text-amber-600 text-xs font-medium">Needs setup</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-gray-500">{{ $user->email }}</td>
                        <td class="px-5 py-3">
                            <div class="flex flex-wrap gap-1">
                                @foreach($user->allRoles() as $role)
                                    <span class="px-2 py-0.5 bg-navy-50 text-navy-700 rounded text-xs font-medium" style="background-color:#EBF0F7;color:#0B1E3D;">{{ $role->display_name }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-5 py-3 text-gray-500">{{ $user->department?->name ?? '—' }}</td>
                        <td class="px-5 py-3">
                            @if($user->hasTwoFactorEnabled())
                                <span class="text-green-600 text-xs font-medium flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    Active
                                </span>
                            @else
                                <span class="text-red-500 text-xs">Not set</span>
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $user->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $user->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right space-x-3">
                            <a href="{{ route('admin.users.edit', $user) }}" class="text-teal-600 hover:text-teal-700 text-sm font-medium">Edit</a>
                            @if(!$user->isSuperAdmin())
                            <a href="{{ route('admin.permissions.edit', $user) }}" class="text-indigo-600 hover:text-indigo-700 text-sm font-medium">Permissions</a>
                            @endif
                            @if($user->hasTwoFactorEnabled() && $user->id !== auth()->id())
                            <form method="POST" action="{{ route('admin.users.reset-2fa', $user) }}" class="inline" onsubmit="return confirm('Reset 2FA for this user?')">
                                @csrf
                                <button type="submit" class="text-amber-600 hover:text-amber-700 text-sm font-medium">Reset 2FA</button>
                            </form>
                            @endif
                            @if($user->id !== auth()->id())
                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline" onsubmit="return confirm('Remove {{ addslashes($user->name) }}? Their account will be deactivated and retained in the audit trail.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-rose-600 hover:text-rose-700 text-sm font-medium">Delete</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="px-5 py-12 text-center text-gray-400">No users found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($users->hasPages())
            <div class="px-5 py-3 border-t border-gray-100">{{ $users->withQueryString()->links() }}</div>
        @endif
    </div>
</div>
@endsection
