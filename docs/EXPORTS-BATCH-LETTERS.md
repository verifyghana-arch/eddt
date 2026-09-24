# Property exports and combined correspondence verification

Verified 21 September 2026 using the isolated eddt_srms_rebuilt_test database and fictional records. No live property letters were generated for these checks.

- 25 export/batch checks passed: contact fields, joint owners, unassigned properties, empty filters, spreadsheet formula protection, eligibility, duplicate submission protection, interrupted batch recovery, failed-item retry, changed eligibility, combined page counts and staff/owner access restrictions.
- 46 existing account-model regression checks passed with database fixture changes rolled back.
- HTTP checks returned successful pages for reports, documents, billing, register, and batch forms/previews. Fictional CSV and XLSX downloads contained the expected 12 property rows and 22 columns; Excel retained leading zeroes. Empty CSV retained its headers.
- Headless Chrome rendered the report and batch forms. PDF.js rendered the combined demand PDF (2 pages) and invitation PDF (4 pages). First-page visual inspection confirmed existing letter branding, Account details, photographs where applicable, dates, totals and QR placement. The merger imports archived PDF pages without changing their layouts.
- Stress testing of a full 500-letter batch was not performed. Generation uses three-record requests; downloads merge completed archives and verify their hashes. Existing QR authorization is unchanged.

Install dependencies with Composer, then run `php tools/migrate.php` to add migration 006 (correspondence batch tracking). This migration adds tables without resetting records. A full local backup was created before applying the migration. FPDI and FPDF are required for combined PDFs and are recorded in composer.lock. Full backups include both batch tracking tables and existing archived documents.

Run `php tests/exports-batches.php` and `php tests/account-model.php` against the configured isolated fixtures. The batch test writes fictional combined PDFs under var/qa for rendering review and rolls back database changes.

## Ownership permissions and expanded fields follow-up

Verified 21 September 2026: 11 new rollback-only checks passed for a single owner linked to two properties, owner access to both, Administrator-only assignment, approved assessment-year amounts, unassessed properties, address/contact fields, and prior-year arrears after payment. The 25 export/batch checks and 46 account-model checks also passed (82 total). No schema change or live record updates were needed. The expanded property export has 27 columns; the earlier 22-column HTTP result above describes the previous version.
