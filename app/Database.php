<?php
namespace Srms;
use PDO;
final class Database {
 private static ?PDO $pdo=null;
 public static function connection(): PDO {return self::$pdo??=new PDO('mysql:host='.config('db_host').';port='.config('db_port').';dbname='.config('db_name').';charset=utf8mb4',config('db_user'),config('db_password'),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);}
 public static function query(string $sql,array $params=[]): \PDOStatement {$s=self::connection()->prepare($sql);$s->execute($params);return $s;}
 public static function all(string $sql,array $params=[]):array{return self::query($sql,$params)->fetchAll();}
 public static function one(string $sql,array $params=[]):?array{return self::query($sql,$params)->fetch()?:null;}
 public static function scalar(string $sql,array $params=[]):mixed{return self::query($sql,$params)->fetchColumn();}
 private static function target(string $table):array {return in_array($table,['parcels','properties','accounts','parcel_accounts'],true)?['parcel_accounts','account_number']:[$table,'id'];}
 public static function insert(string $table,array $data):string {[$table,$key]=self::target($table);if($key==='account_number'){$data[$key]=AccountNumber::validate(require_value($data,$key));unset($data['id']);}else $data=['id'=>$data['id']??uuid()]+$data;$cols=array_keys($data);self::query('INSERT INTO '.$table.' (`'.implode('`,`',$cols).'`) VALUES ('.implode(',',array_fill(0,count($cols),'?')).')',array_values($data));return $data[$key];}
 public static function update(string $table,string $id,array $data):void {[$table,$key]=self::target($table);if($key==='account_number'){$id=AccountNumber::validate($id);if(isset($data['account_number'])&&$data['account_number']!==$id)throw new \DomainException('Account numbers are permanent and cannot be changed.');}self::query('UPDATE '.$table.' SET '.implode(',',array_map(fn($k)=>'`'.$k.'`=?',array_keys($data))).' WHERE '.$key.'=?',[...array_values($data),$id]);}
 public static function transaction(callable $fn):mixed {$db=self::connection();$nested=$db->inTransaction();$savepoint='sp_'.bin2hex(random_bytes(6));if($nested)$db->exec('SAVEPOINT '.$savepoint);else $db->beginTransaction();try{$r=$fn();if($nested)$db->exec('RELEASE SAVEPOINT '.$savepoint);else $db->commit();return $r;}catch(\Throwable $e){if($db->inTransaction()){if($nested)$db->exec('ROLLBACK TO SAVEPOINT '.$savepoint);else $db->rollBack();}throw $e;}}
 public static function page(string $sql,int $page,int $size=25):string{return $sql.' LIMIT '.max(1,min(100,$size)).' OFFSET '.(max(0,$page-1)*$size);}
 public static function lock(string $table,string $id):array {[$table,$key]=self::target($table);if($key==='account_number')$id=AccountNumber::validate($id);return self::one('SELECT *'.($key==='account_number'?',account_number AS id':'').' FROM '.$table.' WHERE '.$key.'=? FOR UPDATE',[$id])??throw new \DomainException('Record not found.');}
}