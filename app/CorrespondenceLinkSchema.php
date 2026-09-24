<?php
namespace Srms;
final class CorrespondenceLinkSchema {
 public const VERSION='010_correspondence_links';
 public static function migrate():void {
  if(Database::scalar('SELECT COUNT(*) FROM schema_migrations WHERE version=?',[self::VERSION]))return;
  Database::query("UPDATE correspondence c JOIN payments p ON p.id=JSON_UNQUOTE(JSON_EXTRACT(c.content_variables_json,'$.payment.id')) AND p.account_number=c.account_number SET c.payment_id=p.id WHERE c.payment_id IS NULL AND c.correspondence_type='receipt'");
  foreach(['correspondence'=>['fk_correspondence_payment','payment_id','payments'],'correspondence_batch_items'=>['fk_batch_correspondence','correspondence_id','correspondence']] as $table=>[$name,$column,$target]){
   if(!Database::scalar('SELECT COUNT(*) FROM information_schema.table_constraints WHERE constraint_schema=DATABASE() AND table_name=? AND constraint_name=?',[$table,$name]))Database::query("ALTER TABLE $table ADD CONSTRAINT $name FOREIGN KEY ($column) REFERENCES $target(id)");
  }
  Database::query('INSERT INTO schema_migrations VALUES (?,CURRENT_TIMESTAMP)',[self::VERSION]);
 }
}
