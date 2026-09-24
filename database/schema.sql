-- EDDT SRMS initial schema
-- MySQL 8.x / XAMPP
-- Domain data is kept portable for a later Microsoft SQL Server migration.

CREATE DATABASE IF NOT EXISTS eddt_srms
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE eddt_srms;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS user_phone_numbers;
DROP TABLE IF EXISTS ratepayer_phone_numbers;
DROP TABLE IF EXISTS import_rows;
DROP TABLE IF EXISTS import_batches;
DROP TABLE IF EXISTS payment_allocations;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS correspondence;
DROP TABLE IF EXISTS ground_rent_bills;
DROP TABLE IF EXISTS ground_rent_assessments;
DROP TABLE IF EXISTS document_links;
DROP TABLE IF EXISTS property_photos;
DROP TABLE IF EXISTS documents;
DROP TABLE IF EXISTS property_ratepayers;
DROP TABLE IF EXISTS ownership_history;
DROP TABLE IF EXISTS accounts;
DROP TABLE IF EXISTS ratepayers;
DROP TABLE IF EXISTS properties;
DROP TABLE IF EXISTS parcels;
DROP TABLE IF EXISTS spatial_layers;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE roles (
    id CHAR(36) NOT NULL,
    name VARCHAR(80) NOT NULL,
    description VARCHAR(255) NULL,
    is_system_role TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_roles_name (name)
) ENGINE = InnoDB;

CREATE TABLE users (
    id CHAR(36) NOT NULL,
    role_id CHAR(36) NOT NULL,
    ratepayer_id CHAR(36) NULL,
    username VARCHAR(120) NULL,
    full_name VARCHAR(180) NOT NULL,
    email VARCHAR(254) NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at DATETIME(6) NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_email (email),
    KEY ix_users_role (role_id),
    KEY ix_users_ratepayer (ratepayer_id),
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles (id)
) ENGINE = InnoDB;

CREATE TABLE spatial_layers (
    id CHAR(36) NOT NULL,
    name VARCHAR(150) NOT NULL,
    layer_type VARCHAR(40) NOT NULL,
    description VARCHAR(500) NULL,
    source_reference VARCHAR(255) NULL,
    display_order INT NOT NULL DEFAULT 0,
    is_visible_by_default TINYINT(1) NOT NULL DEFAULT 1,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_spatial_layers_name (name),
    KEY ix_spatial_layers_type (layer_type)
) ENGINE = InnoDB;

CREATE TABLE parcels (
    id CHAR(36) NOT NULL,
    spatial_layer_id CHAR(36) NULL,
    parcel_number VARCHAR(100) NOT NULL,
    locality VARCHAR(150) NULL,
    zoning VARCHAR(120) NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'active',
    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,
    boundary_geojson LONGTEXT NULL,
    notes TEXT NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_parcels_number (parcel_number),
    KEY ix_parcels_locality (locality),
    KEY ix_parcels_status (status),
    KEY ix_parcels_layer (spatial_layer_id),
    CONSTRAINT fk_parcels_layer FOREIGN KEY (spatial_layer_id) REFERENCES spatial_layers (id)
) ENGINE = InnoDB;

CREATE TABLE properties (
    id CHAR(36) NOT NULL,
    parcel_id CHAR(36) NULL,
    land_id VARCHAR(120) NULL,
    property_number VARCHAR(100) NOT NULL,
    plot_size DECIMAL(19,4) NULL,
    plot_size_unit VARCHAR(30) NULL,
    land_description VARCHAR(500) NULL,
    property_type VARCHAR(80) NULL,
    address_line VARCHAR(255) NULL,
    locality VARCHAR(150) NULL,
    digital_address VARCHAR(120) NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'active',
    assessed_ground_rent DECIMAL(19,4) NULL,
    assessment_currency CHAR(3) NOT NULL DEFAULT 'GHS',
    notes TEXT NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_properties_land_id (land_id),
    UNIQUE KEY uq_properties_number (property_number),
    KEY ix_properties_parcel (parcel_id),
    KEY ix_properties_locality (locality),
    KEY ix_properties_status (status),
    CONSTRAINT fk_properties_parcel FOREIGN KEY (parcel_id) REFERENCES parcels (id)
) ENGINE = InnoDB;

CREATE TABLE ratepayers (
    id CHAR(36) NOT NULL,
    ratepayer_number VARCHAR(100) NOT NULL,
    ratepayer_type VARCHAR(40) NOT NULL DEFAULT 'individual',
    full_name VARCHAR(180) NOT NULL,
    organization_name VARCHAR(180) NULL,
    company_registration_number VARCHAR(120) NULL,
    national_id VARCHAR(100) NULL,
    email VARCHAR(254) NULL,
    postal_address VARCHAR(500) NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'active',
    notes TEXT NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_ratepayers_number (ratepayer_number),
    UNIQUE KEY uq_ratepayers_company_registration (company_registration_number),
    KEY ix_ratepayers_name (full_name),
    KEY ix_ratepayers_national_id (national_id),
    KEY ix_ratepayers_status (status)
) ENGINE = InnoDB;

CREATE TABLE ratepayer_phone_numbers (
    id CHAR(36) NOT NULL,
    ratepayer_id CHAR(36) NOT NULL,
    phone_number VARCHAR(40) NOT NULL,
    phone_label VARCHAR(40) NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    can_login TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_ratepayer_phone_number (phone_number),
    KEY ix_ratepayer_phones_ratepayer (ratepayer_id),
    KEY ix_ratepayer_phones_login (can_login, is_verified),
    CONSTRAINT fk_ratepayer_phones_ratepayer FOREIGN KEY (ratepayer_id) REFERENCES ratepayers (id)
) ENGINE = InnoDB;

ALTER TABLE users
    ADD CONSTRAINT fk_users_ratepayer
    FOREIGN KEY (ratepayer_id) REFERENCES ratepayers (id);

CREATE TABLE user_phone_numbers (
    id CHAR(36) NOT NULL,
    user_id CHAR(36) NOT NULL,
    phone_number VARCHAR(40) NOT NULL,
    phone_label VARCHAR(40) NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    can_login TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_user_phone_number (phone_number),
    KEY ix_user_phones_user (user_id),
    KEY ix_user_phones_login (can_login, is_verified),
    CONSTRAINT fk_user_phones_user FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE = InnoDB;

CREATE TABLE property_ratepayers (
    id CHAR(36) NOT NULL,
    property_id CHAR(36) NOT NULL,
    ratepayer_id CHAR(36) NOT NULL,
    relationship_type VARCHAR(40) NOT NULL DEFAULT 'owner',
    ownership_percentage DECIMAL(7,4) NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    KEY ix_property_ratepayers_property (property_id),
    KEY ix_property_ratepayers_ratepayer (ratepayer_id),
    KEY ix_property_ratepayers_dates (start_date, end_date),
    KEY ix_property_ratepayers_relationship (relationship_type),
    CONSTRAINT fk_property_ratepayers_property FOREIGN KEY (property_id) REFERENCES properties (id),
    CONSTRAINT fk_property_ratepayers_ratepayer FOREIGN KEY (ratepayer_id) REFERENCES ratepayers (id)
) ENGINE = InnoDB;

CREATE TABLE ownership_history (
    id CHAR(36) NOT NULL,
    property_id CHAR(36) NOT NULL,
    previous_ratepayer_id CHAR(36) NULL,
    new_ratepayer_id CHAR(36) NOT NULL,
    transfer_date DATE NOT NULL,
    transfer_reference VARCHAR(120) NULL,
    notes TEXT NULL,
    recorded_by CHAR(36) NOT NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    KEY ix_ownership_property (property_id, transfer_date),
    CONSTRAINT fk_ownership_property FOREIGN KEY (property_id) REFERENCES properties (id),
    CONSTRAINT fk_ownership_previous_ratepayer FOREIGN KEY (previous_ratepayer_id) REFERENCES ratepayers (id),
    CONSTRAINT fk_ownership_new_ratepayer FOREIGN KEY (new_ratepayer_id) REFERENCES ratepayers (id),
    CONSTRAINT fk_ownership_recorded_by FOREIGN KEY (recorded_by) REFERENCES users (id)
) ENGINE = InnoDB;

CREATE TABLE accounts (
    id CHAR(36) NOT NULL,
    account_number VARCHAR(100) NOT NULL,
    land_account_number VARCHAR(100) NULL,
    property_id CHAR(36) NOT NULL,
    primary_ratepayer_id CHAR(36) NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'active',
    opened_on DATE NOT NULL,
    closed_on DATE NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_accounts_number (account_number),
    UNIQUE KEY uq_accounts_land_number (land_account_number),
    KEY ix_accounts_property (property_id),
    KEY ix_accounts_ratepayer (primary_ratepayer_id),
    KEY ix_accounts_status (status),
    CONSTRAINT fk_accounts_property FOREIGN KEY (property_id) REFERENCES properties (id),
    CONSTRAINT fk_accounts_ratepayer FOREIGN KEY (primary_ratepayer_id) REFERENCES ratepayers (id)
) ENGINE = InnoDB;

CREATE TABLE documents (
    id CHAR(36) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    storage_key VARCHAR(500) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_size_bytes BIGINT UNSIGNED NOT NULL,
    sha256_hash CHAR(64) NOT NULL,
    document_type VARCHAR(80) NOT NULL,
    description VARCHAR(500) NULL,
    version_number INT UNSIGNED NOT NULL DEFAULT 1,
    is_current_version TINYINT(1) NOT NULL DEFAULT 1,
    uploaded_by CHAR(36) NOT NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_documents_storage_key (storage_key),
    KEY ix_documents_hash (sha256_hash),
    KEY ix_documents_type (document_type),
    KEY ix_documents_uploader (uploaded_by),
    CONSTRAINT fk_documents_uploader FOREIGN KEY (uploaded_by) REFERENCES users (id)
) ENGINE = InnoDB;

CREATE TABLE document_links (
    id CHAR(36) NOT NULL,
    document_id CHAR(36) NOT NULL,
    entity_type VARCHAR(40) NOT NULL,
    entity_id CHAR(36) NOT NULL,
    link_description VARCHAR(255) NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_document_links (document_id, entity_type, entity_id),
    KEY ix_document_links_entity (entity_type, entity_id),
    CONSTRAINT fk_document_links_document FOREIGN KEY (document_id) REFERENCES documents (id)
) ENGINE = InnoDB;

CREATE TABLE property_photos (
    id CHAR(36) NOT NULL,
    property_id CHAR(36) NOT NULL,
    document_id CHAR(36) NOT NULL,
    photo_type VARCHAR(50) NULL,
    caption VARCHAR(255) NULL,
    taken_at DATETIME(6) NULL,
    display_order INT NOT NULL DEFAULT 0,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    created_by CHAR(36) NOT NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_property_photos_document (document_id),
    KEY ix_property_photos_property_order (property_id, display_order),
    KEY ix_property_photos_primary (property_id, is_primary),
    CONSTRAINT fk_property_photos_property FOREIGN KEY (property_id) REFERENCES properties (id),
    CONSTRAINT fk_property_photos_document FOREIGN KEY (document_id) REFERENCES documents (id),
    CONSTRAINT fk_property_photos_created_by FOREIGN KEY (created_by) REFERENCES users (id)
) ENGINE = InnoDB;

CREATE TABLE ground_rent_assessments (
    id CHAR(36) NOT NULL,
    property_id CHAR(36) NOT NULL,
    assessment_year SMALLINT UNSIGNED NOT NULL,
    annual_amount DECIMAL(19,4) NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'GHS',
    effective_from DATE NOT NULL,
    effective_to DATE NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'active',
    approved_by CHAR(36) NULL,
    approved_at DATETIME(6) NULL,
    notes TEXT NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_assessments_property_year (property_id, assessment_year),
    KEY ix_assessments_year_status (assessment_year, status),
    CONSTRAINT fk_assessments_property FOREIGN KEY (property_id) REFERENCES properties (id),
    CONSTRAINT fk_assessments_approved_by FOREIGN KEY (approved_by) REFERENCES users (id)
) ENGINE = InnoDB;

CREATE TABLE ground_rent_bills (
    id CHAR(36) NOT NULL,
    bill_number VARCHAR(100) NOT NULL,
    account_id CHAR(36) NOT NULL,
    assessment_id CHAR(36) NULL,
    billing_year SMALLINT UNSIGNED NOT NULL,
    issue_date DATE NOT NULL,
    due_date DATE NOT NULL,
    notice_printed_at DATETIME(6) NULL,
    payment_deadline DATE NULL,
    principal_amount DECIMAL(19,4) NOT NULL,
    penalty_amount DECIMAL(19,4) NOT NULL DEFAULT 0.0000,
    adjustment_amount DECIMAL(19,4) NOT NULL DEFAULT 0.0000,
    paid_amount DECIMAL(19,4) NOT NULL DEFAULT 0.0000,
    currency CHAR(3) NOT NULL DEFAULT 'GHS',
    status VARCHAR(30) NOT NULL DEFAULT 'issued',
    demand_notice_path VARCHAR(500) NULL,
    notes TEXT NULL,
    created_by CHAR(36) NOT NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_bills_number (bill_number),
    UNIQUE KEY uq_bills_account_year (account_id, billing_year),
    KEY ix_bills_due_status (due_date, status),
    KEY ix_bills_year (billing_year),
    CONSTRAINT fk_bills_account FOREIGN KEY (account_id) REFERENCES accounts (id),
    CONSTRAINT fk_bills_assessment FOREIGN KEY (assessment_id) REFERENCES ground_rent_assessments (id),
    CONSTRAINT fk_bills_created_by FOREIGN KEY (created_by) REFERENCES users (id)
) ENGINE = InnoDB;

CREATE TABLE correspondence (
    id CHAR(36) NOT NULL,
    correspondence_type VARCHAR(40) NOT NULL,
    account_id CHAR(36) NULL,
    property_id CHAR(36) NULL,
    ratepayer_id CHAR(36) NULL,
    bill_id CHAR(36) NULL,
    reference_number VARCHAR(100) NULL,
    printed_at DATETIME(6) NOT NULL,
    period_from DATE NULL,
    period_to DATE NULL,
    subject VARCHAR(255) NULL,
    content_variables_json JSON NULL,
    generated_document_id CHAR(36) NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'generated',
    created_by CHAR(36) NOT NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_correspondence_reference (reference_number),
    KEY ix_correspondence_type_date (correspondence_type, printed_at),
    KEY ix_correspondence_account (account_id),
    KEY ix_correspondence_property (property_id),
    KEY ix_correspondence_ratepayer (ratepayer_id),
    KEY ix_correspondence_bill (bill_id),
    CONSTRAINT fk_correspondence_account FOREIGN KEY (account_id) REFERENCES accounts (id),
    CONSTRAINT fk_correspondence_property FOREIGN KEY (property_id) REFERENCES properties (id),
    CONSTRAINT fk_correspondence_ratepayer FOREIGN KEY (ratepayer_id) REFERENCES ratepayers (id),
    CONSTRAINT fk_correspondence_bill FOREIGN KEY (bill_id) REFERENCES ground_rent_bills (id),
    CONSTRAINT fk_correspondence_created_by FOREIGN KEY (created_by) REFERENCES users (id),
    CONSTRAINT fk_correspondence_document FOREIGN KEY (generated_document_id) REFERENCES documents (id)
) ENGINE = InnoDB;

CREATE TABLE payments (
    id CHAR(36) NOT NULL,
    receipt_number VARCHAR(100) NOT NULL,
    account_id CHAR(36) NOT NULL,
    payment_date DATE NOT NULL,
    amount DECIMAL(19,4) NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'GHS',
    payment_method VARCHAR(40) NOT NULL,
    external_reference VARCHAR(150) NULL,
    payer_name VARCHAR(180) NULL,
    notes TEXT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'posted',
    recorded_by CHAR(36) NOT NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_payments_receipt (receipt_number),
    KEY ix_payments_account_date (account_id, payment_date),
    KEY ix_payments_external_reference (external_reference),
    CONSTRAINT fk_payments_account FOREIGN KEY (account_id) REFERENCES accounts (id),
    CONSTRAINT fk_payments_recorded_by FOREIGN KEY (recorded_by) REFERENCES users (id)
) ENGINE = InnoDB;

CREATE TABLE payment_allocations (
    id CHAR(36) NOT NULL,
    payment_id CHAR(36) NOT NULL,
    bill_id CHAR(36) NOT NULL,
    amount DECIMAL(19,4) NOT NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_payment_bill (payment_id, bill_id),
    KEY ix_allocations_bill (bill_id),
    CONSTRAINT fk_allocations_payment FOREIGN KEY (payment_id) REFERENCES payments (id),
    CONSTRAINT fk_allocations_bill FOREIGN KEY (bill_id) REFERENCES ground_rent_bills (id)
) ENGINE = InnoDB;

CREATE TABLE import_batches (
    id CHAR(36) NOT NULL,
    source_filename VARCHAR(255) NOT NULL,
    entity_type VARCHAR(60) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'uploaded',
    total_rows INT UNSIGNED NOT NULL DEFAULT 0,
    valid_rows INT UNSIGNED NOT NULL DEFAULT 0,
    invalid_rows INT UNSIGNED NOT NULL DEFAULT 0,
    approved_by CHAR(36) NULL,
    approved_at DATETIME(6) NULL,
    started_at DATETIME(6) NULL,
    completed_at DATETIME(6) NULL,
    created_by CHAR(36) NOT NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    KEY ix_import_batches_status (status),
    KEY ix_import_batches_entity (entity_type),
    CONSTRAINT fk_import_batches_approved_by FOREIGN KEY (approved_by) REFERENCES users (id),
    CONSTRAINT fk_import_batches_created_by FOREIGN KEY (created_by) REFERENCES users (id)
) ENGINE = InnoDB;

CREATE TABLE import_rows (
    id CHAR(36) NOT NULL,
    batch_id CHAR(36) NOT NULL,
    row_number INT UNSIGNED NOT NULL,
    row_data_json JSON NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'pending',
    validation_errors TEXT NULL,
    target_entity_id CHAR(36) NULL,
    processed_at DATETIME(6) NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_import_rows_batch_row (batch_id, row_number),
    KEY ix_import_rows_status (batch_id, status),
    CONSTRAINT fk_import_rows_batch FOREIGN KEY (batch_id) REFERENCES import_batches (id)
) ENGINE = InnoDB;

CREATE TABLE audit_logs (
    id CHAR(36) NOT NULL,
    user_id CHAR(36) NULL,
    action VARCHAR(80) NOT NULL,
    entity_type VARCHAR(60) NOT NULL,
    entity_id CHAR(36) NULL,
    request_id CHAR(36) NULL,
    before_json JSON NULL,
    after_json JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    KEY ix_audit_entity (entity_type, entity_id, created_at),
    KEY ix_audit_user_date (user_id, created_at),
    KEY ix_audit_action (action),
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE = InnoDB;

INSERT INTO roles (id, name, description, is_system_role) VALUES
    ('00000000-0000-0000-0000-000000000001', 'Administrator', 'Full system administration and configuration access', 1),
    ('00000000-0000-0000-0000-000000000002', 'Billing Officer', 'Ground-rent billing and payment processing access', 1),
    ('00000000-0000-0000-0000-000000000003', 'Read-Only Auditor', 'Read-only access to records, reports, and audit history', 1),
    ('00000000-0000-0000-0000-000000000004', 'Property Owner', 'Portal access to owned properties, ground-rent bills, documents, and receipts', 1);
