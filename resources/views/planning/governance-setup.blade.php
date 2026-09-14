<form method="POST" action="{{ route('planning.governance.save') }}" class="kcard p-5 space-y-4">@csrf
<h2 class="font-semibold">Form settings · Default approval chains</h2>
<div class="grid md:grid-cols-2 gap-4">@foreach(\App\Services\Planning\Access::TYPES as $type=>$label)<div><label>{{ $label }}</label><select name="chains[{{ $type }}]"><option value="">{{ $type==='cmt_goals'?'Choose CMT approval chain':'Choose on each draft' }}</option>@foreach($chains as $chain)<option value="{{ $chain->id }}" @selected(old('chains.'.$type,$governance['chains'][$type]??'')===$chain->id)>{{ $chain->name }}</option>@endforeach</select></div>@endforeach</div>
<button class="btn-primary">Save approval defaults</button>
</form>
