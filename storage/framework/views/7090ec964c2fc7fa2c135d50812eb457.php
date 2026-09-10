<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', 'Dashboard'); ?> – Kathford Process Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                fontFamily: { sans: ['Roboto', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
                extend: {
                    colors: {
                        navy:  '#1C3557',
                        'navy-dark': '#162A45',
                        'navy-light': '#254470',
                        keal:  '#00A99D',
                        'keal-dark': '#008C82',
                        'keal-light': '#E6F7F6',
                    }
                }
            }
        }
    </script>
    <style>
        [x-cloak] { display: none !important; }
        body {
            font-family: 'Roboto', ui-sans-serif, system-ui, sans-serif;
            font-size: 14px;
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
        }

        /* ── Sidebar nav links ── */
        .nav-link {
            display: flex; align-items: center; gap: 10px;
            padding: 8px 12px; border-radius: 6px;
            font-size: 13px; font-weight: 500;
            color: #B8C8DC; text-decoration: none;
            transition: background 0.15s, color 0.15s;
            white-space: nowrap;
        }
        .nav-link:hover { background: rgba(255,255,255,0.08); color: #fff; }
        .nav-link.active { background: #00A99D; color: #fff; }
        .nav-link svg { flex-shrink: 0; opacity: 0.85; }
        .nav-link.active svg { opacity: 1; }

        /* ── Section labels ── */
        .nav-section {
            padding: 16px 12px 4px;
            font-size: 10px; font-weight: 700;
            letter-spacing: 0.08em; text-transform: uppercase;
            color: #5A7A9A;
        }

        /* ── Page card ── */
        .kcard {
            background: #fff;
            border: 1px solid #E5E9EE;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .kcard-header {
            padding: 16px 20px;
            border-bottom: 1px solid #F0F2F5;
            display: flex; align-items: center; justify-content: space-between;
        }

        /* ── Status badges ── */
        .badge { display: inline-flex; align-items: center; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 500; white-space: nowrap; }
        .badge-draft     { background: #F1F3F7; color: #5A6878; }
        .badge-pending   { background: #FEF3C7; color: #92400E; }
        .badge-approved  { background: #D1FAE5; color: #065F46; }
        .badge-rejected  { background: #FEE2E2; color: #991B1B; }
        .badge-paid      { background: #D1FAE5; color: #065F46; }
        .badge-overdue   { background: #FEE2E2; color: #991B1B; }

        /* ── Primary button ── */
        .btn-primary, .btn-secondary, .btn-danger, .btn-quiet {
            display: inline-flex; min-height: 40px; align-items: center; justify-content: center; gap: 7px;
            padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 500;
            cursor: pointer; text-decoration: none; transition: background .15s, border-color .15s, color .15s;
        }
        .btn-primary {
            background: #00A99D; color: #fff; border: 1px solid #00A99D;
        }
        .btn-primary:hover { background: #008C82; }

        /* ── Secondary button ── */
        .btn-secondary { background: #fff; color: #374151; border: 1px solid #D1D5DB; }
        .btn-secondary:hover { background: #F9FAFB; border-color: #9CA3AF; }
        .btn-danger { background: #fff; color: #C2413A; border: 1px solid #F3B6B1; }
        .btn-danger:hover { background: #FFF4F2; border-color: #E57770; }
        .btn-quiet { min-height: 36px; padding: 6px 10px; background: transparent; color: #52708F; border: 1px solid transparent; }
        .btn-quiet:hover { color: #007F76; background: #F0FDFA; }

        /* ── Table ── */
        .ktable { width: 100%; border-collapse: collapse; font-size: 13px; }
        .ktable thead th {
            padding: 10px 16px; text-align: left;
            background: #F8F9FB; border-bottom: 1px solid #E5E9EE;
            font-size: 11px; font-weight: 700; letter-spacing: 0.035em;
            text-transform: uppercase; color: #6B7280; white-space: nowrap;
        }
        .ktable tbody tr { border-bottom: 1px solid #F0F2F5; transition: background 0.1s; }
        .ktable tbody tr:hover { background: #F8F9FB; }
        .ktable tbody td { padding: 12px 16px; color: #111827; vertical-align: middle; }
        .ktable tbody tr:last-child { border-bottom: none; }

        /* ── Scrollbar ── */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #D1D5DB; border-radius: 3px; }
    </style>
    <?php echo $__env->yieldPushContent('styles'); ?>
<link rel="stylesheet" href="<?php echo e(asset('css/portal.css')); ?>">
</head>
<body class="h-full" style="background:#F4F5F8;" x-data="{ sidebarOpen: false, userMenu: false }">
<?php
    $organisationLogo = \App\Models\Setting::get('company_logo_path');
    $organisationName = \App\Models\Setting::get('company_name', 'Kathford International College');
?>


<div x-show="sidebarOpen" x-cloak @click="sidebarOpen=false"
     class="fixed inset-0 z-20 bg-black/40 lg:hidden"></div>

<div class="flex h-full">

    
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
           class="fixed inset-y-0 left-0 z-30 w-56 flex flex-col transition-transform duration-200 lg:relative lg:translate-x-0 lg:flex-shrink-0"
           style="background:#1C3557;">

        
        <div class="flex items-center gap-3 px-4 py-4" style="border-bottom:1px solid rgba(255,255,255,0.07);">
            <div class="w-10 h-10 overflow-hidden rounded-md flex items-center justify-center font-bold text-white text-sm flex-shrink-0" style="background:#00A99D;">
                <?php if($organisationLogo): ?>
                    <img src="<?php echo e(asset('storage/'.$organisationLogo)); ?>" alt="Organisation logo" class="h-full w-full object-contain bg-white p-0.5">
                <?php else: ?>
                    KI
                <?php endif; ?>
            </div>
            <div class="min-w-0">
                <p class="truncate text-white font-semibold text-[13px] leading-tight" title="<?php echo e($organisationName); ?>"><?php echo e(\Illuminate\Support\Str::limit($organisationName, 24)); ?></p>
                <p class="text-xs" style="color:#7A9CC0;">Process Portal</p>
            </div>
        </div>

        
        <nav class="flex-1 overflow-y-auto px-3 py-2">

            <a href="<?php echo e(route('dashboard')); ?>"
               class="nav-link <?php echo e(request()->routeIs('dashboard') ? 'active' : ''); ?>">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Dashboard
            </a>

            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('activity_forms.view')): ?><p class="nav-section">Forms</p><?php endif; ?>

            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('activity_forms.view')): ?>
<a href="<?php echo e(route('activity-forms.index')); ?>"
               class="nav-link <?php echo e(request()->routeIs('activity-forms.*') ? 'active' : ''); ?>">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Activity Forms
            </a>
<?php endif; ?>

            <?php if(auth()->user()->canAny(['rfq.view','purchase_orders.view','checklists.view','payments.view','payment_authorisations.view','vendors.view','budgets.view'])): ?>
            <p class="nav-section">Procurement</p>

            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('rfq.view')): ?>
<a href="<?php echo e(route('rfq.index')); ?>"
               class="nav-link <?php echo e(request()->routeIs('rfq.*') ? 'active' : ''); ?>">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                RFQ / Quotations
            </a>
<?php endif; ?>

            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('purchase_orders.view')): ?>
<a href="<?php echo e(route('purchase-orders.index')); ?>"
               class="nav-link <?php echo e(request()->routeIs('purchase-orders.*') ? 'active' : ''); ?>">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Purchase Orders
            </a>
<?php endif; ?>

            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('checklists.view')): ?>
<a href="<?php echo e(route('checklists.index')); ?>"
               class="nav-link <?php echo e(request()->routeIs('checklists.*') ? 'active' : ''); ?>">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                </svg>
                Checklist
            </a>
<?php endif; ?>

            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('payments.view')): ?>
<a href="<?php echo e(route('payments.index')); ?>"
               class="nav-link <?php echo e(request()->routeIs('payments.*') ? 'active' : ''); ?>">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                Payment Schedule
            </a>
<?php endif; ?>

            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('payment_authorisations.view')): ?>
<a href="<?php echo e(route('payment-authorisations.index')); ?>"
               class="nav-link <?php echo e(request()->routeIs('payment-authorisations.*') ? 'active' : ''); ?>">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-9.618 3.04A12.02 12.02 0 003.944 12c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-2.042-.51-3.964-1.382-5.616z"/></svg>
                Payment Authorisation
            </a>
<?php endif; ?>

            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('vendors.view')): ?>
<a href="<?php echo e(route('vendors.index')); ?>"
               class="nav-link <?php echo e(request()->routeIs('vendors.*') ? 'active' : ''); ?>">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                Vendors
            </a>
<?php endif; ?>

            <?php if(auth()->user()->can('budgets.view')): ?>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('budgets.view')): ?>
<a href="<?php echo e(route('budgets.index')); ?>"
               class="nav-link <?php echo e(request()->routeIs('budgets.*') ? 'active' : ''); ?>">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-2.5 0-4.5 1.1-4.5 2.5S9.5 13 12 13s4.5 1.1 4.5 2.5S14.5 18 12 18m-4-10V6m8 12v-2m-4 2v2m0-16v2"/>
                </svg>
                Budgets
            </a>
<?php endif; ?>
            <?php endif; ?>
            <?php endif; ?>

            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('planning.view')): ?>
            <a href="<?php echo e(route('planning.index')); ?>" class="nav-link <?php echo e(request()->routeIs('planning.*') ? 'active' : ''); ?>">Planning</a>
            <?php endif; ?>
            <?php if(auth()->user()->isSuperAdmin() || auth()->user()->can('procurement_setup.manage')): ?>
            <a href="<?php echo e(route('settings.index')); ?>" class="nav-link <?php echo e(request()->routeIs('settings.*','admin.*') ? 'active' : ''); ?>">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h16M8 4v6m8-1v6m-8-1v6"/></svg>
                Settings
            </a>
            <?php endif; ?>
        </nav>

        
        <div class="px-3 py-3" style="border-top:1px solid rgba(255,255,255,0.07);">
            <div class="flex items-center gap-3 px-1">
                <?php if(auth()->user()->avatar): ?>
                    <img src="<?php echo e(auth()->user()->avatar); ?>" class="w-8 h-8 rounded-full object-cover flex-shrink-0" alt="">
                <?php else: ?>
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-sm font-bold flex-shrink-0"
                         style="background:#00A99D;"><?php echo e(substr(auth()->user()->name, 0, 1)); ?></div>
                <?php endif; ?>
                <div class="flex-1 min-w-0">
                    <p class="text-white text-xs font-semibold truncate"><?php echo e(auth()->user()->name); ?></p>
                    <p class="text-xs truncate" style="color:#7A9CC0;"><?php echo e(auth()->user()->role?->display_name); ?></p>
                </div>
                <form method="POST" action="<?php echo e(route('logout')); ?>">
                    <?php echo csrf_field(); ?>
                    <button type="submit" title="Sign out"
                            style="color:#7A9CC0;" class="hover:text-white transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

        
        <header class="flex-shrink-0 flex items-center gap-4 px-6 py-0" style="height:52px;background:#fff;border-bottom:1px solid #E5E9EE;">

            
            <button @click="sidebarOpen=!sidebarOpen" class="lg:hidden text-gray-400 hover:text-gray-600 mr-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            
            <div class="flex-1 min-w-0">
                <h1 class="text-sm font-semibold text-gray-800 truncate"><?php echo $__env->yieldContent('page-title', 'Dashboard'); ?></h1>
                <?php if (! empty(trim($__env->yieldContent('breadcrumb')))): ?>
                <p class="text-xs text-gray-400 mt-0.5"><?php echo $__env->yieldContent('breadcrumb'); ?></p>
                <?php endif; ?>
            </div>

            <?php echo $__env->make('partials.fiscal-year-selector', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            
            <div class="flex items-center gap-1">

                
                <?php $unread = \App\Models\Notification::where('user_id', auth()->id())->where('is_read', false)->count(); ?>
                <a href="<?php echo e(route('notifications.index')); ?>"
                   class="relative flex items-center justify-center w-9 h-9 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    <?php if($unread > 0): ?>
                        <span class="absolute top-1 right-1 w-2 h-2 rounded-full" style="background:#EF4444;"></span>
                    <?php endif; ?>
                </a>

                
                <div class="relative" x-data="{ open: false }">
                    <button @click="open=!open" @click.away="open=false"
                            class="flex items-center gap-2 pl-2 pr-3 py-1.5 rounded-lg hover:bg-gray-100 transition-colors">
                        <?php if(auth()->user()->avatar): ?>
                            <img src="<?php echo e(auth()->user()->avatar); ?>" class="w-7 h-7 rounded-full object-cover" alt="">
                        <?php else: ?>
                            <div class="w-7 h-7 rounded-full flex items-center justify-center text-white text-xs font-bold"
                                 style="background:#1C3557;"><?php echo e(substr(auth()->user()->name, 0, 1)); ?></div>
                        <?php endif; ?>
                        <span class="text-xs font-medium text-gray-700 hidden sm:block"><?php echo e(auth()->user()->name); ?></span>
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open" x-cloak x-transition
                         class="absolute right-0 mt-1 w-48 bg-white rounded-lg shadow-lg border border-gray-100 py-1 z-50">
                        <div class="px-4 py-2.5 border-b border-gray-100">
                            <p class="text-xs font-semibold text-gray-800 truncate"><?php echo e(auth()->user()->name); ?></p>
                            <p class="text-xs text-gray-400 truncate"><?php echo e(auth()->user()->email); ?></p>
                        </div>
                        <form method="POST" action="<?php echo e(route('logout')); ?>">
                            <?php echo csrf_field(); ?>
                            <button type="submit"
                                    class="w-full text-left px-4 py-2 text-xs text-gray-600 hover:bg-gray-50 hover:text-red-600 transition-colors flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                                Sign out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        
        <?php if(session('success') || $errors->any()): ?>
        <div class="px-6 pt-4 space-y-2 flex-shrink-0">
            <?php if(session('success')): ?>
                <div class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm"
                     style="background:#F0FDF8;border:1px solid #A7F3D0;color:#065F46;"
                     x-data x-init="setTimeout(() => $el.remove(), 5000)">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span><?php echo e(session('success')); ?></span>
                </div>
            <?php endif; ?>
            <?php if($errors->any()): ?>
                <div class="rounded-lg px-4 py-3 text-sm"
                     style="background:#FFF1F2;border:1px solid #FECDD3;color:#991B1B;">
                    <ul class="space-y-0.5 list-disc list-inside">
                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($error); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        
        <main class="flex-1 overflow-y-auto p-6">
            <?php echo $__env->make('partials.fiscal-year-notice', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php if(request()->routeIs('admin.*') && (auth()->user()->isSuperAdmin() || auth()->user()->can('procurement_setup.manage'))): ?>
                    <a href="<?php echo e(route('settings.index')); ?>" class="inline-block text-sm text-teal-700 mb-4">← Back to Settings</a>
                <?php endif; ?>
                <?php echo $__env->yieldContent('content'); ?>
        </main>
    </div>

</div>

<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
<?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH /Users/ashbinkumarchamrel/Downloads/kathford-process/resources/views/layouts/app.blade.php ENDPATH**/ ?>