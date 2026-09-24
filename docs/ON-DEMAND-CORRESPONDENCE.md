# Correspondence, map controls and galleries

Implemented and checked locally on 23 September 2026, PHP 8.2/XAMPP MariaDB.

## Everyday use

- Open a property. Its correspondence is beside the main information on wide screens and below it on mobile. Each page contains ten records, independently of other lists.
- Administrators and Billing Officers save demand notices, invitations and receipts from the Correspondence section. Choose whether the record is visible to currently authorized owners.
- Preview, Print and Download render the saved record. Print opens the PDF viewer; use its print control. Every output identifies the requesting user's full name and Africa/Accra timestamp. The issue date is separate.
- Print counts mean successful server-side print requests, not confirmation from a physical printer. Reload the record to see updated counts. Repeated requests carrying the same action token count once. Preview and download have separate events.
- Payment rows link directly to saved receipts. Reversed payments remain in history, and regenerated receipts carry a reversal warning without changing their original financial snapshot.
- Batch creation saves correspondence IDs. Interrupted generation resumes unfinished items; completed items are not recreated. Completed batches offer combined Preview, Print and Download. Auditors may read and print; only Administrators and Billing Officers create or resume.
- The map selector opens on focus or click. Escape, clicking elsewhere or choosing a base map closes it. Parcel overlay checkboxes are independent. The base map choice is remembered.
- Property thumbnails open an enlarged viewer with full-image containment, caption, capture date and primary status. Use Previous/Next or arrow keys, and Escape to close. Focus returns to the thumbnail. Popup images fill their frames without overflowing.

## Storage and historical integrity

New generated PDFs are not stored in the document filesystem. Correspondence saves its immutable JSON snapshot and SHA-256, Account number, reference, issue date, creator, visibility, bill/payment links and exact template version. Version 3 also records template/branding hashes and the retained photograph ID and hash.

Retain every version in app/views/letters and app/assets/letters. Do not edit an issued template version or overwrite its assets; introduce a new version instead. Keep original uploaded files and all photograph versions in protected storage.

Rendering never recalculates historical values from present balances or ownership. If a legacy snapshot cannot be reproduced with verified historical resources, the preserved original PDF receives an additional attribution footer. Originals are never overwritten or deleted. A record without an original and without its required snapshot/resources fails explicitly.

All PDF work is in memory. Trust exports place generated PDFs directly inside the requested ZIP along with records, originals, template assets and SHA-256 manifests. Operational backups contain the database, protected originals and versioned templates/assets. Restore places retained application resources under var/restores/TARGET/application; deploy those exact versions with the restored application. Never replace a version with a file whose hash differs from the restored snapshot.

## Database upgrade

Before deployment, run an operational backup, then tools/migrate.php. Migrations 009_on_demand_correspondence and 010_correspondence_links add visibility, snapshot hashes, payment links, print events, batch correspondence references and foreign keys. Existing document references remain. Legacy receipt payment links and batch references are backfilled where identifiable.

Both database/mysql/schema.json (CLI installer) and database/mysql/eddt_srms_mysql.sql (manual import into a NEW empty database) include these changes. Do not import the fresh schema over an existing application database.

The pre-upgrade local backup is var/backups/srms-20260923-160535-1170b7ce.zip. Restrict access: operational backups contain credentials and private records.

## Verification performed

- 26 MySQL workflow checks; 25 export/batch checks; 20 on-demand correspondence checks; 13 application repository/access checks; 11 HTTP correspondence checks.
- Snapshot text remains identical after changing owner, property, organization and photograph metadata. New PDFs add no persistent document files. Snapshot tampering and unavailable templates fail safely. Failed rendering creates no print event.
- Current owner access, internal-record denial, loss of access after ownership ends, auditor read/print and denial of creation, CSRF, unauthenticated output denial, duplicate print tokens, reversal warnings and legacy original preservation.
- Batch interruption/resume, repeated generation, failed-item retry, combined page counts, long Unicode printer names, independent ten-record sidebar pagination, direct payment receipt links and correspondence reports.
- Browser: keyboard/dropdown/outside-click behavior, independent overlays, remembered basemap, search, fullscreen, responsive popup image fit, single/multiple/empty galleries, unavailable-image message, Escape/focus return, desktop sidebar and mobile stacking, Trust and Forest themes in day/night modes.
- OpenStreetMap and Esri tiles visibly loaded. The configured orthophoto selector was checked using a test source and then its fixture settings were restored. No real orthophoto dataset was supplied, so actual orthophoto coverage/georeferencing remains unverified.
- Generated demand, invitation and receipt pages visually inspected; attribution footer checked for clipping. Extracted PDF text confirmed preserved historical values and the reversal warning.
- Fresh CLI installation and standalone SQL import passed. Trust export manifest: 53 files verified. Latest test operational backup: 23 files verified, including 16 protected originals and versioned assets. Restoration into eddt_correspondence_complete_restore reproduced a new immutable record, its print history and on-demand PDF.

These are local checks, not production deployment or physical-printer confirmation. Tests use isolated databases and storage. QA PDF artifacts in var/qa are review output, not the application's correspondence archive.

Current test commands:

```powershell
C:/xampp/php/php.exe tests/mysql-workflows.php
C:/xampp/php/php.exe tests/exports-batches.php
C:/xampp/php/php.exe tests/correspondence-on-demand.php
C:/xampp/php/php.exe tests/application-rebuild.php
# Run the loopback QA server against eddt_workflow_v2_test on port 8102 first:
C:/xampp/php/php.exe tests/correspondence-http.php
```
