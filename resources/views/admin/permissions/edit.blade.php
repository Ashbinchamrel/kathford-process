@extends('layouts.app')
@section('title', 'Permissions — '.$user->name)
@section('page-title', 'User Permissions')
@section('content')
<div class="space-y-4">
<div class="flex items-center justify-between"><div><a href="{{ route('admin.users.index') }}" class="text-sm text-teal-700">← Users</a><h2 class="text-lg font-semibold mt-2">Access for {{ $user->name }}</h2><p class="text-sm text-gray-500">{{ $user->roleLabel() }}</p></div></div>
<p class="rounded-lg bg-teal-50 p-3 text-sm text-teal-800">Verification and approval are assigned through Approval Chains. Assigned users can open and decide their documents without extra permission switches.</p>
@if(session('success'))<p class="text-teal-700">{{ session('success') }}</p>@endif
<form method="POST" action="{{ route('admin.permissions.update',$user) }}">
@csrf @method('PUT')
<div class="bg-white rounded-xl border overflow-x-auto"><table class="w-full text-sm" style="min-width:900px"><thead class="bg-gray-50"><tr><th class="p-3 text-left">Module / document</th><th class="p-3 text-left">View</th><th class="p-3 text-left">Prepare / edit</th><th class="p-3 text-left">Submit</th><th class="p-3 text-left">Progress</th><th class="p-3 text-left">Other actions / scope</th></tr></thead><tbody>
@foreach($groupedPermissions as $module=>$group)
@php
$sets=$module==='planning' ? $group['permissions']->groupBy(function($p){foreach(\App\Services\Planning\Access::TYPES as $type=>$label){if(str_starts_with($p->key,'planning.'.$type.'_'))return $label;}return 'Planning access and setup';}) : collect([$group['meta']['title']=>$group['permissions']]);
@endphp
@foreach($sets as $label=>$permissions)
@php
$columns=$permissions->groupBy(function($p){$key=$p->key;if(preg_match('/[._]view$/',$key))return 'view';if(preg_match('/[._](create|edit)$/',$key))return 'prepare';if(preg_match('/[._]submit$/',$key))return 'submit';if(preg_match('/[._]report$/',$key))return 'progress';return 'other';});
@endphp
<tr class="border-t"><td class="p-3 font-semibold align-top">{{ $label }}</td>
@foreach(['view','prepare','submit','progress','other'] as $column)<td class="p-3 align-top">
@forelse($columns->get($column,collect()) as $permission)
<label class="flex items-start gap-2 mb-2 cursor-pointer"><input type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked(old('permissions')!==null ? in_array($permission->id,old('permissions',[])) : in_array($permission->key,$userPermissionKeys)) aria-label="{{ $permission->label }}"><span>{{ $permission->label }}</span></label>
@empty<span class="text-gray-300">—</span>@endforelse
</td>@endforeach</tr>
@endforeach @endforeach
</tbody></table></div>
<div class="sticky bottom-0 bg-white border rounded-lg p-3 mt-4 flex justify-end gap-3"><a href="{{ route('admin.users.index') }}" class="px-4 py-2">Cancel</a><button class="bg-teal-600 text-white rounded-lg px-4 py-2">Save access</button></div>
</form>
</div>
@endsection
