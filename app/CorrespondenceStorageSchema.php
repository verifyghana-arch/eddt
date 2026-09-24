<?php
namespace Srms;
final class CorrespondenceStorageSchema {
 public const VERSION='009_on_demand_correspondence';
 public static function migrate():void {
  if(Database::scalar('SELECT COUNT(*) FROM schema_migrations WHERE version=?',[self::VERSION])){CorrespondenceLinkSchema::migrate();return;}
  foreach(['owner_visible'=>'TINYINT NOT NULL DEFAULT 1','payment_id'=>'CHAR(36) NULL','snapshot_sha256'=>'CHAR(64) NULL'] as $name=>$definition)self::column('correspondence',$name,$definition);
  self::column('correspondence_batch_items','correspondence_id','CHAR(36) NULL');
  Database::query("CREATE TABLE IF NOT EXISTS correspondence_events (id CHAR(36) PRIMARY KEY,correspondence_id CHAR(36) NOT NULL,user_id CHAR(36) NOT NULL,user_name VARCHAR(255) NOT NULL,action VARCHAR(20) NOT NULL,request_key CHAR(36) NOT NULL,created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,UNIQUE KEY uq_correspondence_action(correspondence_id,request_key),INDEX ix_correspondence_print(correspondence_id,action,created_at),FOREIGN KEY(correspondence_id) REFERENCES correspondence(id),FOREIGN KEY(user_id) REFERENCES users(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
  Database::query('UPDATE correspondence c JOIN documents d ON d.id=c.generated_document_id SET c.owner_visible=d.owner_visible');
  Database::query('UPDATE correspondence_batch_items i JOIN correspondence c ON c.generated_document_id=i.document_id SET i.correspondence_id=c.id WHERE i.correspondence_id IS NULL');
  Database::query('INSERT INTO schema_migrations VALUES (?,CURRENT_TIMESTAMP)',[self::VERSION]);CorrespondenceLinkSchema::migrate();
 }
 private static function column(string $table,string $name,string $definition):void {
  if(!Database::scalar('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=? AND column_name=?',[$table,$name]))Database::query("ALTER TABLE $table ADD COLUMN $name $definition");
 }
}
