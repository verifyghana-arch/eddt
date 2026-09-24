<?php
namespace Srms;
final class Audit {
 public static function log(string $action,string $type,?string $id=null,?array $before=null,?array $after=null): void {
  foreach(['password','password_hash','csrf','request_key'] as $key){unset($before[$key],$after[$key]);}
  Database::insert('audit_logs',['user_id'=>Auth::user()['id']??null,'action'=>$action,'entity_type'=>$type,'entity_id'=>$id,'request_id'=>uuid(),'before_json'=>$before?json_encode($before,JSON_THROW_ON_ERROR):null,'after_json'=>$after?json_encode($after,JSON_THROW_ON_ERROR):null,'ip_address'=>substr($_SERVER['REMOTE_ADDR']??'cli',0,45),'user_agent'=>substr($_SERVER['HTTP_USER_AGENT']??'CLI',0,500)]);
 }
}
