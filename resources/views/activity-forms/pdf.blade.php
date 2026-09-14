<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>{{ $form->form_number }}</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #1a1a1a; background: #fff; }
    .header { width:100%; border-bottom: 2px solid #00A99D; padding: 0 0 14px; margin-bottom: 4px; display: table; }
    .header-cell { display:table-cell; vertical-align:middle; }
    .header h1 { font-size: 22px; font-weight: 700; margin:0; color: #0B1E3D; }
    .logo-frame { width:158px; height:78px; background:#fff; border:1px solid #e5e7eb; border-radius:4px; margin:0; }
    .logo-frame td { text-align:center; vertical-align:middle; }
    .header .form-num { font-size: 16px; font-family: monospace; color: #00A99D; }
    .content { padding: 25px 30px; }
    .meta-table { width: 100%; background: #f9fafb; border-radius: 6px; margin-bottom: 20px; }
    .meta-table td { width: 33.33%; padding: 10px 12px; vertical-align: top; border: none; }
    .meta-table .label { font-size: 9px; text-transform: uppercase; color: #6b7280; font-weight: 600; margin-bottom: 3px; }
    .meta-table .value { font-size: 11px; font-weight: 600; }
    .status-badge { display:inline-block; padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: 700; background:#e8f7f2; color:#075c4c; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    thead { background: #0B1E3D; color: #fff; }
    thead th { padding: 8px 10px; text-align: left; font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }
    tbody tr:nth-child(even) { background: #f9fafb; }
    tbody td { padding: 8px 10px; border-bottom: 1px solid #f3f4f6; }
    tfoot td { padding: 10px; font-weight: 700; border-top: 2px solid #0B1E3D; }
    .total-row td { background: #f0fdf4; }
    .text-right { text-align: right; }
    .mono { font-family: monospace; }
    .section-label { font-size: 9px; text-transform: uppercase; color: #6b7280; font-weight: 600; margin-bottom: 6px; }
    .remarks-box { background:#f9fafb; border-radius:6px; padding:12px; margin-bottom:20px; }
    .remarks-box p { font-size: 10px; color: #374151; line-height: 1.6; }
    .approval-grid th { background:#f4f6f8; color:#182233; font-size:9px; }
    .approval-grid td { font-size: 10px; }
    .sig-box { margin-top: 40px; display: flex; gap: 30px; }
    .sig { flex: 1; border-top: 1px solid #374151; padding-top: 8px; font-size: 10px; color: #374151; }
    .footer { margin-top: 30px; padding-top: 15px; border-top: 1px solid #e5e7eb; display: flex; justify-content: space-between; font-size: 9px; color: #9ca3af; }
</style>
</head>
<body>
@php($companyName = $company['company_name'] ?? config('kathford.college_name'))
<div class="header">
    <div class="header-cell" style="width:170px">@if($companyLogoPath)<table class="logo-frame"><tr><td><img src="{{ $companyLogoPath }}" width="{{ $logoWidth }}" height="{{ $logoHeight }}" alt="Organisation logo"></td></tr></table>@endif</div>
    <div class="header-cell"><h1>ACTIVITY FORM</h1></div>
    <div class="header-cell" style="text-align:right;width:190px">
        <div class="form-num">{{ $form->form_number }}</div>
        <div style="font-size:10px;color:#6b7280;margin-top:4px">Issued: {{ $form->created_at?->format('d M Y') ?? date('d M Y') }}</div>
    </div>
</div>

<div class="content">
    <table class="meta-table">
        <tr>
            <td><div class="label">Activity Name</div><div class="value">{{ $form->activity_name }}</div></td>
            <td><div class="label">Status</div><div class="value"><span class="status-badge">{{ $form->statusLabel() }}</span></div></td>
            <td><div class="label">Category</div><div class="value">{{ $form->category?->name ?? '—' }}</div></td>
        </tr>
        <tr>
            <td><div class="label">Department</div><div class="value">{{ $form->department?->name ?? '—' }}</div></td>
            <td><div class="label">Created By</div><div class="value">{{ $form->creator?->name ?? '—' }}</div></td>
            <td><div class="label">Deadline</div><div class="value">{{ $form->deadline_date?->format('d M Y') ?? '—' }}</div></td>
        </tr>
        <tr>
            <td><div class="label">Budget</div><div class="value">{{ $form->budget?->activity_title ?? '—' }}</div></td>
            <td><div class="label">Fiscal Year</div><div class="value">{{ $form->budget?->fiscal_year ?? '—' }}</div></td>
            <td><div class="label">Estimated Amount</div><div class="value mono">Rs {{ number_format($form->total_estimated_amount, 2) }}</div></td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th style="width:5%">#</th>
                <th style="width:45%">Description</th>
                <th style="width:10%">Unit</th>
                <th style="width:10%;text-align:right">Qty</th>
                <th style="width:15%;text-align:right">Rate (Rs)</th>
                <th style="width:15%;text-align:right">Amount (Rs)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($form->lineItems as $idx => $item)
            <tr>
                <td>{{ $idx + 1 }}</td>
                <td>{{ $item->item_name }}@if($item->item_remarks)<br><span style="font-size:9px;color:#6b7280">{{ $item->item_remarks }}</span>@endif</td>
                <td>{{ $item->unit }}</td>
                <td class="text-right mono">{{ $item->quantity }}</td>
                <td class="text-right mono">{{ number_format($item->rate, 2) }}</td>
                <td class="text-right mono">{{ number_format($item->amount, 2) }}</td>
            </tr>
            @empty
            <tr><td colspan="6">No line items recorded.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="total-row"><td colspan="5" class="text-right">TOTAL ESTIMATED AMOUNT (NPR)</td><td class="text-right mono" style="font-size:14px;color:#0B1E3D">{{ number_format($form->total_estimated_amount, 2) }}</td></tr>
        </tfoot>
    </table>

    @if($form->remarks || $form->unplanned_reason)
    <div class="remarks-box">
        <div class="section-label">Remarks</div>
        <p>{{ $form->remarks ?: $form->unplanned_reason }}</p>
    </div>
    @endif

    @if($form->approvalActions->isNotEmpty())
    <div class="section-label">Approval Workflow</div>
    <table class="approval-grid">
        <thead><tr><th>Action</th><th>By</th><th>Date / Time</th><th>Comment</th></tr></thead>
        <tbody>
            @foreach($form->approvalActions as $action)
            <tr>
                <td>{{ ucfirst(str_replace('_', ' ', $action->decision)) }}</td>
                <td>{{ $action->actor?->name ?? '—' }}</td>
                <td>{{ $action->acted_at?->format('d M Y H:i') }}</td>
                <td>{{ $action->note ?: '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="sig-box">
        <div class="sig">Authorized Signatory<br><span style="color:#9ca3af">System Approved Activity Form</span></div>
        <div class="sig">Requested By<br><span style="color:#9ca3af">{{ $form->creator?->name ?? '—' }}</span></div>
    </div>

    <div class="footer">
        <span>{{ $companyName }} · {{ $company['company_address'] ?? config('kathford.college_address') }}</span>
        <span>Generated {{ now()->format('d M Y H:i') }}</span>
    </div>
</div>
</body>
</html>
