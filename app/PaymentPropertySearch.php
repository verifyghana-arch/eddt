<?php
namespace Srms;
final class PaymentPropertySearch {
 public static function find(string $query,int $page=1): array {
  Auth::staff();$query=trim($query);if(mb_strlen($query)<2)return [];
  $pattern='%'.str_replace(['!','%','_'],['!!','!%','!_'],mb_substr($query,0,100)).'%';
  return Database::all("SELECT p.account_number,p.locality,p.status,(SELECT GROUP_CONCAT(DISTINCT COALESCE(NULLIF(r.organization_name,''),r.full_name) ORDER BY r.full_name SEPARATOR ', ') FROM property_ratepayers o JOIN ratepayers r ON r.id=o.ratepayer_id WHERE o.account_number=p.account_number AND o.relationship_type='owner' AND o.start_date<=CURRENT_DATE AND (o.end_date IS NULL OR o.end_date>CURRENT_DATE)) owners FROM parcel_accounts p WHERE p.account_number LIKE ? ESCAPE '!' OR p.locality LIKE ? ESCAPE '!' OR EXISTS (SELECT 1 FROM property_ratepayers o JOIN ratepayers r ON r.id=o.ratepayer_id WHERE o.account_number=p.account_number AND o.relationship_type='owner' AND o.start_date<=CURRENT_DATE AND (o.end_date IS NULL OR o.end_date>CURRENT_DATE) AND (r.full_name LIKE ? ESCAPE '!' OR r.organization_name LIKE ? ESCAPE '!')) ORDER BY p.account_number LIMIT 21 OFFSET ".((max(1,min(10000,$page))-1)*20),array_fill(0,4,$pattern));
 }
}
