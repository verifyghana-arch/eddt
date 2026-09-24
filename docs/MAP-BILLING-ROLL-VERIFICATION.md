# Trust theme, property map and Billing Roll delivery — 18 September 2026

Implemented the Trust red/navy/gold/green theme, accessible focus indicators, responsive map controls and parcel legend. Existing Trust branding and user edits were preserved. The map uses Canvas rendering, cached feature bounds/search text, debounced filtering, persistent layer visibility and highlighted selection.

The application now sends `Referrer-Policy: strict-origin-when-cross-origin`. OpenStreetMap tiles loaded successfully in Chrome with attribution. Tile errors have a separate message and retry control; search and parcel counts do not overwrite it. No proxy, cache bypass or alternate host was used.

## Live data

- Database: `eddt_srms`.
- Administrator: Justice, phone +233543914765. A password change is required at first login. Temporary credentials are in private `var/initial-access.txt`; remove that file after handover.
- Import batch: `709739e6-8cc9-4c6c-ba7c-58c16d29e258`.
- Source: `simages/BillingRoll.geojson`; 6,266 valid features, zero invalid.
- Result: 6,266 existing parcels, 6,266 properties, 6,266 matching parcel/property links. All source boundaries still match. No financial accounts were created. Owners and Land IDs remain unassigned.
- Operational backup immediately before linking: `var/backups/srms-20260918-002310-092fde8f.zip`. All 11 manifest entries verified. A separate earlier backup also exists.

## Import workflow

Data imports → Billing Roll GeoJSON accepts JSON/GeoJSON files up to 15 MB and 10,000 polygon features. `Account` must match an existing parcel; this workflow does not create missing parcels. It previews create/skip actions and conflicts in pages of 100. Original source features, FID, Account and file hash remain in the import batch/rows.

Billing Officers can validate; only Administrators can approve. Approval processes small transactional chunks, with pause/resume controls. Completed records are retained after interruption. Repeated files skip already linked properties. Missing parcels, conflicting links, duplicate source identifiers and invalid geometry block approval. Existing geometry and parcel metadata are never overwritten.

CLI alternative:

```powershell
C:\xampp\php\php.exe tools/link-billing-roll.php --admin=ADMIN_USER_UUID --file=simages/BillingRoll.geojson
C:\xampp\php\php.exe tools/link-billing-roll.php --admin=ADMIN_USER_UUID --batch=BATCH_UUID --commit
```

The CLI requires an existing active Administrator and creates an operational backup before committing. The older `tools/import-billing-roll.php` remains a parcel-only utility; use the new linking workflow for property records. No schema migration or destructive reset was required.

## Verification

- Existing service integration suite: 87 checks passed.
- Billing Roll integration suite: 20 checks passed, including dry-run isolation, invalid/duplicate/missing source data, conflict detection, rollback, interrupted continuation, repeat imports, unchanged parcel metadata, no financial records, owner isolation and Administrator-only approval. Tests run against `eddt_srms_test` and roll back changes.
- HTTP regression suite: 28 checks passed, including login/CSRF, main screens, map data, reports, export and unauthenticated access.
- Changed PHP files pass syntax checks.
- Justice login and mandatory first-login password change verified; the temporary credential file returns HTTP 403.
- Live map repository returns 6,266 authorized property features (observed 0.41 seconds in this environment).
- Chrome: actual map tiles loaded; search, layer toggle persistence, fit, independent simulated tile errors and retry passed. Full source rendered 6,266 polygons; exact identifier search returned one result. Desktop and 390-pixel mobile screenshots reviewed; no mobile horizontal overflow.
- Visual QA used the isolated demo database. The full-volume map used the supplied GeoJSON; live database feature count and boundary identity were verified separately.
- Backup archive integrity verified. This change did not repeat a full disaster-recovery restore drill.

Browser automation initially failed because of the environment's sandbox helper; hidden local Chrome provided successful fallback verification. Screenshots are retained under `var/qa/`, including `billing-roll-full-map.png`, `map-mobile-verified.png` and `dashboard-desktop-verified.png`.