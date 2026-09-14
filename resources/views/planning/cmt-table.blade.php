<div class="kcard overflow-x-auto"><table class="w-full text-sm"><thead><tr><th>CMT semester goal / strategic link</th><th>Key results</th><th>Owner / due</th></tr></thead><tbody>
@foreach($document->items->groupBy(fn($i)=>$i->details['goal_key']??$i->id) as $rows)
@php($first=$rows->first())
<tr><td class="align-top" style="width:35%"><strong>{{ $first->details['objective']??$first->title }}</strong>
@php($source=$document->parent?->items->firstWhere('id',$first->source_id))
<p class="text-xs text-gray-500 mt-2">{{ $source?->title }}</p>
@php($semester=collect($document->parent?->strategy_data['semesters']??[])->search(fn($s)=>$document->period && $s['starts_on']<=$document->period->starts_on && $s['ends_on']>=$document->period->ends_on))
@if($semester!==false)<p class="text-xs whitespace-pre-line mt-2">Strategic target: {{ $source?->details['strategy']['targets'][$semester]??'Not specified' }}</p>@endif
<details class="mt-3"><summary class="text-teal-700">Deliverables and details</summary>
@foreach(['deliverables'=>'Deliverables','milestones'=>'Monthly milestones','proposed_tasks'=>'Proposed tasks','target_note'=>'Target explanation'] as $key=>$label)
@if(filled($first->details[$key]??''))<p class="font-semibold mt-2">{{ $label }}</p><p class="whitespace-pre-line">{{ $first->details[$key] }}</p>@endif
@endforeach
<p class="font-semibold mt-2">Definition of Done</p><ul class="list-disc pl-5">@foreach(preg_split('/\r?\n/', $first->definition_of_done??'') as $condition)@if(trim($condition)!=='')<li>{{ $condition }}</li>@endif @endforeach</ul>
@if(!empty($first->details['supporting_owner_ids']))<p class="font-semibold mt-2">Supporting owners</p><p>{{ \App\Models\User::whereIn('id',$first->details['supporting_owner_ids'])->pluck('name')->join(', ') }}</p>@endif
</details></td><td class="align-top">
@foreach($rows as $item)<div class="mb-4"><p class="font-semibold">{{ $item->title }}</p><p>Target: {{ $item->target??'Not set' }} {{ $item->metric }} · Baseline: {{ $item->baseline??'Not set' }}</p>
@if($document->status==='approved')<p>Actual: {{ $item->actual??'No check-in' }}</p>
@if(\App\Services\Planning\Governance::canWrite(auth()->user(),'cmt_goals','report') && ($item->owner_id===auth()->id() || $document->created_by===auth()->id() || auth()->user()->isSuperAdmin()))
<details><summary class="text-teal-700">Record progress</summary><form method="POST" action="{{ route('planning.checkin',[$document,$item]) }}">@csrf<label>Actual value<input type="number" min="0" step="0.01" name="actual" required></label><label>Evidence<textarea name="evidence" required></textarea></label><button class="btn-primary">Save check-in</button></form></details>
@endif @endif
</div>@endforeach
</td><td class="align-top">{{ $first->owner?->name??'Not assigned' }}<p>{{ $first->due_on }} AD</p><p class="text-xs text-gray-500">{{ \App\Services\Planning\Dates::bs($first->due_on) }}</p></td></tr>
@endforeach
</tbody></table></div>
