# Activity Forms document implementation

Source: /Users/ashbinkumarchamrel/Downloads/Activity Forms.docx (text and 12 screenshots inspected).

- [x] Modify returns activity to creator for full permission-controlled editing, then resubmission; Approve / Modify / Reject labels.
- [x] Replace obsolete GRN UI and routes with Checklist; show approval channel and remaining authorities instead of procurement stepper.
- [x] Remove unwanted Activity Forms banner subtitle.
- [x] Final activity approval automatically creates an RFQ preparation entry, idempotently.
- [x] Standalone RFQs select a budget title without requiring an activity; automatically handed-off activities retain their reference.
- [x] RFQ item row: description, quantity, unit, vendor, then remarks.
- [x] Item-level Quotation Not Required: only approved-activity items can route to Payment Schedule; keep approved source amounts and prevent duplicate routing.
- [x] Add additional vendors to an existing RFQ for quote requests, without entering quote amounts; comparison includes their responses.
- [x] Approved vendor item/rate catalogue with validity/review dates, integrated into RFQ item sourcing and normal PO approval.
- [x] Admin-managed goods/service checklist questions with historical snapshots.
- [x] Return goods/invoices with reason, vendor-visible notification, payment safeguards.
- [x] Permission-aware personal dashboard widgets users may add/remove; correct pending actions.
- [x] Users cannot switch fiscal year; administrator controls active year in Organisation Profile.
- [x] Consistent shared UI styles; process/code audit and regression checks.

Prior task completed before document arrived: creator name added to activity approval-chain card; existing stored Full Name field used for all authorities; verifier membership no longer reveals completed history unless that user actually acted. Local read-only test showed only PAA-2026-0010 for verifier. tests/RecordVisibilityTest.php passes (3 tests, 7 assertions). Actual full names still need to be supplied in Users because saved names are General/verefy/Approve/admin.


## Using the new setup

- Organisation Profile → Fiscal year setup: activate the working year. Ordinary users always use this active year; only Super Admin may review a historical year.
- Procurement Setup → RFQ preparation assignment: select the responsible user for future approved activities. No person was selected automatically. Without an assignment, the original creator (subject to RFQ permissions) and Super Admin can open the RFQ.
- Procurement Setup → Approved vendor rates: enter vendor, item, rate, valid dates and optional review date. Current rates are available during RFQ preparation and create priced comparison entries. Expired/inactive rates cannot be selected. Existing prices remain unchanged when the catalogue is revised.
- Procurement Setup → Checklist questions: manage separate goods/service controls. New checklists capture the questions; historical answers and snapshots remain unchanged.
- Dashboard → Customize reports: select permitted reports or remove them. Record ownership and assignment filters still apply.
- Returns retain the original invoice and cancel unpaid, unauthorised schedules. Paid or batched payments block returns. Vendors receive a portal notice and queued email; corrected invoices use a revised reference.

## Verification

- 14 PHPUnit tests, 40 assertions: record visibility, role-filtered authorities, fiscal-year scoping, and stale-session protection.
- Local workflow regression script passed: modification/resubmission, every approval layer, one RFQ handoff, approved-source direct payment, duplicate prevention, standalone RFQ, valid/expired rates, checklist snapshots, parent/child payment return safeguards, corrected invoice quantities and additional-vendor comparison identity.
- Integration test writes were rolled back; mail and queues were faked. No real vendor messages were sent during testing.
- Updated PHP and compiled Blade files passed syntax checks. Core pages and detail/comparison pages returned HTTP 200. Browser previews verified title search, the vendor picker and historical checklist answers.
- Current verifier read-only page check showed only PAA-2026-0010.
- Database backups are in storage/app/backups before the procurement migrations. Historical GRN tables are retained for data preservation; operational GRN routes and permission controls are removed.

The audit covered the changed workflows and their access paths; it is not a formal security certification. Shared styles standardize form controls, typography, tables, focus states and common buttons across application and supplier layouts.
