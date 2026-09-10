<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $__env->yieldContent('title', 'Sign In'); ?> – Kathford Process Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { fontFamily: { sans: ['Roboto','ui-sans-serif','system-ui','sans-serif'] } } }</script>
    <style>body { font-family: 'Roboto', ui-sans-serif, system-ui, sans-serif; -webkit-font-smoothing: antialiased; }</style>
</head>
<?php
    $organisationLogo = \App\Models\Setting::get('company_logo_path');
    $organisationName = \App\Models\Setting::get('company_name', 'Kathford International College');
?>
<body class="h-full flex" style="background:#F4F5F8;">

    <div class="hidden lg:flex lg:w-5/12 flex-col justify-between p-12" style="background:#1C3557;">
        <div>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 overflow-hidden rounded-md flex items-center justify-center font-bold text-white text-sm"
                     style="background:#00A99D;"><?php if($organisationLogo): ?><img src="<?php echo e(asset('storage/'.$organisationLogo)); ?>" alt="Organisation logo" class="h-full w-full bg-white object-contain p-0.5"><?php else: ?> KI <?php endif; ?></div>
                <div>
                    <p class="text-white font-semibold text-sm leading-tight"><?php echo e($organisationName); ?></p>
                    <p class="text-xs" style="color:#7A9CC0;">Process Portal</p>
                </div>
            </div>

        </div>

        <p class="text-xs" style="color:#4A6A8A;">
            © <?php echo e(date('Y')); ?> Kathford International College. All rights reserved.
        </p>
    </div>

    
    <div class="flex-1 flex items-center justify-center p-8">
        <div class="w-full max-w-sm">

            
            <div class="flex items-center gap-3 mb-8 lg:hidden">
                <div class="w-10 h-10 overflow-hidden rounded-md flex items-center justify-center font-bold text-white text-sm"
                     style="background:#1C3557;"><?php if($organisationLogo): ?><img src="<?php echo e(asset('storage/'.$organisationLogo)); ?>" alt="Organisation logo" class="h-full w-full bg-white object-contain p-0.5"><?php else: ?> KI <?php endif; ?></div>
                <div>
                    <p class="text-gray-900 font-semibold text-sm leading-tight"><?php echo e($organisationName); ?></p>
                    <p class="text-xs text-gray-400">Process Portal</p>
                </div>
            </div>

            <?php echo $__env->yieldContent('content'); ?>

            <p class="text-xs text-center mt-8 text-gray-400 lg:hidden">
                © <?php echo e(date('Y')); ?> Kathford International College
            </p>
        </div>
    </div>

</body>
</html>
<?php /**PATH /Users/ashbinkumarchamrel/Downloads/kathford-process/resources/views/layouts/auth.blade.php ENDPATH**/ ?>