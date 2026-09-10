<div class="border rounded-lg p-4 mb-3 grid grid-cols-1 md:grid-cols-3 gap-3">
<div class="md:col-span-2"><label>{{ $type==='plan'?'Activity':'Title' }} *</label><input name="items[{{ $i }}][title]" required value="{{ $row['title']??'' }}"></div>
<div><label>Owner</label><select name="items[{{ $i }}][owner_id]"><option value="">Document creator</option>@foreach($users as $u)<option value="{{ $u->id }}" @selected(($row['owner_id']??'')===$u->id)>{{ $u->name }}</option>@endforeach</select></div>
<div><label>Due date (AD)</label><input type="date" name="items[{{ $i }}][due_on]" value="{{ $row['due_on']??'' }}"></div>
<div class="md:col-span-2"><label>Definition of Done</label><textarea name="items[{{ $i }}][definition_of_done]" rows="2">{{ $row['definition_of_done']??'' }}</textarea></div>
@if(in_array($type,['strategy','goals']))
<div><label>Metric / unit</label><input name="items[{{ $i }}][metric]" placeholder="e.g. Canvas adoption (%)" value="{{ $row['metric']??'' }}"></div><div><label>Baseline</label><input type="number" step="0.01" min="0" name="items[{{ $i }}][baseline]" value="{{ $row['baseline']??'' }}"></div><div><label>Target</label><input type="number" step="0.01" min="0" name="items[{{ $i }}][target]" value="{{ $row['target']??'' }}"></div>
@endif
@if($type==='plan')
<div><label>Programme</label><input name="items[{{ $i }}][programme]" value="{{ $row['programme']??'' }}"></div><div><label>Batch</label><input name="items[{{ $i }}][batch]" value="{{ $row['batch']??'' }}"></div>
<div><label>Support department</label><select name="items[{{ $i }}][support_department_id]"><option value="">No support required</option>@foreach($supportDepartments as $d)<option value="{{ $d->id }}" @selected(($row['support_department_id']??'')===$d->id)>{{ $d->name }}</option>@endforeach</select></div>
<div><label><input type="checkbox" name="items[{{ $i }}][requires_budget]" value="1" @checked($row['requires_budget']??false)> Requires budget</label></div>
@endif
@if($type==='budget')
<div class="md:col-span-2"><label>Approved activity *</label><select required name="items[{{ $i }}][source_id]"><option value="">Select approved activity</option>@foreach($parents->where('type','plan') as $plan)@foreach($plan->items->where('requires_budget',true) as $activity)<option value="{{ $activity->id }}" @selected(($row['source_id']??'')===$activity->id)>{{ $plan->title }} · {{ $activity->title }}</option>@endforeach @endforeach</select></div><div><label>Allocation (NPR) *</label><input required type="number" min="0.01" step="0.01" name="items[{{ $i }}][amount]" value="{{ $row['amount']??'' }}"></div>
@endif
<div class="md:col-span-3"><label>Description / logistics / assumptions</label><textarea name="items[{{ $i }}][description]" rows="2">{{ $row['description']??'' }}</textarea></div><button type="button" class="text-red-600 text-sm text-left" onclick="this.parentElement.remove()">Remove item</button>
</div>
