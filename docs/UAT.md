# Acceptance and training checklist

Run UAT on a separate database with clearly identified sample records. Do not treat synthetic test results as Trust acceptance or proof of deployment on the eventual production server.

## Repeatable developer checks

- Fresh schema installation, upgrade, and second migration run preserve data.
- `php tests/run.php`: service integration and data-access checks; database changes roll back.
- `php tests/concurrency.php`: two real worker processes test payment locks and duplicate submissions.
- `php tests/http.php`: all main screens, CSRF/login/logout, map JSON, PDF/XLSX downloads, and Trust export using the isolated demo server.
- Operational archive restore to a new `_restore` database; record/document hash verification.
- PHP syntax checks across application, tools, views, and tests.

For a demo workspace:

```powershell
$env:SRMS_DB_NAME = 'eddt_srms_demo'
$env:SRMS_BASE_URL = 'http://127.0.0.1:8081'
C:\xampp\php\php.exe tools/migrate.php
C:\xampp\php\php.exe tools/demo.php
C:\xampp\php\php.exe -S 127.0.0.1:8081 -t public tools/qa-router.php
```

Demo setup refuses non-demo/test databases and writes temporary credentials to `var/demo-access.txt`. The QA router binds to loopback and is not a production router. Its PDF review helper uses locally downloaded PDF.js under `var/qa`; it is optional and not a runtime app dependency. The normal app serves all production routes through `public/index.php`.

## User acceptance scenarios

| Area | Expected result |
|---|---|
| Roles | Administrator, Billing Officer, Auditor, and Owner see only authorized actions and records |
| Phone login | Primary and secondary verified/enabled numbers resolve to one owner; disabled/unverified numbers fail |
| Ownership | Individual, corporate, and joint owners work; transfer preserves debt and removes former-owner access |
| Registry | Land IDs are unique; unknown Land IDs may be blank; one active account per property |
| Mapping | Parcels/points render, OSM and Esri switch, configured orthophoto works, full screen works, search/selection/export work, invalid geometry is rejected, and offline tiles do not block registers |
| Assessments | Pending assessment cannot issue a bill; Administrator approval permits individual/batch issue |
| Payments | Partial payments, excess credit, future credit use, penalties, signed adjustments, reversals and duplicate races reconcile |
| Documents | Valid PDF/JPEG/PNG upload, protected downloads, internal visibility, historical versions, and multiple photographs work |
| Correspondence | Demand/invitation/receipt PDFs use correct data and print layout; QR requires login; generated originals and snapshots persist |
| Reports | On-screen and exported filters match; owner reports do not disclose other accounts |
| Imports | CSV/XLSX validation identifies invalid/duplicate rows; approval commits only valid batches; continuation does not repeat committed rows |
| Recovery | Export manifests verify; operational backup restores to a new database with matching records, balances, and files |

Test long names/descriptions, missing photographs, multiple pages of reports, empty registers, mobile navigation, and error messages. Compare correspondence against both supplied PDFs; fill in current Trust contact details and approve the editable invitation/demand text before issuing live letters. QR codes are secure account links, not a mobile-money gateway.

Use representative 10,000-record migration input for scale acceptance. The agreement limits migration to structured electronic records; physical-paper digitization and unrestricted custom reports are excluded. Record environment, browser/version, fixture, steps, expected result, actual result, evidence, severity, owner, and retest outcome for each defect. Firefox/Edge/Safari checks must be run on those actual environments before claiming cross-platform acceptance.

## Training sessions

Session 1: staff sign-in, registers, owner phones, parcels/maps, joint ownership/transfers, document/photo upload, and owner portal visibility. Each officer completes one property-to-account exercise.

Session 2: assessment approval, billing, payment allocation/credit, receipt generation, correction by reversal, reports, import review/approval, audit history, and recovery/handover. Each officer completes the end-to-end workflow and reconciles its reports.

Prepare two group sessions for up to ten designated officers as described in the agreement. Provide the user guide beforehand and record attendance, questions, defects, and follow-up actions. The Trust’s acceptance certificate and live-server handover remain user-led milestones.
