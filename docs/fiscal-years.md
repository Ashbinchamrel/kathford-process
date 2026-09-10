# Fiscal year controls

Organisation Profile contains fiscal-year labels, start/end dates, and the active-year control. Labels may use AD or BS; the date inputs explicitly use Gregorian dates. Date ranges cannot overlap. Existing budget labels are imported without guessing their calendar dates.

The top-bar selector changes the current user's working year. Selecting the active year restores the organisation default, so future active-year changes take effect automatically. Historical selections remain explicitly selected until the user switches back. Switching returns to the dashboard to avoid retaining record IDs and filters from a different year.

Budgets, activities, purchase requests, RFQs/quotes, purchase orders, receipts, supplier bills, checklists, payment schedules, and payment authorisations carry a fiscal_year_id. Eloquent filtering applies to lists, totals, related selectors, route binding, and exports. Existing permission and ownership checks remain in force. Shared configuration (users, departments, vendors, payees, accounts, categories and approval chains) remains available across years. Audit and notification history remains available across years.

Only the active year permits transaction changes. Historical views remain readable; the administrator can activate another configured year when work is needed there. Cross-year source links and attempts to move existing records are rejected. Forms carry their rendering year's ID so a session-year change in another tab cannot silently file an entry in the wrong year. Budget CSV rows must match the active fiscal-year label exactly.

Migration preserves existing budget labels and follows source relationships to associate transactions. Records without a reliable source year are retained in “Unassigned historical data”; they are not guessed into the active year. Review these records before deciding their year. Reference numbers retain their existing numbering convention and count across years to avoid reusing references.

Validation: `vendor/bin/phpunit tests/FiscalYearTest.php tests/RecordVisibilityTest.php`, PHP lint and Blade compilation. A local database backup was created before applying the migration. Read-only application smoke checks cover Organisation Profile, dashboard, budgets, activity list/create, RFQs, purchase orders, payments, payment authorisations, and checklists.
