<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Request for Quotation – <?php echo e($rfq->rfq_number); ?></title>
<style>
    body { font-family: Arial, sans-serif; background: #f3f4f6; margin: 0; padding: 0; }
    .wrapper { max-width: 600px; margin: 30px auto; background: #fff; border-radius: 12px; overflow: hidden; border: 1px solid #e5e7eb; }
    .header { background: #0B1E3D; padding: 24px 32px; }
    .header h1 { color: #fff; font-size: 18px; margin: 0; }
    .header span { color: #00A99D; }
    .rfq-badge { background: rgba(0,169,157,.2); color: #00A99D; display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 13px; margin-top: 8px; font-family: monospace; font-weight: 700; }
    .body { padding: 28px 32px; }
    .greeting { font-size: 16px; color: #1f2937; margin-bottom: 16px; }
    .intro { color: #374151; font-size: 14px; line-height: 1.7; margin-bottom: 24px; }
    .info-box { background: #f9fafb; border-left: 4px solid #00A99D; border-radius: 0 8px 8px 0; padding: 14px 18px; margin-bottom: 24px; }
    .info-row { display: flex; gap: 16px; margin-bottom: 6px; font-size: 13px; }
    .info-label { color: #6b7280; min-width: 120px; }
    .info-value { color: #111827; font-weight: 600; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
    thead { background: #0B1E3D; color: #fff; }
    thead th { padding: 8px 12px; text-align: left; font-size: 12px; font-weight: 600; text-transform: uppercase; }
    tbody td { padding: 8px 12px; border-bottom: 1px solid #f3f4f6; font-size: 13px; color: #374151; }
    tbody tr:nth-child(even) td { background: #f9fafb; }
    .btn { display: inline-block; background: #00A99D; color: #fff; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-size: 15px; font-weight: 700; margin-bottom: 12px; }
    .link-note { font-size: 11px; color: #9ca3af; margin-bottom: 24px; word-break: break-all; }
    .footer { background: #f9fafb; padding: 16px 32px; text-align: center; font-size: 12px; color: #9ca3af; border-top: 1px solid #f3f4f6; }
</style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>Kathford <span>International College</span></h1>
        <div class="rfq-badge"><?php echo e($rfq->rfq_number); ?></div>
    </div>
    <div class="body">
        <p class="greeting">Dear <strong><?php echo e($quote->vendor?->name); ?></strong>,</p>
        <p class="intro">
            Kathford International College invites you to submit a quotation for the items listed below.
            Please click the button below to access your secure quotation portal and submit your pricing.
        </p>

        <div class="info-box">
            <div class="info-row"><span class="info-label">RFQ Reference:</span><span class="info-value"><?php echo e($rfq->rfq_number); ?></span></div>
            <div class="info-row"><span class="info-label">Submission By:</span><span class="info-value"><?php echo e($rfq->deadline ? $rfq->deadline->format('d M Y') : 'As soon as possible'); ?></span></div>
            <?php if($rfq->notes): ?>
            <div class="info-row"><span class="info-label">Scope:</span><span class="info-value"><?php echo e($rfq->notes); ?></span></div>
            <?php endif; ?>
        </div>

        <p style="font-size:13px;color:#374151;font-weight:600;margin-bottom:10px">Items Requested:</p>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Description</th>
                    <th>Unit</th>
                    <th>Quantity</th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $quote->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td><?php echo e($idx + 1); ?></td>
                    <td><?php echo e($item->description); ?></td>
                    <td><?php echo e($item->unit); ?></td>
                    <td><?php echo e($item->quantity); ?></td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>

        <div style="text-align:center;margin-bottom:8px">
            <a href="<?php echo e($portalUrl); ?>" class="btn">Submit Quotation Online</a>
        </div>
        <p class="link-note">Or copy this link: <?php echo e($portalUrl); ?></p>
        <p style="font-size:12px;color:#ef4444">⚠ This link is unique to your company and expires in <?php echo e(config('kathford.rfq_link_expiry_days')); ?> days. Do not share it.</p>
    </div>
    <div class="footer">
        &copy; <?php echo e(date('Y')); ?> Kathford International College · <?php echo e(config('kathford.college_address')); ?>

    </div>
</div>
</body>
</html>
<?php /**PATH /Users/ashbinkumarchamrel/Downloads/kathford-process/resources/views/emails/rfq-invitation.blade.php ENDPATH**/ ?>