<?php
namespace Srms;
final class PhoneIdentity {
 public static function claim(string $number,string $type,string $holder): void {$old=Database::one('SELECT * FROM phone_identities WHERE phone_number=? FOR UPDATE',[$number]);if($old){if($old['holder_type']!==$type||$old['holder_id']!==$holder)throw new \DomainException('Phone number is already assigned to another identity.');return;}Database::query('INSERT INTO phone_identities VALUES (?,?,?)',[$number,$type,$holder]);}
}
