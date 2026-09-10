<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Purchase Order <?php echo e($po->po_number); ?></title>
<style>
    body { font-family: Arial, sans-serif; background: #f3f4f6; margin: 0; padding: 0; }
    .wrapper { max-width: 600px; margin: 30px auto; background: #fff; border-radius: 12px; overflow: hidden; border: 1px solid #e5e7eb; }
    .header { background: #0B1E3D; padding: 24px 32px; }
    .header h1 { color: #fff; font-size: 18px; margin: 0; }
    .header .po-num { color: #00A99D; font-family: monospace; font-size: 20px; font-weight: 700; margin-top: 4px; }
    .body { padding: 28px 32px; }
    .greeting { font-size: 15px; color: #1f2937; margin-bottom: 16px; }
    .intro { color: #374151; font-size: 14px; line-height: 1.7; margin-bottom: 24px; }
    .meta { display: flex; flex-wrap: wrap; gap: 16px; background: #f9fafb; border-radius: 8px; padding: 14px 18px; margin-bottom: 24px; }
    .meta-item .label { font-size: 10px; text-transform: uppercase; color: #6b7280; font-weight: 600; margin-bottom: 3px; }
    .meta-item .value { font-size: 13px; font-weight: 600; color: #111827; }
    .attachment-note { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 12px 16px; font-size: 13px; color: #1d4ed8; margin-bottom: 24px; }
    .footer { background: #f9fafb; padding: 16px 32px; text-align: center; font-size: 12px; color: #9ca3af; border-top: 1px solid #f3f4f6; }
</style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>Kathford International College</h1>
        <div class="po-num"><?php echo e($po->po_number); ?></div>
    </div>
    <div class="body">
        <p class="greeting">Dear <strong><?php echo e($po->vendor?->name); ?></strong>,</p>
        <p class="intro">
            Please find attached our Purchase Order <strong><?php echo e($po->po_number); ?></strong>. It contains only the item(s) awarded to your company. Kindly review and acknowledge receipt at your earliest convenience.
        </p>

        <div class="meta">
            <div class="meta-item"><div class="label">PO Date</div><div class="value"><?php echo e($po->created_at?->format('d M Y') ?? date('d M Y')); ?></div></div>
            <div class="meta-item"><div class="label">Delivery By</div><div class="value"><?php echo e($po->expected_delivery_date?->format('d M Y') ?? 'To be agreed'); ?></div></div>
            <div class="meta-item"><div class="label">RFQ Reference</div><div class="value"><?php echo e($po->rfqQuote?->rfq?->rfq_number ?? 'Standalone PO'); ?></div></div>
            <div class="meta-item"><div class="label">Total Amount</div><div class="value">Rs <?php echo e(number_format($po->subtotal, 2)); ?></div></div>
            <div class="meta-item"><div class="label">Tax Amount</div><div class="value">Rs <?php echo e(number_format($po->tax_amount, 2)); ?></div></div>
            <div class="meta-item"><div class="label">Grand Total</div><div class="value">Rs <?php echo e(number_format($po->total_amount, 2)); ?></div></div>
        </div>

        <div class="attachment-note">
            📎 The Purchase Order document is attached as a PDF. Please sign and return a copy for our records.
        </div>

        <p style="font-size:13px;color:#374151;line-height:1.6;margin-bottom:20px">You can also view this PO and its awarded items in your <a href="<?php echo e($portalUrl); ?>" style="color:#00A99D">Kathford Vendor Portal</a>.</p>

        <p style="font-size:13px;color:#374151">
            For any queries regarding this purchase order, please contact our Accounts department at
            <a href="mailto:<?php echo e(config('kathford.college_email', 'accounts@kathford.edu.np')); ?>" style="color:#00A99D"><?php echo e(config('kathford.college_email', 'accounts@kathford.edu.np')); ?></a>.
        </p>
    </div>
    <div class="footer">
        &copy; <?php echo e(date('Y')); ?> Kathford International College · <?php echo e(config('kathford.college_address')); ?>

    </div>
</div>
</body>
</html>
<?php /**PATH /Users/ashbinkumarchamrel/Downloads/kathford-process/resources/views/emails/purchase-order.blade.php ENDPATH**/ ?>