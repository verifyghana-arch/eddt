<?php
namespace Srms;
final class AccountNumber {
 public static function validate(string $number): string {
  $number=strtoupper(trim($number));
  if(!preg_match('/^EDDT[0-9]{8}$/D',$number))throw new \DomainException('Account number must be EDDT + 2 division digits + 3 block digits + 3 parcel digits (for example EDDT03011010).');
  return $number;
 }
 public static function resolve(string $value,string $legacyType='parcels'): string {
  if(preg_match('/^EDDT[0-9]{8}$/Di',trim($value)))return self::validate($value);
  if(!Database::scalar("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=? AND table_name='legacy_account_keys'",[config('db_name')]))throw new \DomainException('Use the permanent parcel Account number.');$number=Database::scalar('SELECT account_number FROM legacy_account_keys WHERE entity_type=? AND legacy_id=?',[$legacyType,$value]);
  if(!$number)throw new \DomainException('Parcel Account number not found.');return $number;
 }
 public static function parts(string $value): array {$v=self::validate($value);return ['division_code'=>substr($v,4,2),'block_code'=>substr($v,6,3),'parcel_code'=>substr($v,9,3)];}
}