<?php

namespace Database\Seeders;

use App\Models\ActivityForm;
use App\Models\ApprovalChain;
use App\Models\Department;
use App\Models\FormCategory;
use App\Models\FormLineItem;
use App\Models\GoodsReceivedItem;
use App\Models\GoodsReceivedNote;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Rfq;
use App\Models\RfqQuote;
use App\Models\RfqQuoteItem;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // ── 1. Permissions ─────────────────────────────────────
        Permission::seedDefaults();
        $this->command->info('✓ Permissions seeded');

        // ── 2. Roles & Departments ─────────────────────────────
        $roles = Role::pluck('id', 'name');
        $depts = Department::pluck('id', 'code');

        // ── 3. Users ───────────────────────────────────────────
        $superAdmin = User::firstOrCreate(['email' => 'admin@kathford.edu.np'], [
            'name' => 'System Administrator', 'google_id' => 'g_super_001',
            'role_id' => $roles['super_admin'], 'department_id' => $depts['ADMIN'],
            'designation' => 'System Administrator', 'is_active' => true,
        ]);
        $verifier1 = User::firstOrCreate(['email' => 'ram.krishna@kathford.edu.np'], [
            'name' => 'Ram Krishna Shrestha', 'google_id' => 'g_verifier_001',
            'role_id' => $roles['verifier'], 'department_id' => $depts['ACAD'],
            'designation' => 'Head of Academic Affairs', 'is_active' => true,
        ]);
        $verifier2 = User::firstOrCreate(['email' => 'sita.poudel@kathford.edu.np'], [
            'name' => 'Sita Poudel', 'google_id' => 'g_verifier_002',
            'role_id' => $roles['verifier'], 'department_id' => $depts['ADMIN'],
            'designation' => 'Administrative Officer', 'is_active' => true,
        ]);
        $approver1 = User::firstOrCreate(['email' => 'binod.joshi@kathford.edu.np'], [
            'name' => 'Binod Joshi', 'google_id' => 'g_approver_001',
            'role_id' => $roles['approver'], 'department_id' => $depts['ADMIN'],
            'designation' => 'College Principal', 'is_active' => true,
        ]);
        $finance1 = User::firstOrCreate(['email' => 'finance@kathford.edu.np'], [
            'name' => 'Kamal Adhikari', 'google_id' => 'g_finance_001',
            'role_id' => $roles['finance'], 'department_id' => $depts['FIN'],
            'designation' => 'Finance Manager', 'is_active' => true,
        ]);
        $general1 = User::firstOrCreate(['email' => 'priya.sharma@kathford.edu.np'], [
            'name' => 'Priya Sharma', 'google_id' => 'g_general_001',
            'role_id' => $roles['general'], 'department_id' => $depts['IT'],
            'designation' => 'IT Coordinator', 'is_active' => true,
        ]);
        $general2 = User::firstOrCreate(['email' => 'deepak.thapa@kathford.edu.np'], [
            'name' => 'Deepak Thapa', 'google_id' => 'g_general_002',
            'role_id' => $roles['general'], 'department_id' => $depts->get('MKT', $depts->get('IT')),
            'designation' => 'Marketing Officer', 'is_active' => true,
        ]);
        $this->command->info('✓ Users seeded (7 users)');

        // ── 4. Permissions per user ────────────────────────────
        $financePerms  = Permission::whereIn('module', ['activity_forms','purchase_requests','rfq','purchase_orders','grn','payments','vendors'])->pluck('id')->all();
        $verifierPerms = Permission::whereIn('module', ['activity_forms','purchase_requests','vendors'])->pluck('id')->all();
        $approverPerms = Permission::whereIn('module', ['activity_forms','purchase_requests','rfq','vendors'])->pluck('id')->all();
        $generalPerms  = Permission::whereIn('key', ['activity_forms.view','activity_forms.create','activity_forms.edit','activity_forms.submit'])->pluck('id')->all();

        $grant = function (User $u, array $ids) use ($superAdmin) {
            $sync = [];
            foreach ($ids as $pid) {
                $sync[$pid] = ['granted_by' => $superAdmin->id, 'granted_at' => now()];
            }
            $u->permissions()->syncWithoutDetaching($sync);
        };

        $grant($finance1,  $financePerms);
        $grant($verifier1, $verifierPerms);
        $grant($verifier2, $verifierPerms);
        $grant($approver1, $approverPerms);
        $grant($general1,  $generalPerms);
        $grant($general2,  $generalPerms);
        $this->command->info('✓ User permissions granted');

        // ── 5. Approval chains ─────────────────────────────────
        $defaultChain = ApprovalChain::firstOrCreate(['name' => 'Default Approval Chain'], [
            'notes' => 'Standard approval chain for all activities',
            'is_default' => true, 'is_active' => true,
        ]);
        $defaultChain->syncVerifiers([$verifier1->id, $verifier2->id]);
        $defaultChain->syncApprovers([$approver1->id]);

        $financeChain = ApprovalChain::firstOrCreate(['name' => 'Finance & Procurement Chain'], [
            'notes' => 'Approval chain for finance and procurement',
            'is_default' => false, 'is_active' => true,
        ]);
        $financeChain->syncVerifiers([$verifier1->id]);
        $financeChain->syncApprovers([$approver1->id]);

        // Link categories to chains
        FormCategory::where('code', 'PAA')->update(['approval_chain_id' => $defaultChain->id]);
        FormCategory::where('code', 'POA')->update(['approval_chain_id' => $defaultChain->id]);
        FormCategory::where('code', 'UAA')->update(['approval_chain_id' => $defaultChain->id]);
        FormCategory::where('code', 'UOA')->update(['approval_chain_id' => $defaultChain->id]);
        FormCategory::where('code', 'PR')->update(['approval_chain_id'  => $financeChain->id]);
        $this->command->info('✓ Approval chains & categories seeded');

        // ── 6. Vendors ─────────────────────────────────────────
        $vendor1 = Vendor::firstOrCreate(['email' => 'info@technepal.com.np'], [
            'name' => 'Tech Nepal Solutions Pvt. Ltd.', 'category' => 'IT & Electronics',
            'company_type' => 'pvt_ltd', 'pan_vat_number' => '600123456',
            'contact_person' => 'Suresh Maharjan', 'mobile_number' => '9841123456',
            'address' => 'New Baneshwor, Kathmandu', 'bank_name' => 'Nabil Bank',
            'bank_account_name' => 'Tech Nepal Solutions', 'bank_account_number' => '12345678901234',
            'is_active' => true, 'created_by' => $finance1->id,
        ]);
        $vendor2 = Vendor::firstOrCreate(['email' => 'orders@officezone.com.np'], [
            'name' => 'Office Zone Stationery & Supplies', 'category' => 'Office Supplies',
            'company_type' => 'partnership', 'pan_vat_number' => '601234567',
            'contact_person' => 'Meena Pradhan', 'mobile_number' => '9851234567',
            'address' => 'Putalisadak, Kathmandu', 'bank_name' => 'Nepal Investment Mega Bank',
            'bank_account_name' => 'Office Zone', 'bank_account_number' => '23456789012345',
            'is_active' => true, 'created_by' => $finance1->id,
        ]);
        $this->command->info('✓ Vendors seeded');

        // ── 7. Activity Forms ──────────────────────────────────
        $catPAA = FormCategory::where('code', 'PAA')->first();
        $catPOA = FormCategory::where('code', 'POA')->first();
        $catPR  = FormCategory::where('code', 'PR')->first();

        // AF1: Approved (will feed into PR)
        $af1 = ActivityForm::firstOrCreate(['form_number' => 'AF-2081-0001'], [
            'category_id' => $catPAA->id, 'creator_id' => $general1->id,
            'department_id' => $depts['IT'], 'activity_name' => 'Annual IT Equipment Procurement',
            'deadline_date' => now()->addMonths(2)->toDateString(),
            'remarks' => 'Laptops and peripherals for new faculty members',
            'status' => 'approved', 'approval_chain_id' => $defaultChain->id,
            'verifier_id' => $verifier1->id, 'verifier_decision' => 'approved',
            'verified_at' => now()->subDays(5),
            'approver_id' => $approver1->id, 'approver_decision' => 'approved',
            'approved_at' => now()->subDays(3), 'total_estimated_amount' => 485000.00,
        ]);
        $this->seedItems($af1, ActivityForm::class, [
            ['item_name' => 'Laptop 14" Core i7 16GB RAM', 'quantity' => 5, 'unit' => 'pcs', 'rate' => 85000],
            ['item_name' => 'Wireless Mouse & Keyboard',   'quantity' => 5, 'unit' => 'set', 'rate' => 3500],
            ['item_name' => 'External Hard Drive 1TB',     'quantity' => 5, 'unit' => 'pcs', 'rate' => 5500],
            ['item_name' => 'HDMI Monitor 24"',            'quantity' => 3, 'unit' => 'pcs', 'rate' => 22000],
        ]);

        // AF2: Pending Verification
        $af2 = ActivityForm::firstOrCreate(['form_number' => 'AF-2081-0002'], [
            'category_id' => $catPOA->id, 'creator_id' => $general2->id,
            'department_id' => $depts->get('MKT', $depts->get('IT')), 'activity_name' => 'Q4 Marketing Campaign Materials',
            'deadline_date' => now()->addMonth()->toDateString(),
            'remarks' => 'Brochures, banners for admission drive',
            'status' => 'pending_verification', 'approval_chain_id' => $defaultChain->id,
            'total_estimated_amount' => 75000.00,
        ]);
        $this->seedItems($af2, ActivityForm::class, [
            ['item_name' => 'A4 Brochure Printing (1000 pcs)', 'quantity' => 1000, 'unit' => 'pcs', 'rate' => 25],
            ['item_name' => 'Vinyl Banner 3x6 ft',             'quantity' => 10,   'unit' => 'pcs', 'rate' => 3500],
            ['item_name' => 'Roll-up Standee',                 'quantity' => 5,    'unit' => 'pcs', 'rate' => 4000],
        ]);

        // AF3: Pending Approval (verified)
        $af3 = ActivityForm::firstOrCreate(['form_number' => 'AF-2081-0003'], [
            'category_id' => $catPAA->id, 'creator_id' => $general1->id,
            'department_id' => $depts['ACAD'], 'activity_name' => 'Guest Lecture Series — Spring Semester',
            'deadline_date' => now()->addWeeks(3)->toDateString(),
            'remarks' => 'Honorarium and logistics for 4 industry guest lecturers',
            'status' => 'pending_approval', 'approval_chain_id' => $defaultChain->id,
            'verifier_id' => $verifier1->id, 'verifier_decision' => 'approved',
            'verified_at' => now()->subDays(1), 'total_estimated_amount' => 40000.00,
        ]);
        $this->seedItems($af3, ActivityForm::class, [
            ['item_name' => 'Speaker Honorarium', 'quantity' => 4,  'unit' => 'session', 'rate' => 8000],
            ['item_name' => 'Refreshments',       'quantity' => 80, 'unit' => 'person',  'rate' => 250],
        ]);

        // AF4: Draft
        $af4 = ActivityForm::firstOrCreate(['form_number' => 'AF-2081-0004'], [
            'category_id' => FormCategory::where('code','UAA')->value('id'),
            'creator_id' => $general2->id, 'department_id' => $depts['SA'],
            'activity_name' => 'Emergency Computer Lab Repair',
            'unplanned_reason' => 'Three lab computers failed due to power surge',
            'deadline_date' => now()->addWeeks(1)->toDateString(),
            'status' => 'draft', 'total_estimated_amount' => 32000.00,
        ]);
        $this->seedItems($af4, ActivityForm::class, [
            ['item_name' => 'Motherboard Replacement', 'quantity' => 2, 'unit' => 'pcs', 'rate' => 12000],
            ['item_name' => 'RAM DDR4 8GB',            'quantity' => 4, 'unit' => 'pcs', 'rate' => 2000],
        ]);
        $this->command->info('✓ Activity Forms seeded (4 forms)');

        // ── 8. Purchase Request ────────────────────────────────
        $pr1 = PurchaseRequest::firstOrCreate(['form_number' => 'PR-2081-0001'], [
            'title' => 'IT Equipment for New Faculty',
            'description' => 'Purchase of laptops and peripherals as per AF-2081-0001',
            'creator_id' => $general1->id, 'department_id' => $depts['IT'],
            'activity_form_id' => $af1->id, 'activity_name' => $af1->activity_name,
            'deadline_date' => now()->addMonth()->toDateString(),
            'remarks' => 'Urgently needed before new semester',
            'status' => 'approved', 'approval_chain_id' => $financeChain->id,
            'verifier_id' => $verifier1->id, 'verifier_decision' => 'approved',
            'verified_at' => now()->subDays(2),
            'approver_id' => $approver1->id, 'approver_decision' => 'approved',
            'approved_at' => now()->subDay(), 'total_amount' => 485000.00,
        ]);
        $prItems = [
            ['item_name' => 'Laptop 14" Core i7 16GB RAM', 'quantity' => 5, 'unit' => 'pcs', 'rate' => 85000],
            ['item_name' => 'Wireless Mouse & Keyboard',   'quantity' => 5, 'unit' => 'set', 'rate' => 3500],
            ['item_name' => 'External Hard Drive 1TB',     'quantity' => 5, 'unit' => 'pcs', 'rate' => 5500],
            ['item_name' => 'HDMI Monitor 24"',            'quantity' => 3, 'unit' => 'pcs', 'rate' => 22000],
        ];
        $this->seedItems($pr1, PurchaseRequest::class, $prItems);
        $this->command->info('✓ Purchase Request seeded');

        // ── 9. RFQ & Quotations ────────────────────────────────
        $rfq1 = Rfq::firstOrCreate(['rfq_number' => 'RFQ-2081-0001'], [
            'title' => 'IT Equipment Supply — Faculty Batch 2081',
            'purchase_request_id' => $pr1->id, 'created_by' => $finance1->id,
            'deadline' => now()->addDays(7)->toDateString(),
            'notes' => 'Please quote best price. Delivery within 2 weeks required.',
            'status' => 'accepted',
        ]);
        // Vendor 1 quote (accepted)
        $quote1 = RfqQuote::firstOrCreate(['rfq_id' => $rfq1->id, 'vendor_id' => $vendor1->id], [
            'vendor_token' => Str::random(64), 'token_expires_at' => now()->addDays(30),
            'token_used' => true, 'submitted_at' => now()->subDays(3), 'total_quoted' => 480000,
            'valid_until' => now()->addDays(30)->toDateString(),
            'delivery_timeline' => '10 working days',
            'notes' => 'All items in stock. Delivery guaranteed within 10 working days.',
            'status' => 'accepted', 'accepted_by' => $finance1->id, 'accepted_at' => now()->subDay(),
        ]);
        // Vendor 2 quote (submitted, not accepted)
        RfqQuote::firstOrCreate(['rfq_id' => $rfq1->id, 'vendor_id' => $vendor2->id], [
            'vendor_token' => Str::random(64), 'token_expires_at' => now()->addDays(30),
            'token_used' => true, 'submitted_at' => now()->subDays(2), 'total_quoted' => 510000,
            'valid_until' => now()->addDays(30)->toDateString(),
            'delivery_timeline' => '15 working days',
            'notes' => 'Items available. Delivery in 15 working days.',
            'status' => 'submitted',
        ]);
        $prLineItems = $pr1->lineItems()->get();
        foreach ($prLineItems as $li) {
            $unitRate = round($li->rate * 0.98, 2);
            RfqQuoteItem::firstOrCreate(['rfq_quote_id' => $quote1->id, 'line_item_id' => $li->id], [
                'unit_rate' => $unitRate,
                'total'     => round($unitRate * $li->quantity, 2),
                'item_notes' => 'HP brand. In stock.',
            ]);
        }
        $this->command->info('✓ RFQ & Quotations seeded');

        // ── 10. Purchase Order ─────────────────────────────────
        $po1 = PurchaseOrder::firstOrCreate(['po_number' => 'PO-2081-0001'], [
            'purchase_request_id' => $pr1->id, 'rfq_quote_id' => $quote1->id,
            'vendor_id' => $vendor1->id, 'generated_by' => $finance1->id,
            'delivery_address' => 'Kathford International College, Balkumari, Lalitpur',
            'expected_delivery_date' => now()->addDays(10)->toDateString(),
            'terms_and_conditions' => "1. Delivery within 10 working days.\n2. Invoice must match PO.\n3. Defective items returned at vendor's cost.",
            'subtotal' => 480000, 'tax_amount' => 0, 'total_amount' => 480000,
            'status' => 'sent_to_vendor', 'sent_to_vendor_at' => now()->subDays(1),
            'authorised_by_name' => $approver1->name, 'authorised_at' => now()->subDays(1),
        ]);
        $this->command->info('✓ Purchase Order seeded');

        // ── 11. GRN ────────────────────────────────────────────
        $grn1 = GoodsReceivedNote::firstOrCreate(['grn_number' => 'GRN-2081-0001'], [
            'purchase_order_id' => $po1->id, 'received_by_user_id' => $general1->id,
            'received_by_name' => 'Priya Sharma', 'received_date' => now()->toDateString(),
            'condition_notes' => 'All items received in good condition. Boxes intact.',
            'is_partial' => false, 'status' => 'confirmed',
            'confirmed_by' => $finance1->id, 'confirmed_at' => now(),
        ]);
        foreach ($prLineItems as $li) {
            GoodsReceivedItem::firstOrCreate(['grn_id' => $grn1->id, 'line_item_id' => $li->id], [
                'ordered_quantity' => $li->quantity,
                'received_quantity' => $li->quantity,
                'item_condition_note' => 'Good condition',
            ]);
        }
        $po1->update(['status' => 'fully_received']);
        $this->command->info('✓ GRN seeded');

        // ── 12. Payment ─────────────────────────────────────────
        Payment::firstOrCreate(['payment_number' => 'PAY-2081-0001'], [
            'purchase_order_id' => $po1->id, 'vendor_id' => $vendor1->id,
            'created_by' => $finance1->id, 'po_total' => 480000,
            'amount_due' => 480000, 'amount_paid' => 0,
            'scheduled_date' => now()->addDays(7)->toDateString(),
            'payment_method' => 'bank_transfer', 'bank_name' => $vendor1->bank_name,
            'bank_account_number' => $vendor1->bank_account_number,
            'status' => 'scheduled',
            'notes' => 'Bank transfer to Nabil Bank as per PO-2081-0001',
        ]);
        $this->command->info('✓ Payment seeded');

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->newLine();
        $this->command->info('══════════════════════════════════════════════');
        $this->command->info('  ✓ Sample data seeded successfully!');
        $this->command->info('');
        $this->command->info('  Test accounts (Google OAuth login):');
        $this->command->info('  admin@kathford.edu.np         — Super Admin');
        $this->command->info('  ram.krishna@kathford.edu.np   — Verifier');
        $this->command->info('  sita.poudel@kathford.edu.np   — Verifier');
        $this->command->info('  binod.joshi@kathford.edu.np   — Approver');
        $this->command->info('  finance@kathford.edu.np        — Finance');
        $this->command->info('  priya.sharma@kathford.edu.np   — General');
        $this->command->info('  deepak.thapa@kathford.edu.np   — General');
        $this->command->info('══════════════════════════════════════════════');
    }

    private function seedItems(object $model, string $type, array $items): void
    {
        if ($model->lineItems()->count() > 0) return;
        foreach ($items as $item) {
            FormLineItem::create([
                'itemable_type' => $type,
                'itemable_id'   => $model->id,
                'item_name'     => $item['item_name'],
                'quantity'      => $item['quantity'],
                'unit'          => $item['unit'] ?? null,
                'rate'          => $item['rate'],
            ]);
        }
    }
}
