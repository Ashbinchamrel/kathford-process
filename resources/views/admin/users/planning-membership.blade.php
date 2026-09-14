<div class="sm:col-span-2">
<p class="block text-sm font-medium text-gray-700 mb-2">Planning membership</p>
<div class="flex flex-wrap gap-6">
@foreach(['is_board_member'=>'Board member','is_cmt_member'=>'CMT member'] as $field=>$label)
<input type="hidden" name="{{ $field }}" value="0">
<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="{{ $field }}" value="1" @checked(old($field,isset($user)?$user->$field:false)) class="rounded text-teal-500">{{ $label }}</label>
@endforeach
</div>
<p class="text-xs text-gray-400 mt-2">Determines the dashboard shown in Planning Overview. Department heads are assigned in Departments.</p>
</div>
