<?php
namespace Srms;
final class PropertyPreview {
 public static function get(string $id): array {
  if(!Auth::user())throw new \DomainException('Sign in to view property details.');Auth::property($id);
  $owners=Database::all("SELECT r.full_name,r.organization_name,o.ownership_percentage FROM property_ratepayers o JOIN ratepayers r ON r.id=o.ratepayer_id WHERE o.account_number=? AND o.relationship_type='owner' AND o.start_date<=? AND (o.end_date IS NULL OR o.end_date>?) ORDER BY r.full_name",[$id,today(),today()]);
  $photos=Database::all("SELECT ph.caption,ph.taken_at,d.id document_id FROM property_photos ph JOIN documents d ON d.id=ph.document_id WHERE ph.account_number=? AND d.is_current_version=1 AND d.mime_type IN ('image/jpeg','image/png') AND EXISTS (SELECT 1 FROM document_links dl WHERE dl.document_id=d.id AND dl.entity_type IN ('parcels','properties','accounts') AND dl.entity_id=ph.account_number)".(Auth::owner()?' AND d.owner_visible=1':'')." ORDER BY ph.is_primary DESC,ph.display_order,ph.created_at,ph.id",[$id]);
  foreach($photos as &$photo){$photo['url']=url('download',['id'=>$photo['document_id'],'inline'=>1]);unset($photo['document_id']);}unset($photo);
  return ['owners'=>$owners,'photos'=>$photos];
 }
}