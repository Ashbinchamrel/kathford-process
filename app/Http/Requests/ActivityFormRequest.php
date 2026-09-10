<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ActivityFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'category_id'         => ['required', 'exists:form_categories,id'],
            'department_id'       => ['required', 'exists:departments,id'],
            'budget_id'           => ['required', 'exists:department_budgets,id'],
            'activity_name'       => ['required', 'string', 'max:200'],
            'deadline_date'       => ['required', 'date'],
            'remarks'             => ['nullable', 'string', 'max:2000'],
            'unplanned_reason'    => ['nullable', 'string', 'max:2000'],
            'extra_fields'        => ['nullable', 'array'],
            'action'              => ['nullable', 'in:submit,draft'],
            'line_items'          => ['nullable', 'array'],
            'line_items.*.item_name'    => ['nullable', 'string', 'max:255'],
            'line_items.*.quantity'     => ['nullable', 'numeric', 'min:0'],
            'line_items.*.unit'         => ['nullable', 'string', 'max:30'],
            'line_items.*.rate'         => ['nullable', 'numeric', 'min:0'],
            'line_items.*.item_remarks' => ['nullable', 'string', 'max:500'],
            'attachments'         => ['nullable', 'array'],
            'attachments.*'       => ['file', 'max:20480'], // 20MB max per file
            'external_link'       => ['nullable', 'url', 'max:500'],
        ];
    }
}
