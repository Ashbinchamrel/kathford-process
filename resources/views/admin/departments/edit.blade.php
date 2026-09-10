@extends('layouts.app')
@section('title', 'Edit Department')
@section('page-title', 'Edit Department: ' . $department->name)

@section('content')
<div class="max-w-xl">
    <form method="POST" action="{{ route('admin.departments.update', $department) }}">
        @csrf @method('PUT')
        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-4 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $department->name) }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Code</label>
                <input type="text" value="{{ $department->code }}" disabled class="w-full border border-gray-200 bg-gray-50 rounded-lg px-3 py-2 text-sm font-mono text-gray-500">
                <p class="text-xs text-gray-400 mt-1">Code cannot be changed after creation.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Department Head</label>
                <select name="head_user_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                    <option value="">None</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ old('head_user_id', $department->head_user_id) == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $department->is_active) ? 'checked' : '' }} class="rounded text-teal-500">
                    <span class="text-sm font-medium text-gray-700">Active</span>
                </label>
            </div>
        </div>
        <div class="flex gap-3">
            <button type="submit" class="px-6 py-2.5 bg-teal-500 hover:bg-teal-600 text-white rounded-lg text-sm font-semibold transition-colors">Save Changes</button>
            <a href="{{ route('admin.departments.index') }}" class="text-gray-500 hover:text-gray-700 text-sm py-2.5">Cancel</a>
        </div>
    </form>
</div>
@endsection
