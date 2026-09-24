<?php
namespace Srms;
final class Auth {
 public static function user(): ?array {
  if(empty($_SESSION['user_id'])) return null;
  $u=Database::one('SELECT u.*, r.name role_name FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=? AND u.is_active=1',[$_SESSION['user_id']]);
  if(!$u || (int)$u['session_version']!==(int)($_SESSION['session_version']??0)) return null;
  return $u;
 }
 public static function phone(string $value): string { $v=preg_replace('/[\s().-]/','',trim($value)); if(str_starts_with($v,'00'))$v='+'.substr($v,2); if(preg_match('/^0\d{9}$/',$v))$v='+233'.substr($v,1); if(!preg_match('/^\+[1-9]\d{7,14}$/',$v))throw new \DomainException('Enter a valid phone number including country code.'); return $v; }
 public static function login(string $phone,string $password): bool {
  $phone=self::phone($phone);$hash=hash('sha256',$phone);$ip=substr($_SERVER['REMOTE_ADDR']??'cli',0,45);$cutoff=date('Y-m-d H:i:s',time()-900);
  if((int)Database::scalar('SELECT COUNT(*) FROM login_attempts WHERE (identity_hash=? OR ip_address=?) AND attempted_at>?',[$hash,$ip,$cutoff])>=10) throw new \DomainException('Too many attempts. Please wait 15 minutes.');
  $u=Database::one('SELECT DISTINCT u.* FROM users u LEFT JOIN user_phone_numbers p ON p.user_id=u.id LEFT JOIN ratepayer_phone_numbers rp ON rp.ratepayer_id=u.ratepayer_id WHERE u.is_active=1 AND ((p.phone_number=? AND p.is_verified=1 AND p.can_login=1) OR (rp.phone_number=? AND rp.is_verified=1 AND rp.can_login=1))',[$phone,$phone]);
  if(!$u || !password_verify($password,$u['password_hash'])){ Database::insert('login_attempts',['identity_hash'=>$hash,'ip_address'=>$ip,'attempted_at'=>date('Y-m-d H:i:s')]); password_verify($password,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.'); return false; }
  session_regenerate_id(true);$_SESSION=['user_id'=>$u['id'],'session_version'=>$u['session_version'],'csrf'=>bin2hex(random_bytes(32))];
  Database::update('users',$u['id'],['last_login_at'=>date('Y-m-d H:i:s')]); Database::query('DELETE FROM login_attempts WHERE identity_hash=?',[$hash]); Audit::log('login','users',$u['id']);return true;
 }
 public static function role(): string {return self::user()['role_name']??'';}
 public static function admin(): void {if(self::role()!=='Administrator')throw new \DomainException('Administrator access required.');}
 public static function staff(): void {if(!in_array(self::role(),['Administrator','Billing Officer'],true))throw new \DomainException('Staff write access required.');}
 public static function owner(): bool {return self::role()==='Property Owner';}
 public static function property(string $id): void {if(!Database::one('SELECT id FROM properties WHERE id=?',[$id]))throw new \DomainException('Property not found.');if(self::owner() && !Database::one("SELECT id FROM property_ratepayers WHERE account_number=? AND ratepayer_id=? AND relationship_type='owner' AND start_date<=? AND (end_date IS NULL OR end_date>?)",[$id,self::user()['ratepayer_id'],today(),today()]))throw new \DomainException('Access denied.');}
 public static function account(string $id): array {$a=Database::one('SELECT * FROM accounts WHERE id=?',[$id])??throw new \DomainException('Account not found.');self::property($a['account_number']);return $a;}
 public static function password(string $value): string {if(strlen($value)<12 || strlen($value)>72)throw new \DomainException('Use a password between 12 and 72 characters.');return password_hash($value,PASSWORD_DEFAULT);}
 public static function changePassword(string $old,string $new): void {$u=self::user();if(!password_verify($old,$u['password_hash']))throw new \DomainException('Current password is incorrect.');Database::update('users',$u['id'],['password_hash'=>self::password($new),'must_change_password'=>0,'session_version'=>$u['session_version']+1]);$_SESSION['session_version']=$u['session_version']+1;session_regenerate_id(true);Audit::log('password_changed','users',$u['id']);}
}
