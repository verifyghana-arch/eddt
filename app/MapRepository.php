<?php
namespace Srms;
final class MapRepository {
 public static function features(): array {
  [$scope,$params]=Reports::scope();
  $rows=Database::all("SELECT p.id,p.property_number,p.land_id,p.locality,p.status,p.parcel_id,pa.parcel_number,pa.latitude,pa.longitude,pa.boundary_geojson,pa.spatial_layer_id,l.name layer_name,l.is_visible_by_default FROM properties p JOIN parcels pa ON pa.id=p.parcel_id LEFT JOIN spatial_layers l ON l.id=pa.spatial_layer_id WHERE $scope AND (l.is_active=1 OR l.id IS NULL)",$params);
  $owners=[];$ownerRows=Database::all("SELECT o.account_number,r.full_name,r.organization_name FROM property_ratepayers o JOIN properties p ON p.id=o.account_number JOIN ratepayers r ON r.id=o.ratepayer_id WHERE $scope AND o.relationship_type='owner' AND o.start_date<=? AND (o.end_date IS NULL OR o.end_date>?)",[...$params,today(),today()]);foreach($ownerRows as $owner)$owners[$owner['account_number']][]=($owner['organization_name']?:$owner['full_name']);
  $payments=PropertyPayments::rows();$features=[];foreach($rows as $r){$g=self::geometry($r);if(!$g)continue;unset($r['boundary_geojson']);$summary=$payments[$r['id']];foreach(['payment_status','payment_label','billed','paid','outstanding','bill_count','is_exempt'] as $key)$r[$key]=$summary[$key];$r['account_number']=$r['id'];$r['Account']=$r['id'];$r['preview_url']=url('property-preview',['id'=>$r['id']]);$r['owners']=implode(', ',$owners[$r['id']]??[]);$r['url']=url('detail',['type'=>'properties','id'=>$r['id']]);$features[]=['type'=>'Feature','geometry'=>$g,'properties'=>$r];}
  if(!Auth::owner())foreach(Database::all('SELECT pa.*,l.name layer_name,l.is_visible_by_default FROM parcels pa LEFT JOIN spatial_layers l ON l.id=pa.spatial_layer_id WHERE NOT EXISTS (SELECT 1 FROM properties p WHERE p.parcel_id=pa.id) AND (l.is_active=1 OR l.id IS NULL)') as $r){$g=self::geometry($r);if(!$g)continue;$features[]=['type'=>'Feature','geometry'=>$g,'properties'=>['id'=>$r['id'],'payment_status'=>'unbilled','payment_label'=>'Not yet billed','owners'=>'','parcel_number'=>$r['parcel_number'],'property_number'=>$r['parcel_number'],'locality'=>$r['locality'],'status'=>$r['status'],'layer_name'=>$r['layer_name'],'is_visible_by_default'=>$r['is_visible_by_default'],'url'=>url('detail',['type'=>'parcels','id'=>$r['id']])]];}
  return ['type'=>'FeatureCollection','features'=>$features];
 }
 private static function geometry(array $r): ?array {
  if (!empty($r['boundary_geojson'])) {
   $geometry=json_decode((string)$r['boundary_geojson'],true,64,JSON_THROW_ON_ERROR);
   if (is_string($geometry)) {
    $geometry=json_decode($geometry,true,64,JSON_THROW_ON_ERROR);
   }
   if (!is_array($geometry)) throw new \DomainException('Stored parcel geometry is invalid.');
   return $geometry;
  }
  return $r['latitude']!==null&&$r['longitude']!==null
   ? ['type'=>'Point','coordinates'=>[(float)$r['longitude'],(float)$r['latitude']]]
   : null;
 }
}
