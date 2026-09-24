# Verification record

Date: 17 September 2026.

## Verified

- PHP 8.2.12 and MariaDB 10.4.32 on XAMPP.
- All application, view, CLI, and test PHP files pass syntax checks.
- 87 service/integration assertions pass in the isolated eddt_srms_test database. Test writes roll back.
- Two separate-process concurrency assertions pass: account locking for simultaneous payments and exactly-once handling of a duplicated submission key.
- 28 HTTP checks pass against the loopback demo instance, including every principal screen, CSRF, login/logout, map data, authenticated PDF/XLSX report exports, and Trust export.
- Six multipart checks pass: primary-photo upload, exact download hash, CSV dry run, approved CSV commit, XLSX validation, and denial of anonymous document download.
- Total: 123 automated checks. Export-manifest assertions are counted per file.
- Fresh database bootstrap and repeated additive migrations were exercised. The working eddt_srms database was not reset.
- An operational backup restored into a new eddt_qa_restore database; all three documents present in that backup passed restored-file hash verification.
- The main Apache endpoint /srms/public/index.php?r=health returns ready. Direct HTTP access to var/demo-access.txt and config/default.php returns 403.
- The desktop dashboard was visually inspected. Phone/password browser login and six mapped demo parcels were confirmed.
- Original demand/invitation PDFs and the agreement were rendered and inspected. The original letterhead logo and watermark were extracted for correspondence. A generated demand notice was visually reviewed after the template update.
- Generated demand, invitation, and receipt PDFs are archived with a template version and data snapshot. Later automated generation also exercised a primary photograph and retained its document ID/hash in the snapshot.

## Remaining acceptance and handover checks

- The first operational Administrator account has not been created: a real name and phone number are still needed. No default production password or fabricated production identity was added. Use tools/setup.php with those details.
- The browser automation runtime became unavailable after a session interruption. Final mobile/responsive verification and final visual reinspection of all three regenerated PDFs remain outstanding. Earlier desktop and source-PDF checks succeeded; later PHP/HTTP checks succeeded.
- Firefox, Edge, Safari, and a representative 10,000-record production import have not been tested in their actual target environments.
- MySQL/MariaDB is the sole supported database target; see MYSQL-REBUILD.md for the current rebuild checks.
- Live hosting, real Trust-data migration, completed officer training, and Trust-led UAT/sign-off have not occurred.
- Trust phone/contact details, editable legal-demand wording, invitation text, and signatory require operational review before live correspondence.

See UAT.md for reproducible scenarios and OPERATIONS.md for installation, monitoring, backup, restore, and handover.

Dependency audit: Composer reported no known security vulnerability advisories for the installed lock file.
