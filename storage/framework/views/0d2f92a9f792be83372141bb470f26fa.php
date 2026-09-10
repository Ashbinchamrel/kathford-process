<?php $__env->startSection('title', 'Approval Chains'); ?>
<?php $__env->startSection('page-title', 'Approval Chains'); ?>
<?php $__env->startSection('breadcrumb', 'Administration › Approval Chains'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6" x-data="chainManager()">

    
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm text-gray-500 mt-0.5">
                Manage the ordered approval route for <strong>Activity Forms</strong>, <strong>Purchase Requests</strong>, <strong>Purchase Orders</strong>, and <strong>Payment Authorisations</strong>. Each chain defines sequential <strong>verifier</strong> and <strong>approver</strong> layers; only the next assigned person can act.
            </p>
        </div>
        <button @click="openCreate()" class="btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            New Chain
        </button>
    </div>

    <div class="kcard p-5">
        <div class="flex flex-col gap-2 border-b border-gray-100 pb-4"><h2 class="text-sm font-semibold text-gray-900">Payment Authorisation Channels</h2><p class="text-sm text-gray-500">Each scheduled payment period selects one channel. A channel fixes the approval chain for its own Payment Authorisation batch, so different schedules can follow different controls.</p></div>
        <div class="mt-4 overflow-x-auto"><table class="min-w-full text-sm"><thead class="bg-gray-50"><tr><th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">Channel</th><th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">Approval chain</th><th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">Default account</th><th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">Status</th><th class="px-3 py-2"></th></tr></thead><tbody class="divide-y divide-gray-100"><?php $__empty_1 = true; $__currentLoopData = $paymentChannels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $channel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><tr><td class="px-3 py-3"><p class="font-semibold text-gray-800"><?php echo e($channel->name); ?></p><?php if($channel->notes): ?><p class="mt-0.5 text-xs text-gray-400"><?php echo e($channel->notes); ?></p><?php endif; ?></td><td class="px-3 py-3"><?php echo e($channel->approvalChain?->name); ?></td><td class="px-3 py-3"><?php echo e($channel->paymentAccount?->name ?: 'Any account'); ?></td><td class="px-3 py-3"><span class="badge <?php echo e($channel->is_active ? 'badge-approved' : 'badge-draft'); ?>"><?php echo e($channel->is_active ? 'Active' : 'Inactive'); ?></span></td><td class="px-3 py-3 text-right"><details class="inline-block text-left"><summary class="cursor-pointer text-sm font-semibold text-teal-700">Edit</summary><form method="POST" action="<?php echo e(route('admin.approval-chain.payment-authorisation-channels.update', $channel)); ?>" class="mt-2 grid w-96 gap-2 rounded-lg border border-gray-200 bg-white p-3 shadow-lg"><?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?><input name="name" value="<?php echo e($channel->name); ?>" required class="rounded border border-gray-300 px-2 py-1.5 text-sm"><select name="approval_chain_id" required class="rounded border border-gray-300 px-2 py-1.5 text-sm"><?php $__currentLoopData = $chains->where('is_active', true); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $chain): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($chain->id); ?>" <?php if($channel->approval_chain_id === $chain->id): echo 'selected'; endif; ?>><?php echo e($chain->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select><select name="payment_account_id" class="rounded border border-gray-300 px-2 py-1.5 text-sm"><option value="">Any account</option><?php $__currentLoopData = $paymentAccounts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $account): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($account->id); ?>" <?php if($channel->payment_account_id === $account->id): echo 'selected'; endif; ?>><?php echo e($account->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select><textarea name="notes" rows="2" class="rounded border border-gray-300 px-2 py-1.5 text-sm"><?php echo e($channel->notes); ?></textarea><label class="text-xs text-gray-700"><input type="checkbox" name="is_active" value="1" <?php if($channel->is_active): echo 'checked'; endif; ?>> Active</label><button class="btn-primary justify-center">Save channel</button></form></details></td></tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr><td colspan="5" class="px-3 py-6 text-center text-gray-400">No channels yet. Create one below.</td></tr><?php endif; ?></tbody></table></div>
        <form method="POST" action="<?php echo e(route('admin.approval-chain.payment-authorisation-channels.store')); ?>" class="mt-5 grid grid-cols-1 gap-3 border-t border-gray-100 pt-5 md:grid-cols-4"><?php echo csrf_field(); ?><div><label class="mb-1 block text-xs font-semibold text-gray-600">Channel name *</label><input name="name" required placeholder="e.g. Supplier Payments" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div><div><label class="mb-1 block text-xs font-semibold text-gray-600">Approval chain *</label><select name="approval_chain_id" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"><option value="">Select chain</option><?php $__currentLoopData = $chains->where('is_active', true); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $chain): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($chain->id); ?>"><?php echo e($chain->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div><div><label class="mb-1 block text-xs font-semibold text-gray-600">Default account</label><select name="payment_account_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"><option value="">Any account</option><?php $__currentLoopData = $paymentAccounts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $account): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($account->id); ?>"><?php echo e($account->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div><div class="flex items-end gap-3"><label class="mb-2 text-sm text-gray-700"><input type="checkbox" name="is_active" value="1" checked> Active</label><button class="btn-primary flex-1 justify-center">Add channel</button></div><div class="md:col-span-4"><label class="mb-1 block text-xs font-semibold text-gray-600">Notes</label><input name="notes" maxlength="1000" placeholder="When should Finance use this channel?" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div></form>
    </div>

    <div class="kcard p-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h2 class="text-sm font-semibold text-gray-900">Purchase Order approval setting</h2>
                <p class="mt-1 text-sm text-gray-500">Every Purchase Order uses this chain when submitted. PO creators cannot select or change it.</p>
            </div>
            <form method="POST" action="<?php echo e(route('admin.approval-chain.purchase-orders.update')); ?>" class="flex w-full max-w-xl gap-2">
                <?php echo csrf_field(); ?>
                <select name="purchase_order_approval_chain_id" required class="min-w-0 flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="">Select the chain for Purchase Orders…</option>
                    <?php $__currentLoopData = $chains->where('is_active', true); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $chain): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($chain->id); ?>" <?php if($poApprovalChainId === $chain->id): echo 'selected'; endif; ?>><?php echo e($chain->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <button class="btn-primary whitespace-nowrap">Save PO setting</button>
            </form>
        </div>
    </div>

    
    <?php $__empty_1 = true; $__currentLoopData = $chains; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $chain): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <div class="kcard">
        <div class="kcard-header">
            <div class="flex items-center gap-3">
                <div class="w-2 h-2 rounded-full" style="background:<?php echo e($chain->is_active ? '#00A99D' : '#D1D5DB'); ?>;"></div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-900"><?php echo e($chain->name); ?></h3>
                    <p class="text-xs text-gray-400 mt-0.5">
                        <?php if($chain->is_default): ?>
                            <span class="badge badge-approved mr-1">Default</span>
                        <?php endif; ?>
                        <?php if(!$chain->is_active): ?>
                            <span class="badge badge-draft mr-1">Inactive</span>
                        <?php endif; ?>
                        Created <?php echo e($chain->created_at->format('d M Y')); ?>

                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button @click="openEdit('<?php echo e(route('admin.approval-chain.update', $chain)); ?>', <?php echo e(json_encode([
                    'name'         => $chain->name,
                    'notes'        => $chain->notes,
                    'is_default'   => $chain->is_default,
                    'is_active'    => $chain->is_active,
                    'verifier_ids' => $chain->verifiers->pluck('id')->values(),
                    'approver_ids' => $chain->approvers->pluck('id')->values(),
                ])); ?>)" class="btn-secondary" style="font-size:12px;padding:5px 12px;">
                    Edit
                </button>
                <?php if(!$chain->is_default): ?>
                <form method="POST" action="<?php echo e(route('admin.approval-chain.destroy', $chain)); ?>"
                      onsubmit="return confirm('Remove this chain? Forms using it will lose their assignment.');">
                    <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                    <button type="submit" class="btn-secondary" style="font-size:12px;padding:5px 12px;color:#DC2626;border-color:#FECACA;">
                        Remove
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">
                    Verifiers — sequential layers
                </p>
                <?php $__empty_2 = true; $__currentLoopData = $chain->verifiers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_2 = false; ?>
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                         style="background:#1C3557;"><?php echo e(substr($user->name, 0, 1)); ?></div>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-800 leading-tight">Verifier layer <?php echo e($loop->iteration); ?> · <?php echo e($user->name); ?></p>
                        <p class="text-xs text-gray-400"><?php echo e($user->email); ?></p>
                    </div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_2): ?>
                <p class="text-xs text-amber-600 bg-amber-50 rounded px-3 py-2">No verifiers assigned</p>
                <?php endif; ?>
            </div>

            
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">
                    Approvers — sequential layers
                </p>
                <?php $__empty_2 = true; $__currentLoopData = $chain->approvers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_2 = false; ?>
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                         style="background:#00A99D;"><?php echo e(substr($user->name, 0, 1)); ?></div>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-800 leading-tight">Approver layer <?php echo e($loop->iteration); ?> · <?php echo e($user->name); ?></p>
                        <p class="text-xs text-gray-400"><?php echo e($user->email); ?></p>
                    </div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_2): ?>
                <p class="text-xs text-amber-600 bg-amber-50 rounded px-3 py-2">No approvers assigned</p>
                <?php endif; ?>
            </div>
        </div>

        <?php if($chain->notes): ?>
        <div class="px-5 pb-4">
            <p class="text-xs text-gray-500 italic"><?php echo e($chain->notes); ?></p>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <div class="kcard p-12 text-center">
        <svg class="w-10 h-10 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
        </svg>
        <p class="text-sm text-gray-500">No approval chains yet. Create one to start routing forms.</p>
    </div>
    <?php endif; ?>

    
    <div x-show="modalOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background:rgba(0,0,0,0.5);">
        <div @click.away="modalOpen=false"
             class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-screen overflow-y-auto">

            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h2 class="text-base font-semibold text-gray-900" x-text="updateUrl ? 'Edit Approval Chain' : 'New Approval Chain'"></h2>
                <button @click="modalOpen=false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form :action="updateUrl || '<?php echo e(route('admin.approval-chain.store')); ?>'"
                  method="POST" class="p-6 space-y-5">
                <?php echo csrf_field(); ?>
                <input type="hidden" :name="updateUrl ? '_method' : ''" value="PUT">

                
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Chain Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" x-model="form.name" required
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:border-transparent"
                           style="--tw-ring-color:#00A99D;"
                           placeholder="e.g. Default Chain, IT Purchase Chain">
                </div>

                
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">
                        Verifiers <span class="text-red-500">*</span>
                        <span class="font-normal text-gray-400 ml-1">— selection order defines verifier layers; use arrows to reorder</span>
                    </label>
                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        <div class="bg-gray-50 px-3 py-2 border-b border-gray-200">
                            <input type="text" x-model="verifierSearch" placeholder="Search users…"
                                   class="w-full bg-transparent text-xs focus:outline-none">
                        </div>
                        <div class="max-h-40 overflow-y-auto p-2 space-y-1">
                            <?php if($verifierUsers->isEmpty()): ?><p class="p-2 text-xs text-gray-500">No active users have the Verifier role. Assign this role under Users first.</p><?php endif; ?>
                            <?php $__currentLoopData = $verifierUsers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <label class="flex items-center gap-2 px-2 py-1.5 rounded cursor-pointer hover:bg-gray-50"
                                   x-show="!verifierSearch || <?php echo \Illuminate\Support\Js::from(strtolower($user->name.' '.$user->email))->toHtml() ?>.includes(verifierSearch.toLowerCase())">
                                <input type="checkbox" value="<?php echo e($user->id); ?>"
                                       :checked="form.verifier_ids.includes('<?php echo e($user->id); ?>')"
                                       @change="toggleVerifier('<?php echo e($user->id); ?>')"
                                       class="rounded" style="accent-color:#1C3557;">
                                <div class="w-6 h-6 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                                     style="background:#1C3557;"><?php echo e(substr($user->name, 0, 1)); ?></div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-medium text-gray-800"><?php echo e($user->name); ?></p>
                                    <p class="text-xs text-gray-400"><?php echo e($user->role?->display_name); ?> · <?php echo e($user->email); ?></p>
                                </div>
                                <span x-show="form.verifier_ids.includes('<?php echo e($user->id); ?>')" class="inline-flex items-center gap-1 text-xs font-semibold text-slate-600"><span x-text="'L'+verifierLayer('<?php echo e($user->id); ?>')"></span><button type="button" @click.prevent="moveVerifier('<?php echo e($user->id); ?>', -1)" class="rounded border px-1 hover:bg-gray-100">↑</button><button type="button" @click.prevent="moveVerifier('<?php echo e($user->id); ?>', 1)" class="rounded border px-1 hover:bg-gray-100">↓</button></span>
                            </label>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                        <div class="px-3 py-1.5 bg-gray-50 border-t border-gray-200">
                            <template x-for="id in form.verifier_ids" :key="'verifier-'+id"><input type="hidden" name="verifier_ids[]" :value="id"></template>
                            <p class="text-xs text-gray-500"><span x-text="form.verifier_ids.length"></span> selected · their order is the workflow order</p>
                        </div>
                    </div>
                </div>

                <p x-show="removedIneligible" class="rounded-lg bg-amber-50 p-3 text-xs text-amber-800">Some previous members are inactive or do not have the required role. Choose eligible replacements before saving. Existing assignments change only when you save.</p>
                
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">
                        Approvers <span class="text-red-500">*</span>
                        <span class="font-normal text-gray-400 ml-1">— selection order defines approver layers; use arrows to reorder</span>
                    </label>
                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        <div class="bg-gray-50 px-3 py-2 border-b border-gray-200">
                            <input type="text" x-model="approverSearch" placeholder="Search users…"
                                   class="w-full bg-transparent text-xs focus:outline-none">
                        </div>
                        <div class="max-h-40 overflow-y-auto p-2 space-y-1">
                            <?php if($approverUsers->isEmpty()): ?><p class="p-2 text-xs text-gray-500">No active users have the Approver role. Assign this role under Users first.</p><?php endif; ?>
                            <?php $__currentLoopData = $approverUsers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <label class="flex items-center gap-2 px-2 py-1.5 rounded cursor-pointer hover:bg-gray-50"
                                   x-show="!approverSearch || <?php echo \Illuminate\Support\Js::from(strtolower($user->name.' '.$user->email))->toHtml() ?>.includes(approverSearch.toLowerCase())">
                                <input type="checkbox" value="<?php echo e($user->id); ?>"
                                       :checked="form.approver_ids.includes('<?php echo e($user->id); ?>')"
                                       @change="toggleApprover('<?php echo e($user->id); ?>')"
                                       class="rounded" style="accent-color:#00A99D;">
                                <div class="w-6 h-6 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                                     style="background:#00A99D;"><?php echo e(substr($user->name, 0, 1)); ?></div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-medium text-gray-800"><?php echo e($user->name); ?></p>
                                    <p class="text-xs text-gray-400"><?php echo e($user->role?->display_name); ?> · <?php echo e($user->email); ?></p>
                                </div>
                                <span x-show="form.approver_ids.includes('<?php echo e($user->id); ?>')" class="inline-flex items-center gap-1 text-xs font-semibold text-slate-600"><span x-text="'L'+approverLayer('<?php echo e($user->id); ?>')"></span><button type="button" @click.prevent="moveApprover('<?php echo e($user->id); ?>', -1)" class="rounded border px-1 hover:bg-gray-100">↑</button><button type="button" @click.prevent="moveApprover('<?php echo e($user->id); ?>', 1)" class="rounded border px-1 hover:bg-gray-100">↓</button></span>
                            </label>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                        <div class="px-3 py-1.5 bg-gray-50 border-t border-gray-200">
                            <template x-for="id in form.approver_ids" :key="'approver-'+id"><input type="hidden" name="approver_ids[]" :value="id"></template>
                            <p class="text-xs text-gray-500"><span x-text="form.approver_ids.length"></span> selected · their order is the workflow order</p>
                        </div>
                    </div>
                </div>

                
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Notes (optional)</label>
                    <textarea name="notes" x-model="form.notes" rows="2"
                              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:border-transparent resize-none"
                              placeholder="Internal notes about when to use this chain"></textarea>
                </div>

                
                <div class="flex items-center gap-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_default" value="1" x-model="form.is_default"
                               class="rounded" style="accent-color:#00A99D;">
                        <span class="text-sm text-gray-700">Set as default chain</span>
                    </label>
                    <label x-show="updateUrl" x-cloak class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" x-model="form.is_active"
                               class="rounded" style="accent-color:#00A99D;">
                        <span class="text-sm text-gray-700">Active</span>
                    </label>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="modalOpen=false" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-primary" x-text="updateUrl ? 'Save Changes' : 'Create Chain'"></button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
function chainManager() {
    return {
        verifierUserIds: <?php echo \Illuminate\Support\Js::from($verifierUsers->pluck('id')->values())->toHtml() ?>,
        approverUserIds: <?php echo \Illuminate\Support\Js::from($approverUsers->pluck('id')->values())->toHtml() ?>,
        removedIneligible: false,
        modalOpen: false,
        updateUrl: null,
        verifierSearch: '',
        approverSearch: '',
        form: {
            name: '',
            notes: '',
            is_default: false,
            is_active: true,
            verifier_ids: [],
            approver_ids: [],
        },

        openCreate() {
            this.removedIneligible = false;
            this.updateUrl = null;
            this.form = { name: '', notes: '', is_default: false, is_active: true, verifier_ids: [], approver_ids: [] };
            this.verifierSearch = '';
            this.approverSearch = '';
            this.modalOpen = true;
        },

        openEdit(updateUrl, data) {
            this.removedIneligible = (data.verifier_ids || []).some(id => !this.verifierUserIds.includes(String(id))) || (data.approver_ids || []).some(id => !this.approverUserIds.includes(String(id)));
            this.updateUrl = updateUrl;
            this.form = {
                name: data.name,
                notes: data.notes || '',
                is_default: data.is_default,
                is_active: data.is_active,
                verifier_ids: (data.verifier_ids || []).map(String).filter(id => this.verifierUserIds.includes(id)),
                approver_ids: (data.approver_ids || []).map(String).filter(id => this.approverUserIds.includes(id)),
            };
            this.verifierSearch = '';
            this.approverSearch = '';
            this.modalOpen = true;
        },

        toggleVerifier(id) {
            const idx = this.form.verifier_ids.indexOf(String(id));
            if (idx === -1) this.form.verifier_ids.push(String(id));
            else this.form.verifier_ids.splice(idx, 1);
        },

        verifierLayer(id) {
            return this.form.verifier_ids.indexOf(String(id)) + 1;
        },

        moveVerifier(id, direction) {
            const index = this.form.verifier_ids.indexOf(String(id));
            const target = index + direction;
            if (index < 0 || target < 0 || target >= this.form.verifier_ids.length) return;
            [this.form.verifier_ids[index], this.form.verifier_ids[target]] = [this.form.verifier_ids[target], this.form.verifier_ids[index]];
        },

        toggleApprover(id) {
            const idx = this.form.approver_ids.indexOf(String(id));
            if (idx === -1) this.form.approver_ids.push(String(id));
            else this.form.approver_ids.splice(idx, 1);
        },

        approverLayer(id) {
            return this.form.approver_ids.indexOf(String(id)) + 1;
        },

        moveApprover(id, direction) {
            const index = this.form.approver_ids.indexOf(String(id));
            const target = index + direction;
            if (index < 0 || target < 0 || target >= this.form.approver_ids.length) return;
            [this.form.approver_ids[index], this.form.approver_ids[target]] = [this.form.approver_ids[target], this.form.approver_ids[index]];
        },
    };
}
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ashbinkumarchamrel/Downloads/kathford-process/resources/views/admin/approval-chain/index.blade.php ENDPATH**/ ?>