<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?php echo e($po->po_number); ?></title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #1a1a1a; background: #fff; }
    .header { width:100%; background: #0B1E3D; color: #fff; padding: 18px 30px; display: table; }
    .header-cell { display:table-cell; vertical-align:middle; }
    .header h1 { font-size: 22px; font-weight: 700; margin:0; }
    .brand-logo { display:block; width:150px; height:78px; padding:6px; background:#fff; border-radius:4px; object-fit:contain; object-position:center; }
    .header .po-num { font-size: 16px; font-family: monospace; color: #00A99D; }
    .content { padding: 25px 30px; }
    .parties { display: flex; gap: 30px; margin-bottom: 20px; }
    .party { flex: 1; border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px; }
    .party .label { font-size: 9px; text-transform: uppercase; color: #6b7280; font-weight: 600; letter-spacing: .05em; margin-bottom: 6px; }
    .party .name { font-size: 13px; font-weight: 700; margin-bottom: 4px; }
    .party .detail { font-size: 10px; color: #6b7280; line-height: 1.6; }
    .meta { display: flex; gap: 20px; background: #f9fafb; border-radius: 6px; padding: 12px; margin-bottom: 20px; }
    .meta-item .label { font-size: 9px; text-transform: uppercase; color: #6b7280; font-weight: 600; margin-bottom: 3px; }
    .meta-item .value { font-size: 11px; font-weight: 600; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    thead { background: #0B1E3D; color: #fff; }
    thead th { padding: 8px 10px; text-align: left; font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }
    tbody tr:nth-child(even) { background: #f9fafb; }
    tbody td { padding: 8px 10px; border-bottom: 1px solid #f3f4f6; }
    tfoot td { padding: 10px; font-weight: 700; border-top: 2px solid #0B1E3D; }
    .total-row td { background: #f0fdf4; }
    .text-right { text-align: right; }
    .mono { font-family: monospace; }
    .footer { margin-top: 30px; padding-top: 15px; border-top: 1px solid #e5e7eb; display: flex; justify-content: space-between; font-size: 9px; color: #9ca3af; }
    .sig-box { margin-top: 40px; display: flex; gap: 30px; }
    .sig { flex: 1; border-top: 1px solid #374151; padding-top: 8px; font-size: 10px; color: #374151; }
</style>
</head>
<body>
<?php ($companyName = $company['company_name'] ?? config('kathford.college_name')); ?>
<div class="header">
    <div class="header-cell" style="width:165px"><?php if($companyLogoPath): ?><img class="brand-logo" src="<?php echo e($companyLogoPath); ?>" alt="Organisation logo"><?php endif; ?></div>
    <div class="header-cell"><h1>PURCHASE ORDER</h1></div>
    <div class="header-cell" style="text-align:right;width:190px">
        <div class="po-num"><?php echo e($po->po_number); ?></div>
        <div style="font-size:10px;color:#94a3b8;margin-top:4px">Issued: <?php echo e($po->created_at?->format('d M Y') ?? date('d M Y')); ?></div>
    </div>
</div>

<div class="content">
    <div class="parties">
        <div class="party">
            <div class="label">From (Buyer)</div>
            <div class="name"><?php echo e($companyName); ?></div>
            <div class="detail"><?php echo e(config('kathford.college_address')); ?><br><?php echo e(config('kathford.college_phone')); ?><br><?php echo e(config('kathford.college_website')); ?></div>
        </div>
        <div class="party">
            <div class="label">To (Vendor)</div>
            <div class="name"><?php echo e($po->vendor?->name); ?></div>
            <div class="detail"><?php echo e($po->vendor?->address); ?><br><?php echo e($po->vendor?->email); ?><br><?php echo e($po->vendor?->phone); ?></div>
        </div>
    </div>

    <div class="meta">
        <div class="meta-item"><div class="label">PO Date</div><div class="value"><?php echo e($po->created_at?->format('d M Y') ?? date('d M Y')); ?></div></div>
        <div class="meta-item"><div class="label">Delivery By</div><div class="value"><?php echo e($po->expected_delivery_date?->format('d M Y') ?? '—'); ?></div></div>
        <div class="meta-item"><div class="label">Delivery Address</div><div class="value"><?php echo e($po->delivery_address ?? '—'); ?></div></div>
        <div class="meta-item"><div class="label">RFQ Reference</div><div class="value mono"><?php echo e($po->rfqQuote?->rfq?->rfq_number); ?></div></div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:5%">#</th>
                <th style="width:45%">Description</th>
                <th style="width:10%">Unit</th>
                <th style="width:10%;text-align:right">Qty</th>
                <th style="width:15%;text-align:right">Unit Rate (Rs)</th>
                <th style="width:15%;text-align:right">Total (Rs)</th>
            </tr>
        </thead>
        <tbody>
            <?php $__currentLoopData = $po->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <td><?php echo e($idx + 1); ?></td>
                <td><?php echo e($item->description); ?><?php if($item->request_remarks): ?><br><span style="font-size:9px;color:#6b7280">Request details: <?php echo e($item->request_remarks); ?></span><?php endif; ?></td>
                <td><?php echo e($item->unit); ?></td>
                <td class="text-right mono"><?php echo e($item->quantity); ?></td>
                <td class="text-right mono"><?php echo e(number_format($item->unit_rate, 2)); ?></td>
                <td class="text-right mono"><?php echo e(number_format($item->total, 2)); ?></td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
        <tfoot>
            <tr><td colspan="5" class="text-right">TOTAL AMOUNT (NPR)</td><td class="text-right mono"><?php echo e(number_format($po->subtotal, 2)); ?></td></tr>
            <tr><td colspan="5" class="text-right">TAX AMOUNT (NPR)</td><td class="text-right mono"><?php echo e(number_format($po->tax_amount, 2)); ?></td></tr>
            <tr class="total-row"><td colspan="5" class="text-right">GRAND TOTAL (NPR)</td><td class="text-right mono" style="font-size:14px;color:#0B1E3D"><?php echo e(number_format($po->total_amount, 2)); ?></td></tr>
        </tfoot>
    </table>

    <?php if($po->terms_and_conditions): ?>
    <div style="background:#f9fafb;border-radius:6px;padding:12px;margin-bottom:20px">
        <div style="font-size:9px;text-transform:uppercase;color:#6b7280;font-weight:600;margin-bottom:6px">Terms & Conditions</div>
        <p style="font-size:10px;color:#374151;line-height:1.6"><?php echo e($po->terms_and_conditions); ?></p>
    </div>
    <?php endif; ?>

    <div class="sig-box">
        <div class="sig">Authorized Signatory<br><span style="color:#9ca3af"><?php echo e($companyName); ?></span></div>
        <div class="sig">Vendor Acknowledgment<br><span style="color:#9ca3af">Date: ____________________</span></div>
    </div>

    <div class="footer">
        <span><?php echo e($companyName); ?> · <?php echo e($company['company_address'] ?? config('kathford.college_address')); ?></span>
        <span>Generated <?php echo e(now()->format('d M Y H:i')); ?></span>
    </div>
</div>
</body>
</html>
<?php /**PATH /Users/ashbinkumarchamrel/Downloads/kathford-process/resources/views/purchase-orders/pdf.blade.php ENDPATH**/ ?>