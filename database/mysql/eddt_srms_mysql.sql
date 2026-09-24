-- EDDT Spatial Revenue Management System - clean MySQL/XAMPP schema
-- Import into a NEW EMPTY database using phpMyAdmin or the mysql command-line client.
-- Includes schema, roles, migration markers and application defaults only.
-- No owners, properties, financial records, user accounts or passwords are included.
-- No DROP, TRUNCATE or replacement statements. Do not import into an existing application database.
-- Validated against the local XAMPP MariaDB server; separate Oracle MySQL validation is not claimed.

SET NAMES utf8mb4;
SET @EDDT_OLD_SQL_MODE = @@SQL_MODE;
SET SQL_MODE = 'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION,NO_BACKSLASH_ESCAPES';
SET @EDDT_OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE `audit_logs` (
  `id` char(36) NOT NULL,
  `user_id` char(36) DEFAULT NULL,
  `action` varchar(80) NOT NULL,
  `entity_type` varchar(60) NOT NULL,
  `entity_id` char(36) DEFAULT NULL,
  `request_id` char(36) DEFAULT NULL,
  `before_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`before_json`)),
  `after_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`after_json`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  PRIMARY KEY (`id`),
  KEY `ix_audit_entity` (`entity_type`,`entity_id`,`created_at`),
  KEY `ix_audit_user_date` (`user_id`,`created_at`),
  KEY `ix_audit_action` (`action`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `correspondence` (
  `id` char(36) NOT NULL,
  `correspondence_type` varchar(40) NOT NULL,
  `ratepayer_id` char(36) DEFAULT NULL,
  `bill_id` char(36) DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `printed_at` datetime(6) NOT NULL,
  `period_from` date DEFAULT NULL,
  `period_to` date DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `content_variables_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`content_variables_json`)),
  `generated_document_id` char(36) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'generated',
  `created_by` char(36) NOT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `template_version` varchar(30) NOT NULL DEFAULT '1',
  `account_number` char(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_correspondence_reference` (`reference_number`),
  KEY `ix_correspondence_type_date` (`correspondence_type`,`printed_at`),
  KEY `ix_correspondence_ratepayer` (`ratepayer_id`),
  KEY `ix_correspondence_bill` (`bill_id`),
  KEY `fk_correspondence_created_by` (`created_by`),
  KEY `fk_correspondence_document` (`generated_document_id`),
  KEY `fk_correspondence_canonical_account` (`account_number`),
  CONSTRAINT `fk_correspondence_bill` FOREIGN KEY (`bill_id`) REFERENCES `ground_rent_bills` (`id`),
  CONSTRAINT `fk_correspondence_canonical_account` FOREIGN KEY (`account_number`) REFERENCES `parcel_accounts` (`account_number`),
  CONSTRAINT `fk_correspondence_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_correspondence_document` FOREIGN KEY (`generated_document_id`) REFERENCES `documents` (`id`),
  CONSTRAINT `fk_correspondence_ratepayer` FOREIGN KEY (`ratepayer_id`) REFERENCES `ratepayers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `correspondence_batches` (
  `id` char(36) NOT NULL,
  `request_key` char(36) NOT NULL,
  `correspondence_type` varchar(30) NOT NULL,
  `parameters_json` longtext NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'ready',
  `created_by` char(36) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `request_key` (`request_key`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `correspondence_batches_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `correspondence_batch_items` (
  `id` char(36) NOT NULL,
  `batch_id` char(36) NOT NULL,
  `account_number` char(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `bill_id` char(36) DEFAULT NULL,
  `document_id` char(36) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `error_message` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `batch_id` (`batch_id`,`account_number`),
  KEY `account_number` (`account_number`),
  KEY `bill_id` (`bill_id`),
  KEY `document_id` (`document_id`),
  CONSTRAINT `correspondence_batch_items_ibfk_1` FOREIGN KEY (`batch_id`) REFERENCES `correspondence_batches` (`id`),
  CONSTRAINT `correspondence_batch_items_ibfk_2` FOREIGN KEY (`account_number`) REFERENCES `parcel_accounts` (`account_number`),
  CONSTRAINT `correspondence_batch_items_ibfk_3` FOREIGN KEY (`bill_id`) REFERENCES `ground_rent_bills` (`id`),
  CONSTRAINT `correspondence_batch_items_ibfk_4` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `documents` (
  `id` char(36) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `storage_key` varchar(500) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `file_size_bytes` bigint(20) unsigned NOT NULL,
  `sha256_hash` char(64) NOT NULL,
  `document_type` varchar(80) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `version_number` int(10) unsigned NOT NULL DEFAULT 1,
  `is_current_version` tinyint(1) NOT NULL DEFAULT 1,
  `uploaded_by` char(36) NOT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6),
  `owner_visible` tinyint(4) NOT NULL DEFAULT 0,
  `version_group_id` char(36) DEFAULT NULL,
  `previous_version_id` char(36) DEFAULT NULL,
  `account_number` char(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_documents_storage_key` (`storage_key`),
  KEY `ix_documents_hash` (`sha256_hash`),
  KEY `ix_documents_type` (`document_type`),
  KEY `ix_documents_uploader` (`uploaded_by`),
  KEY `fk_documents_canonical_account` (`account_number`),
  CONSTRAINT `fk_documents_canonical_account` FOREIGN KEY (`account_number`) REFERENCES `parcel_accounts` (`account_number`),
  CONSTRAINT `fk_documents_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `document_links` (
  `id` char(36) NOT NULL,
  `document_id` char(36) NOT NULL,
  `entity_type` varchar(40) NOT NULL,
  `entity_id` char(36) NOT NULL,
  `link_description` varchar(255) DEFAULT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `account_number` char(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_document_links` (`document_id`,`entity_type`,`entity_id`),
  KEY `ix_document_links_entity` (`entity_type`,`entity_id`),
  KEY `fk_document_links_canonical_account` (`account_number`),
  CONSTRAINT `fk_document_links_canonical_account` FOREIGN KEY (`account_number`) REFERENCES `parcel_accounts` (`account_number`),
  CONSTRAINT `fk_document_links_document` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `financial_adjustments` (
  `id` char(36) NOT NULL,
  `bill_id` char(36) NOT NULL,
  `adjustment_type` varchar(30) NOT NULL,
  `amount` decimal(19,4) NOT NULL,
  `reason` varchar(500) NOT NULL,
  `created_by` char(36) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `bill_id` (`bill_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `financial_adjustments_ibfk_1` FOREIGN KEY (`bill_id`) REFERENCES `ground_rent_bills` (`id`),
  CONSTRAINT `financial_adjustments_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ground_rent_assessments` (
  `id` char(36) NOT NULL,
  `assessment_year` smallint(5) unsigned NOT NULL,
  `annual_amount` decimal(19,4) NOT NULL,
  `currency` char(3) NOT NULL DEFAULT 'GHS',
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `approved_by` char(36) DEFAULT NULL,
  `approved_at` datetime(6) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6),
  `account_number` char(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ground_rent_assessments_canonical_year` (`account_number`,`assessment_year`),
  KEY `ix_assessments_year_status` (`assessment_year`,`status`),
  KEY `fk_assessments_approved_by` (`approved_by`),
  CONSTRAINT `fk_assessments_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_ground_rent_assessments_canonical_account` FOREIGN KEY (`account_number`) REFERENCES `parcel_accounts` (`account_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ground_rent_bills` (
  `id` char(36) NOT NULL,
  `bill_number` varchar(100) NOT NULL,
  `assessment_id` char(36) DEFAULT NULL,
  `billing_year` smallint(5) unsigned NOT NULL,
  `issue_date` date NOT NULL,
  `due_date` date NOT NULL,
  `notice_printed_at` datetime(6) DEFAULT NULL,
  `payment_deadline` date DEFAULT NULL,
  `principal_amount` decimal(19,4) NOT NULL,
  `penalty_amount` decimal(19,4) NOT NULL DEFAULT 0.0000,
  `adjustment_amount` decimal(19,4) NOT NULL DEFAULT 0.0000,
  `paid_amount` decimal(19,4) NOT NULL DEFAULT 0.0000,
  `currency` char(3) NOT NULL DEFAULT 'GHS',
  `status` varchar(30) NOT NULL DEFAULT 'issued',
  `demand_notice_path` varchar(500) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` char(36) NOT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6),
  `opening_balance` tinyint(4) NOT NULL DEFAULT 0,
  `account_number` char(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_bills_number` (`bill_number`),
  UNIQUE KEY `uq_ground_rent_bills_canonical_year` (`account_number`,`billing_year`),
  KEY `ix_bills_due_status` (`due_date`,`status`),
  KEY `ix_bills_year` (`billing_year`),
  KEY `fk_bills_assessment` (`assessment_id`),
  KEY `fk_bills_created_by` (`created_by`),
  CONSTRAINT `fk_bills_assessment` FOREIGN KEY (`assessment_id`) REFERENCES `ground_rent_assessments` (`id`),
  CONSTRAINT `fk_bills_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_ground_rent_bills_canonical_account` FOREIGN KEY (`account_number`) REFERENCES `parcel_accounts` (`account_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `import_batches` (
  `id` char(36) NOT NULL,
  `source_filename` varchar(255) NOT NULL,
  `entity_type` varchar(60) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'uploaded',
  `total_rows` int(10) unsigned NOT NULL DEFAULT 0,
  `valid_rows` int(10) unsigned NOT NULL DEFAULT 0,
  `invalid_rows` int(10) unsigned NOT NULL DEFAULT 0,
  `approved_by` char(36) DEFAULT NULL,
  `approved_at` datetime(6) DEFAULT NULL,
  `started_at` datetime(6) DEFAULT NULL,
  `completed_at` datetime(6) DEFAULT NULL,
  `created_by` char(36) NOT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `file_hash` char(64) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_import_batches_status` (`status`),
  KEY `ix_import_batches_entity` (`entity_type`),
  KEY `fk_import_batches_approved_by` (`approved_by`),
  KEY `fk_import_batches_created_by` (`created_by`),
  CONSTRAINT `fk_import_batches_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_import_batches_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `import_rows` (
  `id` char(36) NOT NULL,
  `batch_id` char(36) NOT NULL,
  `row_number` int(10) unsigned NOT NULL,
  `row_data_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`row_data_json`)),
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `validation_errors` text DEFAULT NULL,
  `target_entity_id` char(36) DEFAULT NULL,
  `processed_at` datetime(6) DEFAULT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_import_rows_batch_row` (`batch_id`,`row_number`),
  KEY `ix_import_rows_status` (`batch_id`,`status`),
  CONSTRAINT `fk_import_rows_batch` FOREIGN KEY (`batch_id`) REFERENCES `import_batches` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `login_attempts` (
  `id` char(36) NOT NULL,
  `identity_hash` char(64) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempted_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_login_identity` (`identity_hash`,`attempted_at`),
  KEY `ix_login_ip` (`ip_address`,`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ownership_history` (
  `id` char(36) NOT NULL,
  `previous_ratepayer_id` char(36) DEFAULT NULL,
  `new_ratepayer_id` char(36) NOT NULL,
  `transfer_date` date NOT NULL,
  `transfer_reference` varchar(120) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `recorded_by` char(36) NOT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `account_number` char(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_ownership_previous_ratepayer` (`previous_ratepayer_id`),
  KEY `fk_ownership_new_ratepayer` (`new_ratepayer_id`),
  KEY `fk_ownership_recorded_by` (`recorded_by`),
  KEY `fk_ownership_history_canonical_account` (`account_number`),
  CONSTRAINT `fk_ownership_history_canonical_account` FOREIGN KEY (`account_number`) REFERENCES `parcel_accounts` (`account_number`),
  CONSTRAINT `fk_ownership_new_ratepayer` FOREIGN KEY (`new_ratepayer_id`) REFERENCES `ratepayers` (`id`),
  CONSTRAINT `fk_ownership_previous_ratepayer` FOREIGN KEY (`previous_ratepayer_id`) REFERENCES `ratepayers` (`id`),
  CONSTRAINT `fk_ownership_recorded_by` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `parcel_accounts` (
  `account_number` char(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `division_code` char(2) GENERATED ALWAYS AS (substr(`account_number`,5,2)) STORED,
  `block_code` char(3) GENERATED ALWAYS AS (substr(`account_number`,7,3)) STORED,
  `parcel_code` char(3) GENERATED ALWAYS AS (substr(`account_number`,10,3)) STORED,
  `spatial_layer_id` char(36) DEFAULT NULL,
  `land_id` varchar(120) DEFAULT NULL,
  `locality` varchar(150) DEFAULT NULL,
  `zoning` varchar(120) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `boundary_geojson` longtext DEFAULT NULL,
  `plot_size` decimal(19,4) DEFAULT NULL,
  `plot_size_unit` varchar(30) DEFAULT NULL,
  `land_description` varchar(500) DEFAULT NULL,
  `property_type` varchar(80) DEFAULT NULL,
  `address_line` varchar(255) DEFAULT NULL,
  `digital_address` varchar(120) DEFAULT NULL,
  `assessed_ground_rent` decimal(19,4) DEFAULT NULL,
  `assessment_currency` char(3) NOT NULL DEFAULT 'GHS',
  `is_exempt` tinyint(4) NOT NULL DEFAULT 0,
  `exemption_reason` varchar(500) DEFAULT NULL,
  `primary_ratepayer_id` char(36) DEFAULT NULL,
  `credit_amount` decimal(19,4) NOT NULL DEFAULT 0.0000,
  `opened_on` date NOT NULL,
  `closed_on` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6),
  PRIMARY KEY (`account_number`),
  UNIQUE KEY `land_id` (`land_id`),
  KEY `spatial_layer_id` (`spatial_layer_id`),
  KEY `primary_ratepayer_id` (`primary_ratepayer_id`),
  KEY `ix_parcel_division_block` (`division_code`,`block_code`),
  CONSTRAINT `parcel_accounts_ibfk_1` FOREIGN KEY (`spatial_layer_id`) REFERENCES `spatial_layers` (`id`),
  CONSTRAINT `parcel_accounts_ibfk_2` FOREIGN KEY (`primary_ratepayer_id`) REFERENCES `ratepayers` (`id`),
  CONSTRAINT `ck_account_format` CHECK (`account_number` regexp cast('^EDDT[0-9]{8}$' as char charset binary))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `payments` (
  `id` char(36) NOT NULL,
  `receipt_number` varchar(100) NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(19,4) NOT NULL,
  `currency` char(3) NOT NULL DEFAULT 'GHS',
  `payment_method` varchar(40) NOT NULL,
  `external_reference` varchar(150) DEFAULT NULL,
  `payer_name` varchar(180) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'posted',
  `recorded_by` char(36) NOT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6),
  `request_key` char(36) DEFAULT NULL,
  `reversal_reason` varchar(500) DEFAULT NULL,
  `reversed_by` char(36) DEFAULT NULL,
  `reversed_at` datetime DEFAULT NULL,
  `account_number` char(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_payments_receipt` (`receipt_number`),
  UNIQUE KEY `request_key` (`request_key`),
  KEY `ix_payments_external_reference` (`external_reference`),
  KEY `fk_payments_recorded_by` (`recorded_by`),
  KEY `fk_payments_canonical_account` (`account_number`),
  CONSTRAINT `fk_payments_canonical_account` FOREIGN KEY (`account_number`) REFERENCES `parcel_accounts` (`account_number`),
  CONSTRAINT `fk_payments_recorded_by` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `payment_allocations` (
  `id` char(36) NOT NULL,
  `payment_id` char(36) NOT NULL,
  `bill_id` char(36) NOT NULL,
  `amount` decimal(19,4) NOT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_payment_bill` (`payment_id`,`bill_id`),
  KEY `ix_allocations_bill` (`bill_id`),
  CONSTRAINT `fk_allocations_bill` FOREIGN KEY (`bill_id`) REFERENCES `ground_rent_bills` (`id`),
  CONSTRAINT `fk_allocations_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `phone_identities` (
  `phone_number` varchar(40) NOT NULL,
  `holder_type` varchar(20) NOT NULL,
  `holder_id` char(36) NOT NULL,
  PRIMARY KEY (`phone_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `property_photos` (
  `id` char(36) NOT NULL,
  `document_id` char(36) NOT NULL,
  `photo_type` varchar(50) DEFAULT NULL,
  `caption` varchar(255) DEFAULT NULL,
  `taken_at` datetime(6) DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` char(36) NOT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `account_number` char(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_property_photos_document` (`document_id`),
  KEY `fk_property_photos_created_by` (`created_by`),
  KEY `fk_property_photos_canonical_account` (`account_number`),
  CONSTRAINT `fk_property_photos_canonical_account` FOREIGN KEY (`account_number`) REFERENCES `parcel_accounts` (`account_number`),
  CONSTRAINT `fk_property_photos_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_property_photos_document` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `property_ratepayers` (
  `id` char(36) NOT NULL,
  `ratepayer_id` char(36) NOT NULL,
  `relationship_type` varchar(40) NOT NULL DEFAULT 'owner',
  `ownership_percentage` decimal(7,4) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `account_number` char(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_property_ratepayers_ratepayer` (`ratepayer_id`),
  KEY `ix_property_ratepayers_dates` (`start_date`,`end_date`),
  KEY `ix_property_ratepayers_relationship` (`relationship_type`),
  KEY `fk_property_ratepayers_canonical_account` (`account_number`),
  CONSTRAINT `fk_property_ratepayers_canonical_account` FOREIGN KEY (`account_number`) REFERENCES `parcel_accounts` (`account_number`),
  CONSTRAINT `fk_property_ratepayers_ratepayer` FOREIGN KEY (`ratepayer_id`) REFERENCES `ratepayers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ratepayers` (
  `id` char(36) NOT NULL,
  `ratepayer_number` varchar(100) NOT NULL,
  `ratepayer_type` varchar(40) NOT NULL DEFAULT 'individual',
  `full_name` varchar(180) NOT NULL,
  `organization_name` varchar(180) DEFAULT NULL,
  `company_registration_number` varchar(120) DEFAULT NULL,
  `national_id` varchar(100) DEFAULT NULL,
  `email` varchar(254) DEFAULT NULL,
  `postal_address` varchar(500) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ratepayers_number` (`ratepayer_number`),
  UNIQUE KEY `uq_ratepayers_company_registration` (`company_registration_number`),
  KEY `ix_ratepayers_name` (`full_name`),
  KEY `ix_ratepayers_national_id` (`national_id`),
  KEY `ix_ratepayers_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ratepayer_phone_numbers` (
  `id` char(36) NOT NULL,
  `ratepayer_id` char(36) NOT NULL,
  `phone_number` varchar(40) NOT NULL,
  `phone_label` varchar(40) DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `can_login` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ratepayer_phone_number` (`phone_number`),
  KEY `ix_ratepayer_phones_ratepayer` (`ratepayer_id`),
  KEY `ix_ratepayer_phones_login` (`can_login`,`is_verified`),
  CONSTRAINT `fk_ratepayer_phones_ratepayer` FOREIGN KEY (`ratepayer_id`) REFERENCES `ratepayers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `roles` (
  `id` char(36) NOT NULL,
  `name` varchar(80) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_system_role` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_roles_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `schema_migrations` (
  `version` varchar(100) NOT NULL,
  `applied_at` datetime NOT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `spatial_layers` (
  `id` char(36) NOT NULL,
  `name` varchar(150) NOT NULL,
  `layer_type` varchar(40) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `source_reference` varchar(255) DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `is_visible_by_default` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_spatial_layers_name` (`name`),
  KEY `ix_spatial_layers_type` (`layer_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
  `id` char(36) NOT NULL,
  `role_id` char(36) NOT NULL,
  `ratepayer_id` char(36) DEFAULT NULL,
  `username` varchar(120) DEFAULT NULL,
  `full_name` varchar(180) NOT NULL,
  `email` varchar(254) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` datetime(6) DEFAULT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6),
  `must_change_password` tinyint(4) NOT NULL DEFAULT 1,
  `session_version` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_username` (`username`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `ix_users_role` (`role_id`),
  KEY `ix_users_ratepayer` (`ratepayer_id`),
  CONSTRAINT `fk_users_ratepayer` FOREIGN KEY (`ratepayer_id`) REFERENCES `ratepayers` (`id`),
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_phone_numbers` (
  `id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `phone_number` varchar(40) NOT NULL,
  `phone_label` varchar(40) DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `can_login` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_phone_number` (`phone_number`),
  KEY `ix_user_phones_user` (`user_id`),
  KEY `ix_user_phones_login` (`can_login`,`is_verified`),
  CONSTRAINT `fk_user_phones_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE ALGORITHM=MERGE VIEW `properties` AS SELECT a.*,a.account_number AS id,a.account_number AS property_number,a.account_number AS parcel_number,a.account_number AS parcel_id,a.account_number AS land_account_number FROM parcel_accounts a;

CREATE ALGORITHM=MERGE VIEW `parcels` AS SELECT a.*,a.account_number AS id,a.account_number AS property_number,a.account_number AS parcel_number,a.account_number AS parcel_id,a.account_number AS land_account_number FROM parcel_accounts a;

CREATE ALGORITHM=MERGE VIEW `accounts` AS SELECT a.*,a.account_number AS id,a.account_number AS property_number,a.account_number AS parcel_number,a.account_number AS parcel_id,a.account_number AS land_account_number FROM parcel_accounts a;

DELIMITER $$
CREATE TRIGGER `permanent_account_insert` BEFORE INSERT ON `parcel_accounts` FOR EACH ROW BEGIN IF NEW.account_number NOT REGEXP BINARY '^EDDT[0-9]{8}$' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid Account number'; END IF; END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER `permanent_account_update` BEFORE UPDATE ON `parcel_accounts` FOR EACH ROW BEGIN IF BINARY NEW.account_number <> BINARY OLD.account_number THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Account numbers are permanent and cannot be changed'; END IF; END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER `permanent_account_delete` BEFORE DELETE ON `parcel_accounts` FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Issued Account numbers cannot be deleted or reused; archive the parcel'$$
DELIMITER ;

SET FOREIGN_KEY_CHECKS = @EDDT_OLD_FOREIGN_KEY_CHECKS;

-- Access roles (create the first Administrator with tools/setup.php).
INSERT INTO roles (`id`,`name`,`description`,`is_system_role`,`created_at`,`updated_at`) VALUES ('00000000-0000-0000-0000-000000000001','Administrator','Full system administration and configuration access','1','2026-09-18 23:54:55.730410','2026-09-18 23:54:55.730410');
INSERT INTO roles (`id`,`name`,`description`,`is_system_role`,`created_at`,`updated_at`) VALUES ('00000000-0000-0000-0000-000000000002','Billing Officer','Ground-rent billing and payment processing access','1','2026-09-18 23:54:55.730410','2026-09-18 23:54:55.730410');
INSERT INTO roles (`id`,`name`,`description`,`is_system_role`,`created_at`,`updated_at`) VALUES ('00000000-0000-0000-0000-000000000003','Read-Only Auditor','Read-only access to records, reports, and audit history','1','2026-09-18 23:54:55.730410','2026-09-18 23:54:55.730410');
INSERT INTO roles (`id`,`name`,`description`,`is_system_role`,`created_at`,`updated_at`) VALUES ('00000000-0000-0000-0000-000000000004','Property Owner','Portal access to owned properties, ground-rent bills, documents, and receipts','1','2026-09-18 23:54:55.730410','2026-09-18 23:54:55.730410');

-- Baseline migration tracking.
INSERT INTO schema_migrations (version,applied_at) VALUES ('002_application',CURRENT_TIMESTAMP);
INSERT INTO schema_migrations (version,applied_at) VALUES ('003_identity_templates',CURRENT_TIMESTAMP);
INSERT INTO schema_migrations (version,applied_at) VALUES ('004_property_exemptions',CURRENT_TIMESTAMP);
INSERT INTO schema_migrations (version,applied_at) VALUES ('005_permanent_accounts',CURRENT_TIMESTAMP);
INSERT INTO schema_migrations (version,applied_at) VALUES ('006_correspondence_batches',CURRENT_TIMESTAMP);
INSERT INTO schema_migrations (version,applied_at) VALUES ('007_mysql_workflows',CURRENT_TIMESTAMP);
INSERT INTO schema_migrations (version,applied_at) VALUES ('008_application_rebuild',CURRENT_TIMESTAMP);

-- Application defaults. Review organization contacts before issuing letters.
INSERT INTO settings (setting_key,setting_value) VALUES ('organization_name','East Dadekotopon Development Trust (EDDT)');
INSERT INTO settings (setting_key,setting_value) VALUES ('address','No. 9 Leshie Road, East La, Accra, Ghana');
INSERT INTO settings (setting_key,setting_value) VALUES ('email','admin@eddt.org');
INSERT INTO settings (setting_key,setting_value) VALUES ('phone','');
INSERT INTO settings (setting_key,setting_value) VALUES ('digital_address','GT-0433-5976');
INSERT INTO settings (setting_key,setting_value) VALUES ('currency','GHS');
INSERT INTO settings (setting_key,setting_value) VALUES ('number_prefix','EDDT');
INSERT INTO settings (setting_key,setting_value) VALUES ('upload_mb','15');
INSERT INTO settings (setting_key,setting_value) VALUES ('map_tiles','https://tile.openstreetmap.org/{z}/{x}/{y}.png');
INSERT INTO settings (setting_key,setting_value) VALUES ('map_default_basemap','osm');
INSERT INTO settings (setting_key,setting_value) VALUES ('map_osm_tiles','https://tile.openstreetmap.org/{z}/{x}/{y}.png');
INSERT INTO settings (setting_key,setting_value) VALUES ('map_osm_attribution','© OpenStreetMap contributors');
INSERT INTO settings (setting_key,setting_value) VALUES ('map_esri_tiles','https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}');
INSERT INTO settings (setting_key,setting_value) VALUES ('map_esri_attribution','Tiles © Esri — Source: Esri, Maxar, Earthstar Geographics and the GIS User Community');
INSERT INTO settings (setting_key,setting_value) VALUES ('map_orthophoto_name','EDDT Orthophoto');
INSERT INTO settings (setting_key,setting_value) VALUES ('map_orthophoto_tiles','');
INSERT INTO settings (setting_key,setting_value) VALUES ('map_orthophoto_attribution','East Dadekotopon Development Trust');
INSERT INTO settings (setting_key,setting_value) VALUES ('map_orthophoto_min_zoom','0');
INSERT INTO settings (setting_key,setting_value) VALUES ('map_orthophoto_max_zoom','22');
INSERT INTO settings (setting_key,setting_value) VALUES ('map_orthophoto_opacity','1');
INSERT INTO settings (setting_key,setting_value) VALUES ('map_attribution','© OpenStreetMap contributors');
INSERT INTO settings (setting_key,setting_value) VALUES ('invitation_body','The East Dadekotopon Development Trust is by this letter inviting all lessees or occupants on Tse Addo, Opintin, and La Dadekotopon lands to come to the Trust to rectify, correct and update all land documents. This invitation is for the period {{period_from}} to {{period_to}}.

The Trust will be glad if all tenants, lessees, grantees and occupants take advantage of this period to have updated documents from the land-owning Stool/families and to receive guidance on annual ground rent. The updating process includes data collection, digitization and property imagery to provide credible records for the Land Secretariat, Office of the Administrator of Stool Lands, and Lands Commission.

Kindly come along with your land document (indenture) or a photocopy to the office located at {{address}}.

PLEASE BRING THIS INVITATION OR A PHOTOGRAPH OR PHOTOCOPY OF IT. For enquiries, contact {{phone}} or {{email}} during office hours.');
INSERT INTO settings (setting_key,setting_value) VALUES ('demand_warning','PLEASE NOTE: In accordance with the lease / licence agreement between the Trust / landowners and you, as contained in the lease document (indentures), non-payment of ground rents, whether the land is registered or not, constitutes a breach of contract and legal (court) action will be taken to recover the said rents.');
INSERT INTO settings (setting_key,setting_value) VALUES ('signatory','ADMINISTRATOR EDDT');
INSERT INTO settings (setting_key,setting_value) VALUES ('retention_policy','Retain financial and document history. Archive operational records.');
INSERT INTO settings (setting_key,setting_value) VALUES ('import_max_rows','5000');
INSERT INTO settings (setting_key,setting_value) VALUES ('import_chunk_rows','100');

-- On-demand correspondence records; historical document references are retained.
ALTER TABLE correspondence ADD owner_visible TINYINT NOT NULL DEFAULT 1, ADD payment_id CHAR(36) NULL, ADD snapshot_sha256 CHAR(64) NULL;
ALTER TABLE correspondence_batch_items ADD correspondence_id CHAR(36) NULL;
CREATE TABLE correspondence_events (id CHAR(36) PRIMARY KEY,correspondence_id CHAR(36) NOT NULL,user_id CHAR(36) NOT NULL,user_name VARCHAR(255) NOT NULL,action VARCHAR(20) NOT NULL,request_key CHAR(36) NOT NULL,created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,UNIQUE KEY uq_correspondence_action(correspondence_id,request_key),INDEX ix_correspondence_print(correspondence_id,action,created_at),FOREIGN KEY(correspondence_id) REFERENCES correspondence(id),FOREIGN KEY(user_id) REFERENCES users(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO schema_migrations VALUES ('009_on_demand_correspondence',CURRENT_TIMESTAMP);
ALTER TABLE correspondence ADD CONSTRAINT fk_correspondence_payment FOREIGN KEY(payment_id) REFERENCES payments(id);
ALTER TABLE correspondence_batch_items ADD CONSTRAINT fk_batch_correspondence FOREIGN KEY(correspondence_id) REFERENCES correspondence(id);
INSERT INTO schema_migrations VALUES ('010_correspondence_links',CURRENT_TIMESTAMP);
SET SQL_MODE = @EDDT_OLD_SQL_MODE;
-- Import complete. Configure the app database/storage and provision the first Administrator.
