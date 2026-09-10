<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Portal - Kathford</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={theme:{fontFamily:{sans:['Roboto','ui-sans-serif','system-ui','sans-serif']}}}</script>
    <style>body{font-family:'Roboto',ui-sans-serif,system-ui,sans-serif;-webkit-font-smoothing:antialiased}</style>
</head>
<?php
    $organisationLogo = \App\Models\Setting::get('company_logo_path');
    $organisationName = \App\Models\Setting::get('company_name', 'Kathford International College');
?>
<body class="min-h-screen bg-slate-50 flex items-center justify-center p-4">
    <main class="w-full max-w-md">
        <div class="mb-6 text-center">
            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center overflow-hidden rounded-lg bg-slate-900 font-bold text-white"><?php if($organisationLogo): ?><img src="<?php echo e(asset('storage/'.$organisationLogo)); ?>" alt="Organisation logo" class="h-full w-full bg-white object-contain p-1"><?php else: ?> KI <?php endif; ?></div>
            <h1 class="text-xl font-bold text-slate-900">Vendor Portal</h1>
            <p class="mt-1 text-sm text-slate-500"><?php echo e($organisationName); ?></p>
        </div>
        <form method="POST" action="<?php echo e(route('vendor.portal.authenticate')); ?>" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
            <?php echo csrf_field(); ?>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Approved vendor email</label>
                <input type="email" name="email" value="<?php echo e(old('email')); ?>" required autofocus autocomplete="email"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-teal-500">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Password</label>
                <input type="password" name="password" required autocomplete="current-password"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-teal-500">
            </div>
            <?php if($errors->any()): ?>
                <p class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700"><?php echo e($errors->first()); ?></p>
            <?php endif; ?>
            <button class="w-full rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Sign in</button>
        </form>
        <p class="mt-4 text-center text-xs text-slate-400">Your administrator sets the first password and can reset access if needed.</p>
    </main>
</body>
</html>
<?php /**PATH /Users/ashbinkumarchamrel/Downloads/kathford-process/resources/views/vendor-portal/login.blade.php ENDPATH**/ ?>