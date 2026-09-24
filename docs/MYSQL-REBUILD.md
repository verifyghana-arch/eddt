# MySQL rebuild

The application now has a property-centred workflow workspace and interactive dashboard. MySQL/MariaDB is the only supported database; no SQL Server driver or migration stage is required.

## Fresh installation

Install Composer dependencies as documented in README. Create a NEW database:

    C:\xampp\php\php.exe tools/install-mysql.php eddt_production

The installer refuses every existing database name. It loads database/mysql/schema.json, creates the roles and settings, and never reads or copies existing property/owner data. Point configuration at the new database and a separate storage directory, then use the existing secure Administrator creation command. For fictional samples, choose a name ending _test or _demo and run tools/demo.php with SRMS_DB_NAME and SRMS_STORAGE_PATH set to that database and directory.

Do not run the old migration chain to create a fresh database. tools/migrate.php remains for upgrades of historical installations. The clean baseline omits legacy migration tables and columns. Compatibility views `properties`, `parcels`, and `accounts` all reference the single `parcel_accounts` table; they do not represent separate entities. Account numbers are immutable physical keys. Internal transaction/document IDs identify individual records attached to an Account number.

## Use

Open **Workflows** for queues, or **Open property workflow** from a property. Registering a new property opens its workflow; assigning an owner returns to the workflow. A registered property needs an Administrator-assigned owner before assessment in the guided flow. Billing Officers submit assessments; Administrators approve; staff issue bills and follow the linked searchable payment screen. Existing bulk billing, imports, financial corrections and historical record screens remain available.

The dashboard offers a collection-year filter, bar/line switch, billing-series toggles, keyboard-focus and hover values, month/method/year report links, and payment-status map links. The collection-year selector affects collection trends only; property balances and payment status continue to include all bills. Annual billing bars show the latest five billed years. Tables remain available when JavaScript is disabled.

## Validation and rollout

A new isolated database `eddt_workflow_v2_test` was installed and seeded with fictional records. Current live data remains in its existing database. New charts and workflow screens use the configured database; there has been no live database replacement.

Run `php tests/mysql-workflows.php` for clean-schema workflow and chart/report reconciliation checks. Existing account-model, owner/export, and batch tests continue to use the historical isolated fixtures. A large production import, production data reconciliation and cutover have not been performed by this rebuild. Configure and verify backups before a future cutover.

Validation completed: 26 clean-schema workflow checks, 46 account-model checks, 11 owner/export checks, 25 export/batch checks, and 13 rebuilt application checks passed (121 total). The rebuild checks cover OSM/Esri/orthophoto configuration, unsafe tile URL rejection, navigation by role, paginated repositories, and owner isolation. Chrome browser checks cover interactive charts, map layers, workflow screens, appearance controls, and mobile viewport fit. Monetary calculations and property financial history remain unchanged except that assessment, approval and first bill issuance require a current owner.

The clean-schema installer was exercised against a new database. Large-batch performance, a production cutover and migration of the existing real property dataset to the new baseline remain unperformed. Historical upgrade tables remain only in the existing databases, not in new installations.
