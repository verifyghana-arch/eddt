<?php
require_once __DIR__.'/ui.php';
page_heading('System settings', 'Configure Trust identity, correspondence, imports, storage and map sources.');

$groups = [
    'Organization' => ['organization_name','address','email','phone','digital_address','currency','number_prefix'],
    'Correspondence' => ['invitation_body','demand_warning','signatory','retention_policy'],
    'Files and imports' => ['upload_mb','import_max_rows','import_chunk_rows'],
];
$labels = [
    'map_default_basemap'=>'Default base map','map_osm_tiles'=>'OpenStreetMap tile URL','map_osm_attribution'=>'OpenStreetMap attribution',
    'map_esri_tiles'=>'Esri World Imagery tile URL','map_esri_attribution'=>'Esri attribution','map_orthophoto_name'=>'Orthophoto layer name',
    'map_orthophoto_tiles'=>'Orthophoto XYZ tile URL','map_orthophoto_attribution'=>'Orthophoto attribution / owner',
    'map_orthophoto_min_zoom'=>'Orthophoto minimum zoom','map_orthophoto_max_zoom'=>'Orthophoto maximum zoom','map_orthophoto_opacity'=>'Orthophoto default opacity',
];
?>
<form method="post" action="<?=e(url('settings'))?>" class="settings-form">
    <?=csrf_field()?>
    <?php foreach($groups as $heading=>$keys):?>
    <section class="panel form-panel">
        <div class="panel-heading"><div><h2><?=e($heading)?></h2><p>Changes are audited and apply to future records and correspondence.</p></div></div>
        <div class="form-grid">
        <?php foreach($keys as $key):if(!array_key_exists($key,$settings))continue;
            field($key,ucwords(str_replace('_',' ',$key)),$settings[$key],in_array($key,['invitation_body','demand_warning','retention_policy'],true)?'textarea':'text',true);
        endforeach?>
        </div>
    </section>
    <?php endforeach?>
    <section class="panel form-panel" id="map-settings">
        <div class="panel-heading"><div><h2>Map sources</h2><p>Choose street or satellite imagery and optionally publish a tiled Trust orthophoto.</p></div><a class="button" href="<?=e(url('map'))?>">Preview map</a></div>
        <div class="callout"><strong>Orthophoto requirement</strong><p>Publish a georeferenced image as XYZ tiles using the pattern <code>/tiles/orthophoto/{z}/{x}/{y}.png</code> or an HTTPS tile service. A plain JPG or PNG cannot be positioned reliably without georeferencing.</p></div>
        <div class="form-grid">
            <?php field('map_default_basemap',$labels['map_default_basemap'],$settings['map_default_basemap']??'osm','select',true,['osm'=>'OpenStreetMap','esri'=>'Esri World Imagery']);?>
            <?php foreach(['map_osm_tiles','map_osm_attribution','map_esri_tiles','map_esri_attribution','map_orthophoto_name','map_orthophoto_tiles','map_orthophoto_attribution','map_orthophoto_min_zoom','map_orthophoto_max_zoom','map_orthophoto_opacity'] as $key):
                $type=in_array($key,['map_orthophoto_min_zoom','map_orthophoto_max_zoom','map_orthophoto_opacity'],true)?'number':'text';
                field($key,$labels[$key],$settings[$key]??'', $type, !in_array($key,['map_orthophoto_tiles'],true));
            endforeach?>
        </div>
        <p class="help-text">OpenStreetMap and Esri attribution must remain visible. Verify the licence and attribution supplied with your orthophoto.</p>
    </section>
    <div class="sticky-actions"><button class="button primary" type="submit">Save system settings</button></div>
</form>
<section class="panel action-panel">
    <div><h2>Trust-data export</h2><p>Download structured records, original documents, archived correspondence and a SHA-256 integrity manifest. Passwords are excluded.</p></div>
    <?php form_start('backup');submit('Download Trust export ZIP');?>
</section>
