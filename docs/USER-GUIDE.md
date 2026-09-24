# EDDT SRMS user guide

## Sign in and roles

Use your verified phone number and password. Ghanaian local numbers such as `024…` normalize to `+23324…`. An owner may have multiple verified, login-enabled numbers attached to one account. The first sign-in requires a password change. Passwords contain 12–72 characters. Ten failed attempts within 15 minutes temporarily block the matching phone or IP address. Idle sessions expire after 30 minutes.

| Role | Access |
|---|---|
| Administrator | All modules; approvals, imports, user provisioning, reversals, settings, exports |
| Billing Officer | Property/owner/account records, documents, assessments, bills, payments, reports, import validation |
| Read-Only Auditor | Read records, reports, documents and audit history; no operational changes |
| Property Owner | Current owned properties, bills, payments, owner-visible documents and reports |

Ask an Administrator for a temporary password if locked out. An access change or password reset invalidates existing sessions.

## Registers and ownership

Use **Properties** to search and maintain parcels/properties, and **Property owners** to maintain people and companies. A parcel and property are the same record. Its permanent 12-character Account number, such as `EDDT03011010`, is the database key: EDDT + two-digit division + three-digit block + three-digit parcel. It cannot be edited after creation. A Land ID remains optional descriptive information. Keep inactive records by archiving them; financial history is not deleted.

A company is a corporate owner with an organization name and optional company registration number. One owner may hold several properties. On a property detail page, an Administrator can add another owner for joint ownership. Record known ownership percentages; a warning appears if declared shares do not total 100%. To transfer a share, select the former owner and effective date. Its previous link is ended and the new relationship recorded. Debt stays on the permanent property Account number. A former owner loses access from the transfer date. Future-dated transfers are not applied ahead of their effective date.

Each property Account number directly owns its assessments, bills, payments, documents and correspondence. There is no separate land-account record to create.

## Owner phone numbers and portal accounts

On an owner detail page, add a phone number, label, and primary flag. To change an existing number’s verification or login flags, enter that same number again. Only an Administrator can verify ownership or enable login. The verification checkbox records an officer’s completed verification; the application does not send SMS codes.

In **Users & access**, create a Property Owner user linked to the existing owner record. A verified, enabled owner phone number may sign in to this account. All active joint owners can view the property’s owner-visible records. Internal documents remain staff-only.

## Mapping

Open **Property map** to inspect properties. Select a shape to open its record. Search by Account number, Land ID, owner or locality. Switch between OpenStreetMap and Esri World Imagery, and select the Trust orthophoto when an Administrator has configured an XYZ tile URL. Use **Full screen** for map-only work. Export the authorized features as GeoJSON.

On a property form, enter latitude/longitude or a valid Point/Polygon/MultiPolygon GeoJSON geometry. The boundary editor lets you click polygon corners and choose **Finish polygon** to populate the GeoJSON field. Save the form to persist it. Import a FeatureCollection through **Data imports**; every feature needs a valid unique `Account`. Approved imports never overwrite conflicting properties. Base tiles require a network connection; registers and stored geometry remain available without them.

## Assessments, bills, payments, and corrections

1. A Billing Officer enters a positive annual assessment.
2. An Administrator approves it.
3. Issue that assessment with a payment deadline, or issue all approved assessments for a year. The same account/year cannot be billed twice.
4. Record a positive payment with date and method. The account’s oldest bills are paid first. Any excess becomes account credit and is applied when a later bill is issued.
5. Generate the receipt from the account detail page.

Amounts are GHS. Future payment dates and negative payments are rejected. Cash, bank, cheque, and mobile-money entries record transactions already handled by staff; they do not initiate transfers or collect money electronically.

Penalties are staff-entered positive amounts. Adjustments may be positive or negative but cannot make a bill total negative. Both require a reason and remain in the audit history. Incorrect posted payments must be reversed by an Administrator, with a reason; the original transaction is retained and allocations are rebuilt. Record a fresh payment when necessary. Archived receipts describe their status at issue; the account page shows current payment status.

Earlier outstanding bills appear as arrears on a demand notice; they are not added to a new annual bill’s principal.

## Documents, photographs, and correspondence

Upload PDF, JPEG, and PNG files from a property, owner, or account detail page. Select **Visible to property owners** only when appropriate. A replacement creates a new version while retaining the previous original. Every download is authorized and integrity-checked.

For photographs, select **Property photograph**, enter a caption/date/order, and choose one primary photograph. View and edit photograph metadata in the property’s **Photos** tab. A primary photograph must be owner-visible to appear in correspondence.

From a property, choose a demand notice, invitation, or receipt. Demand notices require a bill; receipts require a payment; invitations require a start/end date. Generation archives the PDF and its data snapshot. The QR code leads to the protected property record; it does not initiate a payment. Organization details, contact information, signatory, invitation text and map sources are configured by the Administrator.

Invitation text supports `{{period_from}}`, `{{period_to}}`, `{{address}}`, `{{phone}}`, `{{email}}`, `{{owner}}`, and `{{land_account_number}}`. Keep dates dynamic to avoid copying an old service period.

## Imports and reports

Download the exact CSV template for properties, owners, accounts, or opening balances. CSV and XLSX uploads must use the same header order. Import properties and owners before accounts, then opening balances. Validation changes no operational records. Correct all errors and upload a new file. An Administrator approves the batch and commits up to 100 rows at a time. Continue until completed; already imported rows are not repeated.

Opening balances require an account number, billing year, positive amount, due date, and legacy reference. They create traceable bills and cannot duplicate an existing account/year.

Reports provide date and locality filters where applicable. Dates filter bill issue dates, payment dates, assessment effective dates, ownership start dates, or document/audit timestamps. Balance/credit reports are current snapshots. Export PDF or Excel from the same filtered report. Owner reports retain owner restrictions.

## Billing Roll properties and map

Use **Data imports → Billing Roll GeoJSON** to validate a file with `Account` and optional `FID` feature properties. Each Account must match an existing parcel number. Review all validation errors and planned actions. Administrators can approve one chunk or process all remaining chunks, pausing safely between transactions. Back up before approval. Repeating a file skips existing matching links; it never overwrites another property's parcel link. These records do not create owners or financial accounts.

The map legend distinguishes active, archived, closed and selected parcels. Search matches property/parcel numbers, Land IDs, locality and owners. Layer choices remain selected while filtering. **Fit all parcels** clears the current-area restriction and fits matching records in visible layers. A separate warning appears when base-map tiles fail; parcel boundaries and register links remain usable.
## Dashboard charts, map payments and exemptions

The dashboard shows monthly collections, property payment status, billing versus applied payments, and payment methods. Expand **View monthly amounts** or **View annual amounts** for exact figures. All dashboard data respects the signed-in user's property access.

Parcel colors use all issued bills, including arrears: **red** = no payment; **yellow** = partial payment; **green** = full payment; **white** = explicitly exempt; **gray** = not yet billed. Reversed receipts do not count as payments. Unallocated credit remains separate. Use the payment-status filter to focus the map.

Click a parcel to see its current owners, amounts and photographs. Photos are primary-first, with previous/next controls. Add ownership through the property's **Add owner or record a transfer** section. Upload images through **Upload a document or photograph**, selecting **Property photograph**, caption and primary-photo options. Owner portal users see only owner-visible current images.

Administrators can use **Manage ground-rent exemption** on property details. Both granting and removing exemption require a reason. Exemption stops new annual bills and makes the parcel white, while retaining existing debt and payment history.
## Property contact exports and batch letters

Open **Properties / Parcels ? Export properties and owner contacts**, or choose **Properties and owner contacts** in Reports. Filter by Account number, owner name or locality, then download **CSV** or **Excel**. Exports contain all matching rows; the screen previews the first 100. Each current joint owner gets a separate row. Properties without owners remain included with empty owner details. Multiple phone numbers are separated by semicolons. Excel preserves Account numbers, leading zeroes and phone numbers as text; CSV protects values from spreadsheet formula execution.

Administrators, Billing Officers and Read-Only Auditors can export this staff contact report. Property Owners cannot access it.

Open **Documents** or **Ground rent ? Batch demand notices / invitations**. Choose the letter type and dates, optionally filter locality or paste Account numbers, and preview recipients. Create the batch, then select **Generate / resume letters**. Keep the page open while processing; reopen the batch to resume after an interruption. Completed letters are retained. When complete, choose **Download combined PDF** for printing. Individual PDFs remain archived against each Account number and retain the existing document access controls.

Batches allow up to 500 active properties. Narrow the filter or supply smaller Account lists for larger runs. Properties without current owners are excluded unless explicitly selected. Demand notices require an issued bill for the selected year, outstanding debt through that year, and a non-exempt property. Invitations also support unbilled and exempt properties. Eligibility is checked again during generation. Skipped properties and failed letters are shown in the batch records; retry failures after addressing the reported problem. Creating a new batch intentionally creates new correspondence; resuming an existing batch does not duplicate completed letters.

Only Administrators and Billing Officers can create, resume or download letter batches. Generating letters does not send them by email or SMS.

### Multiple properties per owner and expanded property export

Reuse the same owner record when a person owns more than one property. An Administrator selects that owner from each property's **Add owner or record a transfer** form. Ownership assignment and transfers are Administrator-only, including direct requests to the server. Billing Officers can maintain operational records but cannot assign owners. The owner portal grants access to every property currently linked to that owner.

The Properties page offers **Export all properties (CSV)** and **Export all properties (Excel)**. For filtered results, use **Export properties and owner contacts**. Columns include owner names, all phone numbers, email, locality, plot size and unit, address line, approved assessment years, approved annual ground rent amounts by year, current-year ground rent, prior-year arrears, total outstanding and credit. Arrears are remaining unpaid bills from billing years before the displayed report year, including penalties and adjustments. An unassessed property's assessment fields are blank. Amounts preserve the application's four-decimal precision.

Each current owner has one row per property. A person owning multiple properties appears against every relevant Account number. Joint owners produce multiple rows for the same Account number, so property financial figures repeat: deduplicate by Account number before summing totals.

## Property workflows and interactive dashboard

Use **Workflows** to find properties by the next action they need. Registering a property and assigning its owner lead into the property workspace. Only Administrators assign owners or approve rent assessments. Billing Officers can submit assessments, issue approved bills, and follow the **Record payment** link. Assessment, approval and initial bill issuance require a current owner at the service level. The workspace links to bills, payments, owner details, photographs and batch correspondence for that Account number.

Choose a collections year on the dashboard, switch monthly collections between bars and a line, or toggle billed/paid series. Select a month, payment method or billed year to open its records. Payment-status categories open the filtered map. Hover or keyboard-focus a bar/point to read its amount. The year filter changes collections charts; balances and property status include all outstanding bills.

## Night mode and alternate theme

Open **Appearance** at the bottom-right of any screen, including sign-in. Choose **Day mode**, **Night mode**, or **Follow device**. Choose **Trust red & navy** or the alternate **Forest green & cream** colour theme; both support day and night mode. Preferences are saved for each user in that browser. A new user's initial choice inherits the sign-in screen preference. Use Escape to close the menu. If browser storage is unavailable, changes still apply to the current page.

Night mode covers forms, registers, dashboard charts, navigation and map controls. Parcel payment colours, photographs and generated PDFs retain their original colours. Printed pages use a light background.

Browser verification: login night mode, saved preference reload, signed-in dashboard/charts, alternate day theme, mobile menu fit, device dark/light changes and Escape passed using fictional records. Login and dashboard screenshots were visually checked.
