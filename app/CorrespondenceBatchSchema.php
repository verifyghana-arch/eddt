<?php
namespace Srms;
final class CorrespondenceBatchSchema {
 public static function migrate(): void {
  if(Database::scalar("SELECT COUNT(*) FROM schema_migrations WHERE version='006_correspondence_batches'"))return;
  Database::query("CREATE TABLE IF NOT EXISTS correspondence_batches (id CHAR(36) PRIMARY KEY,request_key CHAR(36) NOT NULL UNIQUE,correspondence_type VARCHAR(30) NOT NULL,parameters_json LONGTEXT NOT NULL,status VARCHAR(30) NOT NULL DEFAULT 'ready',created_by CHAR(36) NOT NULL,created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(created_by) REFERENCES users(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
  Database::query("CREATE TABLE IF NOT EXISTS correspondence_batch_items (id CHAR(36) PRIMARY KEY,batch_id CHAR(36) NOT NULL,account_number CHAR(12) COLLATE utf8mb4_bin NOT NULL,bill_id CHAR(36) NULL,document_id CHAR(36) NULL,status VARCHAR(30) NOT NULL DEFAULT 'pending',error_message VARCHAR(500) NULL,created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,UNIQUE(batch_id,account_number),FOREIGN KEY(batch_id) REFERENCES correspondence_batches(id),FOREIGN KEY(account_number) REFERENCES parcel_accounts(account_number),FOREIGN KEY(bill_id) REFERENCES ground_rent_bills(id),FOREIGN KEY(document_id) REFERENCES documents(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
  Database::query("INSERT INTO schema_migrations VALUES ('006_correspondence_batches',CURRENT_TIMESTAMP)");
 }
}
