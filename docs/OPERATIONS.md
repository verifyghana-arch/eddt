# Installation, operations, and deployment

## Local setup

Use the README commands. `tools/install-mysql.php NEW_DATABASE` installs the clean schema in a new database. `tools/migrate.php` applies additive changes to existing installations. It never runs the destructive DROP statements in the legacy schema. Do not manually re-run `database/schema.sql` against data you intend to retain.

The first Administrator is provisioned with `tools/setup.php PHONE NAME`; it refuses to run if users already exist. The temporary password file is local-only. Provision other staff and owners through **Users & access**. Configure Trust phone/contact details before issuing real correspondence.

The `/index.php?r=health` endpoint returns a generic readiness response and HTTP 200/503. Run `tools/doctor.php` locally for detailed checks. Application failures receive a reference ID; detailed exceptions are in `var/logs/application.log`. Never expose those logs over HTTP.

## Production hosting

Use an HTTPS virtual host whose document root is the project’s `public/` directory. Set the canonical `base_url` accordingly. Only that directory needs public read access. Grant the PHP runtime write access to `var/` and the configured document storage path. Deny directory listing and executable uploads. Keep configuration, database scripts, original samples, tools, tests, dependencies, and backups outside the public root.

Use PHP 8.2 or newer compatible with the locked dependencies. Enable required extensions, set `upload_max_filesize` at least 16M, `post_max_size` at least 20M, and a writable upload temporary directory. The application’s configured upload limit cannot exceed the web server’s own limits. Configure enough memory for PDF and spreadsheet generation (256 MB is a practical initial setting), log errors privately, and disable browser error display.

Use a dedicated least-privilege application database account. Run migrations using a separate deployment account with DDL permission. Keep secrets in server environment settings or an access-restricted `config/local.php`; never commit them. Configure backup encryption, TLS termination, log retention, monitoring, and an appropriate map-tile provider before go-live. The application requires no SMS or payment-provider credentials.

Windows IIS: install PHP through FastCGI, serve only `public/`, configure FastCGI environment variables, enable the required PHP extensions, and keep sessions/documents outside the served root. Routes use `index.php?r=…`, so URL rewriting is not required. Windows Server uses MySQL/MariaDB. See MYSQL-REBUILD.md for fresh installation.

## Backups and recovery

There are two distinct exports:

- **Trust export** in Settings: structured JSON records, original documents, correspondence, and a SHA-256 manifest. Password hashes are excluded. Verify with `php tools/verify-export.php export.zip`.
- **Operational backup**: complete SQL dump (including credential hashes) and files. Run `php tools/operational-backup.php`. Archives are created in `var/backups/`. Copy them to encrypted, restricted, separately located storage using the Trust’s backup system.

`SRMS_MYSQL_BIN` can override the MySQL utility directory (default `C:/xampp/mysql/bin`). Backups use a consistent database snapshot. Application document originals are immutable and are not automatically deleted.

Test recovery regularly:

```powershell
C:\xampp\php\php.exe tools/restore.php var/backups/ARCHIVE.zip eddt_recovery_restore
```

Restore requires a **new** database whose name ends in `_restore`. It refuses existing targets, verifies the archive manifest, loads the SQL into the new database, extracts documents into `var/restores/TARGET/storage`, and verifies every database-referenced document hash. The original database is untouched. Run migrations against the restored database, configure a separate UAT instance, compare balances/record counts, and sign off before changing the live configuration. Restrict access to recovered credentials and backups.

## Retention and monitoring

Retain financial records, audit history, document originals/versions, and archived correspondence. Archive inactive operational records through their status field. Permanent disposal is a separately controlled administrative process, not a web action. Include metadata and original file storage in every recovery plan.

Monitor readiness, failed logins, application-error references, disk capacity, backup completion, and restore drills. Audit records cover financial changes, administrative changes, ownership, document operations, imports, report exports, and authentication. Rotate logs through the host’s log-management service. The 30-minute session timeout is independent of PHP’s eventual session-file garbage collection.

## Go-live handover

Record the release checksum/commit, dependency lock file, server configuration, data-migration reconciliation, UAT sign-off, backup location, recovery test, administrator access, and contact details. Provide credentials through a secure channel and verify the Trust can independently install and operate the delivered source. Training materials and the UAT checklist are included; live hosting selection, operational data migration, user-led UAT, and training attendance require the Trust’s participation.
