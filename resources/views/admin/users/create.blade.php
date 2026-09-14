@extends('layouts.app')
@section('title', 'Add User')
@section('page-title', 'Add User')

@section('content')
<div class="max-w-2xl">
    <div class="bg-blue-50 border border-blue-200 rounded-xl px-5 py-4 mb-5 text-sm text-blue-800 flex items-start gap-3">
        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <div>
            <p class="font-semibold mb-1">Portal account credentials</p>
            <p>Set an initial password and share it securely. The user will set up two-factor authentication on their first sign-in.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.users.store') }}">
        @csrf
        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Address <span class="text-red-500">*</span></label>
                    <input type="email" name="email" value="{{ old('email') }}" required placeholder="username@kathford.edu.np"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                    @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Initial Password <span class="text-red-500">*</span></label>
                    <input type="password" name="password" required autocomplete="new-password" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                    <p class="text-xs text-gray-400 mt-1">At least 12 characters.</p>
                    @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password <span class="text-red-500">*</span></label>
                    <input type="password" name="password_confirmation" required autocomplete="new-password" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Roles <span class="text-red-500">*</span></label>
                    <p class="text-xs text-gray-400 mb-2">A user can hold more than one role — e.g. Verifier on one process and Approver on another.</p>
                    <div class="flex flex-wrap gap-x-6 gap-y-2 border border-gray-300 rounded-lg p-3" data-role-checkbox-group>
                        @foreach($roles as $role)
                            <label class="flex items-center gap-2 cursor-pointer text-sm">
                                <input type="checkbox" name="roles[]" value="{{ $role->id }}" data-role-name="{{ $role->name }}" {{ in_array($role->id, old('roles', [])) ? 'checked' : '' }} class="rounded text-teal-500">
                                {{ $role->display_name }}
                            </label>
                        @endforeach
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Super Admin already has full access, so it can't be combined with other roles.</p>
                    @error('roles') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    @error('roles.*') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                @include('admin.users.planning-membership')
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                    <select name="department_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                        <option value="">No department</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Designation</label>
                    <input type="text" name="designation" value="{{ old('designation') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
            </div>
        </div>
        <div class="flex gap-3">
            <button type="submit" class="px-6 py-2.5 bg-teal-500 hover:bg-teal-600 text-white rounded-lg text-sm font-semibold transition-colors">Create User</button>
            <a href="{{ route('admin.users.index') }}" class="text-gray-500 hover:text-gray-700 text-sm py-2.5">Cancel</a>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.querySelectorAll('[data-role-checkbox-group]').forEach(function (group) {
    group.addEventListener('change', function (e) {
        if (!e.target.matches('input[type="checkbox"][data-role-name]')) return;
        const boxes = Array.from(group.querySelectorAll('input[type="checkbox"][data-role-name]'));
        const isSuperAdmin = e.target.dataset.roleName === 'super_admin';
        if (isSuperAdmin && e.target.checked) {
            boxes.forEach(box => { if (box !== e.target) box.checked = false; });
        } else if (!isSuperAdmin && e.target.checked) {
            const superAdmin = boxes.find(box => box.dataset.roleName === 'super_admin');
            if (superAdmin) superAdmin.checked = false;
        }
    });
});
</script>
@endpush
@endsection
