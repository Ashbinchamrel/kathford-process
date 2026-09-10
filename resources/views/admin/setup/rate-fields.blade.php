<div class="grid gap-3 sm:grid-cols-3">
<label class="text-sm">Vendor<select name="vendor_id" required class="block w-full border rounded-lg p-2">@foreach($vendors as $vendor)<option value="{{ $vendor->id }}" @selected($rate?->vendor_id===$vendor->id)>{{ $vendor->name }}</option>@endforeach</select></label>
<label class="text-sm">Item<input name="item_name" required value="{{ $rate?->item_name }}" class="block w-full border rounded-lg p-2"></label>
<label class="text-sm">Unit<input name="unit" required value="{{ $rate?->unit ?? 'pcs' }}" class="block w-full border rounded-lg p-2"></label>
<label class="text-sm">Unit rate<input type="number" step="0.01" min="0.01" name="unit_rate" required value="{{ $rate?->unit_rate }}" class="block w-full border rounded-lg p-2"></label>
@foreach(['valid_from'=>'Valid from','valid_until'=>'Valid until'] as $key=>$label)<label class="text-sm">{{ $label }}<input type="date" name="{{ $key }}" value="{{ $rate?->{$key}?->format('Y-m-d') }}" required class="block w-full border rounded-lg p-2"></label>@endforeach
<label class="text-sm sm:col-span-2">Specification<textarea name="specification" class="block w-full border rounded-lg p-2">{{ $rate?->specification }}</textarea></label><label class="text-sm"><input type="checkbox" name="is_active" value="1" @checked($rate?->is_active ?? true)> Active</label></div>
