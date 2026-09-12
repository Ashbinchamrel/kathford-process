<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 28px 32px; }
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 10px; }
        .header { border-bottom: 2px solid #00A99D; padding-bottom: 12px; margin-bottom: 18px; display:table; width:100%; }
        .header-logo { display:table-cell; width:150px; vertical-align:middle; }
        .header-copy { display:table-cell; vertical-align:middle; }
        .logo-frame { width:140px; height:68px; background:#fff; border:1px solid #e5e7eb; border-radius:4px; margin:0; }
        .logo-frame td { text-align:center; vertical-align:middle; }
        h1 { color: #0B1E3D; font-size: 19px; margin: 0 0 4px; }
        .subtitle { color: #6b7280; font-size: 10px; margin: 0; }
        .ref { float: right; text-align: right; color: #0B1E3D; font-weight: bold; font-size: 12px; }
        .meta { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        .meta td { width: 50%; padding: 6px 8px; vertical-align: top; border: 1px solid #e5e7eb; }
        .label { display: block; color: #6b7280; font-size: 8px; font-weight: bold; text-transform: uppercase; margin-bottom: 3px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th { background: #0B1E3D; color: #ffffff; font-size: 8px; letter-spacing: .03em; text-transform: uppercase; text-align: left; padding: 7px 8px; }
        td { border: 1px solid #e5e7eb; padding: 7px 8px; vertical-align: top; }
        .right { text-align: right; }
        .notes { margin-top: 18px; border: 1px solid #e5e7eb; background: #f9fafb; padding: 10px 12px; }
        .footer { position: fixed; bottom: -12px; left: 0; right: 0; color: #9ca3af; font-size: 8px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-logo">@if($companyLogoPath)<table class="logo-frame"><tr><td><img src="{{ $companyLogoPath }}" width="{{ $logoWidth }}" height="{{ $logoHeight }}" alt="Organisation logo"></td></tr></table>@endif</div>
        <div class="header-copy"><div class="ref">{{ $rfq->rfq_number }}<br><span style="color:#6b7280;font-size:9px;font-weight:normal">Issued {{ $rfq->created_at->format('d M Y') }}</span></div><h1>Request for Quotation</h1><p class="subtitle">Procurement record</p></div>
    </div>

    <table class="meta">
        <tr>
            <td><span class="label">Title</span>{{ $rfq->title }}</td>
            <td><span class="label">Submission deadline</span>{{ $rfq->deadline?->format('d M Y') ?? 'Not specified' }}</td>
        </tr>
        <tr>
            <td><span class="label">Linked activity form</span>{{ $rfq->activityForm?->form_number ?? 'Standalone RFQ' }}</td>
            <td><span class="label">Invited vendors</span>{{ $rfq->quotes->pluck('vendor.name')->filter()->join(', ') }}</td>
        </tr>
    </table>

    @php($items = $rfq->quotes->first(fn ($quote) => $quote->items->isNotEmpty())?->items ?? collect())
    <table>
        <thead>
            <tr><th style="width:6%">#</th><th>Description &amp; request details</th><th style="width:15%">Unit</th><th class="right" style="width:15%">Quantity</th></tr>
        </thead>
        <tbody>
            @forelse($items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->description }}@if($item->request_remarks)<br><span style="color:#6b7280;font-size:8px">Request details: {{ $item->request_remarks }}</span>@endif</td>
                    <td>{{ $item->unit ?? '-' }}</td>
                    <td class="right">{{ $item->quantity }}</td>
                </tr>
            @empty
                <tr><td colspan="4">No quotation items were recorded.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if($rfq->notes)
        <div class="notes"><span class="label">Instructions to vendors</span>{{ $rfq->notes }}</div>
    @endif

    <div class="footer">{{ $company['company_name'] ?? config('kathford.college_name') }} | RFQ record {{ $rfq->rfq_number }}</div>
</body>
</html>
