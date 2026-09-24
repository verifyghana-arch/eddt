# Billing Roll import and full-screen map verification — 2026-09-20

Active database: eddt_srms_rebuilt_demo.
Source: simages/BillingRoll.geojson.
Backup before import: var/backups/srms-20260920-172629-325d22ea.zip.
Approved import batch: 44bccb2f-5bb6-4dc4-894e-56f870d2eca4, recorded under Justice.

All 6,266 source features imported successfully in resumable batches of 100. Total parcel records: 6,278, including 12 existing sample parcels. Source identifiers and original feature data are retained in import_rows, together with the file hash and approval audit history.
Every imported boundary was compared with the source: no mismatches. No owners, Trust Land IDs, bills or credits were assigned to imported parcels. All 6,266 are unbilled and gray on the map.

The Full screen button expands the whole map panel, including search, filters, export, legend and record count. Exit full screen restores the embedded map. Native browser fullscreen is used where available; a viewport fallback supports other browsers. Escape exits the fallback, focus stays within the expanded panel while tabbing, and Leaflet recalculates its size after expansion/restoration.

Chrome checks passed: 6,278 rendered features; GeoJSON export count; native full-screen viewport dimensions; exit; fallback and Escape; mobile fit; imported Account search; imported parcel popup; layer toggle; mobile popup bounds after opening animation.
Screenshots: var/qa/billing-roll-fullscreen-desktop.png and var/qa/billing-roll-fullscreen-mobile.png.