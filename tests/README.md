# Tests for the permanent Account-number model
Run tests/account-model.php with PHP 8.2 and the isolated eddt_srms_rebuilt_test database.
Initialize it with tools/migrate.php and tools/demo.php using SRMS_DB_NAME and a separate SRMS_STORAGE_PATH.
The suite wraps its fixture writes in a transaction and rolls them back.
Older tests (run.php, dashboard-map.php, billing-roll.php, permanent-numbers.php and http.php) target the previous UUID/separate-register model and are retained as historical coverage references, not current acceptance commands.
Current browser/HTTP verification results and rollback instructions: docs/REBUILT-ACCOUNT-MODEL.md.

Current on-demand correspondence coverage: tests/correspondence-on-demand.php and tests/correspondence-http.php. See docs/ON-DEMAND-CORRESPONDENCE.md for fixture setup, validation results and rendering/backup behavior.
