# User display and record access

- The permissions saved under Users control module links and action forms. Server route checks enforce the same permissions when a URL is entered directly.
- Staff transaction lists show entries created by the signed-in user. Assigned verification/approval authorities with the corresponding permission may also access submitted records in their approval chain.
- Super Admin retains access to all records and configuration.
- Automatically generated procurement checklists follow purchase-order ownership.
- Direct record URLs and attachment downloads check record access. Activity attachment IDs must belong to the activity in the URL.
- Activity creators can read the current pending authority and the recorded decisions and times for previous authorities. The history retains earlier approval attempts.
- Title / Subject can be filtered by activity title or fiscal year on create and edit screens. The current selection stays available during a search.

Verification: `vendor/bin/phpunit tests/RecordVisibilityTest.php`; `php artisan view:cache`; PHP syntax checks on application files and compiled views.
