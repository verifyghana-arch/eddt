<?php
namespace Srms;
/** Compatible protection for issued identifiers before the unified-key migration. */
final class PermanentNumbers {
 public static function migrate(): void {
  if(Database::scalar("SELECT COUNT(*) FROM schema_migrations WHERE version='004b_permanent_numbers'"))return;
  foreach(['parcels'=>'parcel_number','properties'=>'property_number','accounts'=>'account_number'] as $table=>$column){
   foreach(['update','delete'] as $event){
    $name='permanent_'.$table.'_'.$event;
    if(Database::scalar('SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() AND TRIGGER_NAME=?',[$name]))continue;
    $body=$event==='update'
     ?"BEGIN IF NOT (BINARY NEW.$column <=> BINARY OLD.$column) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Account numbers are permanent once issued and cannot be changed.'; END IF; END"
     :"SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Issued Account numbers cannot be deleted or reused. Archive the record instead.'";
    Database::query("CREATE TRIGGER $name BEFORE ".strtoupper($event)." ON $table FOR EACH ROW $body");
   }
  }
  Database::query("INSERT INTO schema_migrations VALUES ('004b_permanent_numbers',CURRENT_TIMESTAMP)");
 }
}