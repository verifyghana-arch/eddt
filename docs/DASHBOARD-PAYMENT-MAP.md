# Readable dashboard and payment map — 18 September 2026

## Delivered

- Larger fonts throughout navigation, forms, tables, dashboard labels, map controls and popups. Body text is 16 px; navigation is 15 px; form fields are 16 px on mobile.
- Four server-rendered charts: monthly posted collections (12 months), property payment status, billed versus applied payments (up to five billing years), and payment methods (12 months). Charts have readable labels, exact-value tables or legends, and honest empty states. Reversed payments are excluded; amounts remain decimal-safe in calculations.
- Shared payment classification across dashboard and map: red for billed/unpaid; yellow for partial payment; green when all issued balances are settled; white for explicit exemption; gray for no issued bills. All issued years and arrears are included, as requested. Selection adds a navy border without changing the payment fill color.
- Popup owner names and joint ownership shares, balances and a property image gallery. Images load when a popup opens, primary image first, followed by display order. Captions, capture dates and previous/next controls are included. Missing owners/photos show explicit empty states. Photos retain the existing authenticated download controls and audit trail; owner users cannot see internal or superseded photos, or another property's preview.
- Administrator-only exemption decisions on property details, with a required reason and audit history. Exemption prevents new bills and batch billing skips exempt properties. Existing bills and arrears remain unchanged. Removing exemption restores the computed financial color.

## Upgrade and operation

Migration `004_property_exemptions` adds `properties.is_exempt` and `properties.exemption_reason` without deleting data. It was applied to the live, demo and test databases. Re-running migrations is safe:

```powershell
C:\xampp\php\php.exe tools/migrate.php
```

Before migration, backup `var/backups/srms-20260918-011525-c4ff8c70.zip` was created; all 11 integrity-manifest entries verified.

To populate popup details, open a property's detail page and record its current owner(s). Under **Upload a document or photograph**, select **Property photograph**, supply the image and caption, and select **Primary property photo** as appropriate. Mark **Visible to property owners** only when intended. The popup reads these records automatically.

The live Billing Roll still has 6,266 properties and mapped features, zero issued bills, zero owner links and zero property photographs. All 6,266 therefore correctly appear gray. No sample owners, photos, payments or exemptions were inserted into the live database.

## Verification

- 87 existing integration checks passed.
- 29 new dashboard/map checks passed: all payment states, credit, reversals, historical debt, required exemption reasons, preserved balances, billing restrictions, audit entries, joint owners, primary/current photo order, internal-photo restrictions, exact chart totals, owner isolation and ownership transfers.
- 31 HTTP checks passed, including authenticated property-preview responses, missing records and unauthenticated preview rejection.
- 20 Billing Roll regression checks passed.
- All application PHP files pass syntax checks; migration repeatability and live record counts verified.
- Hidden Chrome verified four chart panels, larger fonts, mobile dashboard width, all five payment states, map payment filtering, protected image loading, gallery navigation and popup positioning below mobile map controls. Desktop and mobile screenshots were reviewed. An async popup-resizing issue found during review was corrected and retested.
- Visual fixtures are explicitly synthetic and isolated in `eddt_srms_visual_test`, with storage under `var/qa/visual-storage`. The QA gallery uses labeled logo images to verify image rendering and navigation; these are not represented as actual property photographs. Screenshots are stored under `var/qa/` (`charts-desktop.png`, `charts-mobile.png`, `payment-map-colors.png`, `parcel-photo-popup.png`, `parcel-photo-popup-mobile.png`).

The initial QA setup was briefly blocked by an automatic approval-review usage limit; after the user requested continuation, authorized execution resumed and all checks above completed.