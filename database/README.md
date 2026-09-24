# SRMS database setup

## Local XAMPP setup

1. Start Apache and MySQL from the XAMPP Control Panel.
2. Open phpMyAdmin or the MySQL client.
3. Run `C:\xampp\php\php.exe tools/migrate.php` from the project root.
4. Confirm that the `eddt_srms` database contains the SRMS tables and the four initial roles.

The legacy `schema.sql` script drops and recreates application tables. Do not use it to install or upgrade the working application. The migration runner creates a fresh schema without executing DROP statements and applies versioned additive upgrades to existing databases. See the project README and operations guide.

The schema stores PDF/image metadata in MySQL and expects file binaries to be handled by the configured storage service. `boundary_geojson` keeps the spatial representation portable while MySQL/MariaDB remains the only supported database.

## Printed correspondence

The schema supports the supplied EDDT samples. A parcel/property is identified by its permanent Account number; compatibility views expose the same record under older names. Properties store plot size, plot-size unit, land description, and digital address. Ground-rent bills store notice-print and payment-deadline dates. The `correspondence` table records generated demand notices and invitation letters, their recipient links, service period, printed date, template variables, status, and generated PDF document. This preserves a reproducible history of what was printed while allowing the PDF itself to remain in external document storage.

Each property can have multiple photographs through `property_photos`. Every photo is an externally stored image tracked in `documents`, with optional type, caption, capture date, display order, and primary-image flag. The application must validate that linked documents are images and must authorize photo viewing and downloads using the property's access rules.

## Login identities

Phone numbers are login identifiers. Phone numbers are normalized into `ratepayer_phone_numbers` and `user_phone_numbers`, so a property owner can have two or more numbers without duplicating the ratepayer or property. Each number can be labeled, marked primary, verified, and enabled/disabled for login. A property owner is a ratepayer linked to a user account through `users.ratepayer_id` and uses the `Property Owner` role. Any verified, login-enabled owner number can authenticate to that same account and access only authorized property, ground-rent, document, and receipt records.

## Property identifiers

`properties.land_id` stores the Trust's existing Land ID and is indexed for property searches. It is nullable so legacy records can be loaded before their Land ID is confirmed, but each supplied Land ID must be unique. `property_number` remains available as a separate system identifier.

## Ownership model

Ownership is represented by the `property_ratepayers` linking table, so one property can have several owners and one ratepayer can own several properties. Use `relationship_type = 'owner'` for ownership records and record an optional `ownership_percentage` where the ownership shares are known. A corporate owner is stored as a ratepayer with `ratepayer_type = 'corporate'`, its legal/entity name in `organization_name`, and its company registration number when available. Joint ownership is therefore represented by multiple active owner links for the same property; application validation must prevent overlapping duplicate links and should warn when declared shares do not total 100%.
