# EDDT SRMS: MySQL property-centred rebuild

This plan supersedes the earlier database-portability and separate land-account design. MySQL/MariaDB on XAMPP is the development and production database target.

## Confirmed workflow

Register parcel ? Administrator assigns owner ? staff assess rent ? Administrator approves assessment ? staff issue annual bill ? collect payment ? archive receipt and correspondence.

A property and a parcel are the same entity. Its permanent 12-character Account number is the physical primary key: EDDT + division (2 digits) + block (3 digits) + parcel (3 digits). Account numbers are never changed or reused. Owners are independent records; one owner may own many properties and a property may have multiple owners. Only Administrators assign owners or record ownership transfers. Dated relationships determine owner access.

## Database

Use the clean baseline in database/mysql/schema.json and tools/install-mysql.php for NEW databases. Preserve existing databases while testing. The baseline has no legacy migration tables or legacy foreign-key columns. Properties and all financial/document relationships use account_number; internal records such as individual payments retain their own IDs. Compatibility views expose the same property entity to existing reports without creating separate property or land-account records.

Property registration and geometry; people and contacts; dated ownership; annual assessments and bills; payments and allocations; adjustments and reversal metadata; managed documents and versions; correspondence and resumable batches; imports, roles, settings and audit records form the schema modules. Workflow queues derive the next action from these records, so there is no second mutable workflow status that can disagree with the financial records.

## Workspaces

- Staff workflow queues: assign owner, assess rent, approve assessment, issue bill, collect payment, up to date, exempt.
- Property workspace: next action, balance, assessment, bills, payment entry, owner/photo/document history and correspondence.
- Interactive dashboard: collection year controls; monthly bar/line switch; hover/focus amounts; billing-series toggles; payment method and month drill-downs; status-to-map links; accessible tabular alternatives.
- Existing mapping, searchable payment entry, owner contact CSV/Excel exports and combined batch PDFs remain supported.

## Financial rules and access

Use decimal-safe calculations and account-level locking. Payments settle oldest bills first, with excess retained as credit. Financial corrections require Administrator reversals. Arrears remain on the property after transfers. Payment map colors include all outstanding bills: red unpaid, yellow partial, green paid, white exempt, gray unbilled. Explicit exemption does not cancel old debt. Owners see only current authorized properties and owner-visible documents. Audit sensitive writes without recording credentials.

## Delivery and validation

Install and seed only isolated test databases first. Exercise the process end to end, owner isolation, multi-property ownership, permission denials, financial reconciliation, retries, duplicate submissions, clean-schema exports, mobile UI and interactive charts. Existing production data is not replaced by fictional fixtures. A switch to a fresh production database requires importing and reconciling the approved real dataset and document storage, then performing UAT and a backed-up cutover.
