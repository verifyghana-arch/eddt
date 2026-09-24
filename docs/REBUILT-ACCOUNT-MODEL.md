# Rebuilt Account-number model

The local app uses eddt_srms_rebuilt_demo. The original eddt_srms database is unchanged.

## Identity
One physical parcel_accounts row represents one parcel/property. account_number CHAR(12) is its primary key. Format: EDDT + two division digits + three block digits + three parcel digits. Generated division_code, block_code, and parcel_code preserve leading zeroes.
Issued numbers cannot be updated or deleted. Archive records instead. Ownership transfers preserve the Account number, financial history, credit and debt.
Payments, bills, assessments, ownership links, documents, photos and correspondence have direct account_number foreign keys. Individual transactions and documents retain their own unique IDs because a parcel can have many of them.
The parcels, properties and accounts names are compatibility SQL views of the same physical record; they are not separate registrations. Empty legacy tables are retained by the migration for compatibility with upgrades.

## Sample workspace
12 fictional parcels EDDT99001001 through EDDT99001012, 12 fictional owners, 16 annual bills, partial/full payments, a credit, exemptions and unbilled parcels. All financial and ownership data is illustrative. Photographs reuse one image from the supplied sample correspondence and are clearly captioned as illustrative.
Login credentials: var/rebuilt-demo-access.txt (private, not served by Apache). Justice uses +233543914765. All temporary passwords require a change at first login.
Documents: var/rebuilt-storage. The old document directory remains intact.
The app displays a Sample workspace banner.

## Recreate an isolated sample database
In PowerShell, choose a NEW database name ending in _demo or _test:
    $env:SRMS_DB_NAME='eddt_srms_new_demo'
    $env:SRMS_STORAGE_PATH='C:/xampp/htdocs/srms/var/new-demo-storage'
    & C:/xampp/php/php.exe tools/migrate.php
    & C:/xampp/php/php.exe tools/demo.php
Migration never resets existing tables. Seeding refuses a nonempty database.

## Validation
46 model/integration checks and 38 HTTP checks passed before cutover, including actual primary key, immutable numbers, oldest-bill reconciliation, overpayments, credit application, reversals, ownership transfers, direct owner/document isolation, import resume/repeat/conflicts, exports and all four roles.
Headless Chrome verified four dashboard charts, mobile fit, 12 rendered parcels, all five payment colors, map search/filter, popup owner/photo, and 18 loaded map tiles. Screenshot inspection confirmed genuine map imagery without blocked tiles.
Historical UUID-based tests describe the previous model; use the rebuilt model suite for the current schema.

## Rollback
Restore the source-before-rebuild ZIP in var/backups to a separate directory first for review. Its config points to eddt_srms and var/storage. Restore that source/config together to return to the old database. Do not point old code at the rebuilt database or vice versa.
No old data was deleted. No new sample ownership or balances were inserted into eddt_srms.
Current validation commands:
    & C:/xampp/php/php.exe tests/account-model.php

This suite uses eddt_srms_rebuilt_test, with transaction rollback. Fresh installation and fixture seeding were also verified in that database. After live cutover, all 38 HTTP checks passed again. Document hashes matched for all 15 files; the private credentials URL returned HTTP 403. Final counts: 12 parcels, 12 owners, 16 bills, 5 payments, 12 photos and 3 generated letters/receipts. All four users require a first-login password change.
Future demo seeds save credentials separately as var/<database-name>-access.txt. Current workspace credentials remain var/rebuilt-demo-access.txt.
The mobile map popup width is constrained to the map container and was rechecked in Chrome after correction.
