@extends('layouts.app')
@section('title', 'Edit ' . $user->name)
@section('page-title', 'Edit User')

@section('content')
<div class="max-w-2xl">
    <form method="POST" action="{{ route('admin.users.update', $user) }}">
        @csrf @method('PUT')
        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-4">
            <div class="flex items-center gap-4 mb-5 pb-4 border-b border-gray-100">
                @if($user->avatar)
                    <img src="{{ $user->avatar }}" class="w-12 h-12 rounded-full" alt="">
                @else
                    <div class="w-12 h-12 rounded-full bg-teal-100 text-teal-700 flex items-center justify-center text-lg font-bold">{{ substr($user->name,0,1) }}</div>
                @endif
                <div>
                    <p class="font-semibold text-gray-900">{{ $user->email }}</p>
                    <p class="text-sm text-gray-400">Portal account · Email address cannot be changed</p>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Roles <span class="text-red-500">*</span></label>
                    <p class="text-xs text-gray-400 mb-2">A user can hold more than one role — e.g. Verifier on one process and Approver on another. Assign both here, then pick them per process under Approval Chains.</p>
                    <div class="flex flex-wrap gap-x-6 gap-y-2 border border-gray-300 rounded-lg p-3" data-role-checkbox-group>
                        @php $selectedRoles = old('roles', $user->allRoles()->pluck('id')->all()); @endphp
                        @foreach($roles as $role)
                            <label class="flex items-center gap-2 cursor-pointer text-sm">
                                <input type="checkbox" name="roles[]" value="{{ $role->id }}" data-role-name="{{ $role->name }}" {{ in_array($role->id, $selectedRoles) ? 'checked' : '' }} class="rounded text-teal-500">
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
                            <option value="{{ $dept->id }}" {{ old('department_id', $user->department_id) == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Designation</label>
                    <input type="text" name="designation" value="{{ old('designation', $user->designation) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Set New Password</label>
                    <input type="password" name="password" autocomplete="new-password" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                    <p class="text-xs text-gray-400 mt-1">Leave blank to retain the current password.</p>
                    @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                    <input type="password" name="password_confirmation" autocomplete="new-password" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        @if($user->id === auth()->id())<input type="hidden" name="is_active" value="1">@else<input type="hidden" name="is_active" value="0">@endif
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }} class="rounded text-teal-500"
                               {{ $user->id === auth()->id() ? 'disabled' : '' }}>
                        <span class="text-sm font-medium text-gray-700">Active Account</span>
                    </label>
                    @if($user->id === auth()->id())
                        <p class="text-xs text-gray-400 mt-1">Cannot deactivate your own account.</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="flex gap-3">
            <button type="submit" class="px-6 py-2.5 bg-teal-500 hover:bg-teal-600 text-white rounded-lg text-sm font-semibold transition-colors">Save Changes</button>
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
