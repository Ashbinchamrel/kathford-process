<!DOCTYPE html>
<html lang="en">
<body style="margin:0;background:#f3f4f6;font-family:Arial,sans-serif;color:#1f2937">
    <div style="max-width:600px;margin:30px auto;background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden">
        <div style="padding:24px 32px;background:#0B1E3D;color:#fff">
            <div style="font-size:18px;font-weight:700">Kathford International College</div>
            <div style="margin-top:7px;color:#5eead4;font-family:monospace;font-weight:700">{{ $rfq->rfq_number }}</div>
        </div>
        <div style="padding:28px 32px">
            <p>Dear <strong>{{ $vendor->name }}</strong>,</p>
            <p>Kathford would like to negotiate your quotation for the following item.</p>
            <div style="margin:20px 0;padding:16px;border-left:4px solid #00A99D;background:#f0fdfa">
                <div style="font-size:12px;color:#64748b;text-transform:uppercase;font-weight:700">Item</div>
                <div style="margin-top:4px;font-weight:700">{{ $quoteItem->description }} ({{ $quoteItem->quantity }} {{ $quoteItem->unit }})</div>
                <div style="margin-top:12px;font-size:12px;color:#64748b;text-transform:uppercase;font-weight:700">Negotiation message</div>
                <div style="margin-top:4px;white-space:pre-line">{{ $quoteItem->negotiation_message }}</div>
            </div>
            <p>Please sign in to the Vendor Portal to review the request and revise your unit rate if appropriate.</p>
            <p style="text-align:center;margin:24px 0"><a href="{{ $portalUrl }}" style="display:inline-block;padding:13px 24px;background:#00A99D;border-radius:8px;color:#fff;text-decoration:none;font-weight:700">Open Vendor Portal</a></p>
        </div>
    </div>
</body>
</html>
