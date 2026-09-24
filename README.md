# EDDT Spatial Revenue Management System

A plain PHP 8.2 application for property records, mapping, ground-rent accounting, documents, correspondence, imports, reports, and an owner portal. MySQL/MariaDB is the database for local and production deployment. The property-centred workflow and clean schema are described in docs/MYSQL-REBUILD.md.

## Run on XAMPP

1. Start Apache and MySQL.
2. Enable PHP extensions `pdo_mysql`, `bcmath`, `fileinfo`, `gd`, `zip`, `dom`, `mbstring`, and `openssl`. Restart Apache after changing `php.ini`.
3. From this folder, run:

```powershell
C:\xampp\php\php.exe composer.phar install --no-dev --prefer-dist
C:\xampp\php\php.exe tools/install-mysql.php eddt_production
$env:SRMS_DB_NAME='eddt_production'
$env:SRMS_STORAGE_PATH='C:/xampp/htdocs/srms/var/production-storage'
C:\xampp\php\php.exe tools/setup.php +233YOURNUMBER "Administrator Name"
```

For a fresh installation, configure the same database and storage path in config/local.php for Apache before signing in. The installer requires a new database name; it never resets existing records. For an existing installation, keep its configuration and use tools/migrate.php for additive upgrades. Use a real phone number in international format. Setup writes a random temporary password to `var/initial-access.txt`, which is blocked from HTTP access. Sign in at **http://localhost/srms/public/**, change the password, and remove the handover file after recording credentials securely. There are no hardcoded production passwords or demo users in the working database.

To import the supplied billing-roll property boundaries into the map, use **Data imports → Billing Roll GeoJSON** for preview and Administrator approval. For a controlled command-line import into a prepared test database, run:

```powershell
C:\xampp\php\php.exe tools/import-billing-roll.php simages/BillingRoll.geojson
```

The import creates the **Billing roll** map layer and is safe to repeat; existing parcel numbers are skipped. Open **Property map** and choose **Fit all parcels** to view the imported boundaries.

The repository includes `composer.lock`. Install exactly its versions. If `composer.phar` is absent, install Composer from its official website and use `composer install` instead.

## Configuration

Copy desired overrides into `config/local.php`, returning a PHP array. This file is ignored by source control. Environment variables are also supported: `SRMS_DB_HOST`, `SRMS_DB_PORT`, `SRMS_DB_NAME`, `SRMS_DB_USER`, `SRMS_DB_PASSWORD`, `SRMS_BASE_URL`, and `SRMS_STORAGE_PATH`.

```php
<?php
return [
    'db_name' => 'eddt_srms',
    'db_user' => 'srms_app',
    'db_password' => 'set-a-local-secret',
    'base_url' => 'http://localhost/srms/public',
    'storage_path' => 'C:/srms-data/documents',
];
```

For production, serve only `public/`, use HTTPS and a restricted database login, and locate document storage outside the web root. XAMPP subfolder mode is supported through the root access-denial rules. Sessions, logs, exports, and local credentials live under the protected `var/` directory.

## First workflow

Create a property with its permanent 12-character Account number → create or select its owner → let an Administrator assign ownership → enter and approve an assessment → issue the annual bill → record a payment → generate a notice or receipt from the property workspace. The Account number is the property key and cannot be changed. Administrators provision owner portal accounts using verified phone numbers.

Read [the user guide](docs/USER-GUIDE.md), [operations and deployment](docs/OPERATIONS.md), [MySQL rebuild and installation](docs/MYSQL-REBUILD.md), and [UAT checklist](docs/UAT.md).

## Verification

```powershell
C:\xampp\php\php.exe tools/doctor.php
$env:SRMS_DB_NAME = 'eddt_workflow_v2_test'
C:\xampp\php\php.exe tests/mysql-workflows.php
C:\xampp\php\php.exe tests/application-rebuild.php
C:\xampp\php\php.exe tests/property-ownership-export.php
C:\xampp\php\php.exe tests/exports-batches.php
```

Integration tests use `eddt_srms_test` and roll their records back. Concurrency tests create uniquely named fixtures and remove only those fixtures after completion. Never point these tests at operational data. The HTTP suite uses a separate demo server and `var/demo-access.txt`; see the UAT guide.

## Architecture

- `public/`: front controller and locally hosted browser assets; no build step.
- `app/`: request controller, access policies, financial/registry/document services, persistence gateway, and templates.
- `database/schema.sql`: original schema reference; **destructive legacy reset script, not the application installer**.
- `tools/install-mysql.php`: safe fresh installation into a new database.
- `tools/migrate.php`: versioned, non-destructive upgrades for an existing database.
- `tools/`: secure account bootstrap, demo fixtures, diagnostics, backup/restore, and QA utilities.
- `var/`: protected generated data, never source-controlled or directly served.

Financial mutations lock the account and commit balances, payment allocations, and audit records atomically. DECIMAL values use BCMath rather than binary floating-point arithmetic. Display formatting uses two decimals; stored amounts retain four decimals.

All protected pages and JSON endpoints require a session. POST actions require CSRF tokens. Owner access derives from effective ownership dates, including joint owners. No SMS service, payment gateway, or external identity service is required.

## Dependencies and services

Dompdf generates PDFs; chillerlan/php-qrcode creates QR codes; PhpSpreadsheet reads/writes Excel; Leaflet renders maps. Their licenses are retained in `vendor/` and the Leaflet distribution. The Trust artwork was extracted from the supplied correspondence samples. No recurring fees are introduced by the PHP libraries. Map tiles are an external configurable dependency; hosting, domain, SSL, and any paid map provider belong to the Trust. Retain attribution and configure a provider appropriate for production usage.
