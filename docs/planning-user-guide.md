# Using Planning

Planning is available at `/planning/overview` in the existing application.

1. Open **Planning → Setup** and add college periods or programme/batch semesters. Date fields accept AD and BS; the paired field converts locally. Supported BS years: 2000–2090.
2. In **Settings → Users → Permissions**, grant Open Planning plus the relevant view/create/submit/verify/approve permissions. Existing users and departments are reused. Each document selects its own approval chain. Chain members need Planning access and the matching decision permission.
3. Prepare the Board Strategic Plan, then linked CMT Goals, Department Goals and Semester Plans. Each new CMT/Department goal document requires the correct approved parent and each goal links to a parent row. Standalone operational tasks can remain unlinked. Add activities, owners, dates, Definition of Done, budget requirements and support departments.
4. Final plan approval creates tasks and support requests. Weekly/monthly activities with a start and end generate occurrences, limited to 104 per activity. Nothing is ordered or paid by this approval.
5. Finance creates a Budget Proposal for the owning department and current fiscal year, selects approved funded activities and submits it through its separate chain. Finance staff preparing another department's budget require the all-departments Planning permission.
6. Final budget approval publishes allocations into existing Department Budgets. Use **Create Activity Form** on an approved allocation; the form preselects the budget and department. The existing Activity Form and downstream approvals still apply.
7. In **Team work**, create tasks/subtasks, assign department members, create/start/complete sprints, and move work through To Do, In Progress, Review and Done. The reviewer (or task creator where no reviewer is set) accepts completion from Review with evidence. Unfinished sprint tasks return to the backlog.
8. **My calendar** shows your assigned tasks by month. Private personal tasks are owner-only, including against other administrators. The board's task editor can change a task deadline without changing an approved plan date.
9. Approved documents are read-only. Use **Create amendment**. Existing activities and allocations must be retained to preserve links. Approved amendments supersede earlier revisions. A revised allocation cannot fall below current commitments.
10. Authorized CMT/Department goal owners and creators can record actual values and evidence with the matching report permission. Task completion and KR achievement are separate. Board Strategic Plan targets change through semester reviews and approval.

The import preview accepts the supplied six-column Planning CSV template or an XLSX sheet with the same header names: title, description, definition_of_done, programme, batch. It adds draft rows only, up to 200 total per document. Complete dates, owners, targets and funding references before submission. The original multi-sheet workbooks and Jira history have not been imported; their fields need mapping and placeholder/date review first.

## Overview and membership

- **Settings → Users → Edit**: tick **Board member** and/or **CMT member**. These checkboxes are also available when adding a user.
- **Settings → Departments → Edit**: assign the Department Head. Planning Setup does not duplicate this setting.
- Users with Open Planning access receive their dashboard automatically in **Planning → Overview**: Board members see approved CMT goal reports; CMT members see approved Department goal reports; department heads see their assigned department's goals and non-private task totals.
- People with multiple memberships can select among their dashboards inside Overview. The default order is Board, CMT, then Department. Others receive the personal Planning overview. Super Admin status alone does not imply Board/CMT membership or department-head assignment.
- Dashboard access follows membership directly; separate dashboard permission checkboxes are unnecessary. Membership does not grant create, edit, submit, verify, approve, or report permissions.
- Existing Board/CMT selections were transferred to user records. Revoking membership removes dashboard access, including old dashboard URLs. Approval defaults and department-head assignments are preserved.
- **Planning → Setup** contains default approval chains and Planning periods.

## Strategic Plan matrix and reviews

Strategic Plans use Strategic Priority, Goal, Strategy and one target-KPI column per semester. The long-term plan has its own start/end dates, independent of the active budget fiscal year. Four semesters are offered initially; up to twelve are supported. Semester dates cannot overlap or fall outside the plan dates. Multiline KPI targets and blank target cells are supported.

**Start from the supplied 2026–2028 strategy** opens the provided eight priorities and 36 rows as an unsaved draft. Review the content and choose an approval chain before saving. Opening the template does not import or approve a live record.

Board editors require Strategic Plan view and edit permissions; creating plans and starting reviews have separate permissions. Submission also requires the submit permission. The existing Super Admin permission override remains for plan administration.

Use **Start semester review** on the current approved plan, select the semester and record the reason. This creates a draft revision. The approved version remains current until final approval of the revision, then becomes superseded. Existing CMT goals retain the exact strategy revision and targets originally used. New CMT goals must link to the current approved strategy. The approval history and links to earlier revisions remain available.

## Release boundaries

This release provides the core approval, funding and task workflow. Team access is department-based; arbitrary multi-department workspace membership, sophisticated KR aggregation/burndown reports, attachment management, and Jira history migration are not included. The calendar currently has a month view. Academic course/allocation details are optional fields within activities rather than a full faculty workload subsystem. These differences from the broader architecture design should be considered before retiring Jira.

## Verification and recovery

- Active Super Admin users can submit, verify and approve Planning documents for testing, including steps assigned to another user. Submission may proceed for testing even when a configured chain member is inactive; active assigned members no longer need separate Planning decision permissions. Each configured step still requires a separate decision. Approval history and the audit log identify the Super Admin and the assigned member or submission chain exceptions. A valid document and an active chain containing an approver are still required. Ordinary users must be active and assigned to the current decision step.
- `php tests/planning_superadmin_smoke.php`: runs the existing 100 checks plus 14 checks covering Super Admin submission, sequential verification/approval, audit history, unchanged ordinary-user restrictions and validation.

- `php tests/planning_overview_membership_smoke.php`: runs 100 isolated checks including the workflow and governance checks, User checkbox saving, membership transfer, dashboard selection, access denial and settings.
- `php tests/planning_workflow_smoke.php`: isolated in-memory database; covers approvals, publication, duplicates, revisions, privacy, dates and view rendering.
- Existing procurement rollback smoke checks and permission/fiscal-year/unit tests were rerun.
- Code and database baseline backups are under `storage/app/planning-backup/`. Database backup permissions are restricted to the owner. Restore only as an explicit recovery operation; restoring the old database would discard newer work.
- Calendar data provenance and MIT license are retained in `resources/data/`.

## CMT goal table and chain-based access

CMT Goals now use one expandable row per semester goal. Select the approved Strategic Plan and semester once. Each row shows its inherited strategic target and has an accountable owner and due date. Expand it for multiple key results (each with a metric, baseline and target), Definition of Done, deliverables, monthly milestones, proposed tasks, supporting owners and additional strategic links. Saved goals reopen collapsed. Each result is stored separately, can receive its own progress check-in, and can be selected as the source of a Department Goal. No progress is inferred from task completion.

The optional April–September 2026 reference loads eight draft goals from the supplied CMT document. It does not save or approve them. Select links and owners before submitting. The internship target intentionally remains blank until the preparer resolves the document's count-versus-percentage discrepancy; reference notes also identify mentoring differences. Monthly reference milestones are retained with the semester theme. Proposed tasks are planning notes, not automatically launched work.

User Permissions is a table of View, Prepare/edit, Submit, Progress and Other actions/scope. Verification and approval toggles are omitted for Planning, Activity Forms, Purchase Orders and Payment Authorisations. Active assigned members receive document access and must act in the existing step order. Planning reviewers may also read the document's parent evidence. General queue access still requires its explicit View permission. Awarding quotes, issuing orders, scheduling/marking payments paid, exports and setup remain explicit permissions. Membership does not grant these actions. Existing Super Admin testing overrides remain available. Legacy decision flags are retained when saving the matrix for compatibility, but no longer confer decision authority independently of assignment.

Verification: `php tests/cmt_chain_access_smoke.php` runs 139 isolated checks, including the existing Planning suite plus multi-result saving, strategic-link validation, individual check-ins, chain decisions and unrelated-record denial across the four workflows. The Super Admin suite and existing permission/fiscal-year tests also pass. No live document or user permissions were changed by testing.

### CMT form refinement

Semester selection now comes directly from the selected Strategic Plan's Semester timeline, with AD and BS dates. Saving resolves the selected timeline entry to a college planning period transactionally; no separate period setup is required. CMT approval chains are configured in Planning Setup → Form settings and enforced when saving and submitting. A submitted document retains its approval-step snapshot.

Choose Strategic priority first, then Strategic Goal; the latter is filtered to that priority. The supplied-document starter and additional strategic links are no longer shown. Supporting owners use individual checkboxes. Only checked, active supporting owners receive the CMT support-assignment in-app notification on final approval, once per document regardless of key-result count. Normal approval-chain workflow notifications continue separately. Supporting owners can open their approved CMT document and its strategic evidence, without gaining edit or approval authority.

The latest CMT integration script includes 146 checks, including timeline period resolution, server-enforced chain settings and supporting-owner notification recipients. Browser controls and local page rendering were also verified.
