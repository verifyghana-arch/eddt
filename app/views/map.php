<?php
require_once __DIR__.'/ui.php';
use Srms\Auth;
use Srms\MapConfig;

$mapConfig = MapConfig::public();
page_heading('Property map', 'Switch between street, satellite and Trust imagery while exploring every authorized parcel.');
?>
<section class="panel map-panel">
    <div class="toolbar map-toolbar">
        <label class="field grow"><span>Search properties</span><input id="map-search" type="search" placeholder="Account, owner, Land ID or locality"></label>
        <label class="field"><span>Payment status</span><select id="map-filter-status">
            <option value="">All statuses</option>
            <option value="unpaid">Non-payment</option>
            <option value="partial">Partial payment</option>
            <option value="paid">Full payment</option>
            <option value="exempt">Exempt</option>
            <option value="unbilled">Not yet billed</option>
        </select></label>
        <label class="check map-in-view"><input type="checkbox" id="map-in-view"> Current map area only</label>
        <button id="map-fit" class="button" type="button">Fit parcels</button>
        <a class="button" href="<?=e(url('geojson'))?>">Export GeoJSON</a>
    </div>
    <div class="map-layer-help">
        <strong>Map layers</strong>
        <span>Use the layer control on the map to choose OpenStreetMap, Esri World Imagery, or the configured orthophoto.</span>
        <label id="orthophoto-opacity-wrap" hidden>Orthophoto opacity <input id="orthophoto-opacity" type="range" min="10" max="100" value="100"></label>
    </div>
    <div id="property-map"
         data-source="<?=e(url('map-data'))?>"
         data-map-config="<?=e(json_encode($mapConfig, JSON_THROW_ON_ERROR))?>"></div>
    <div class="map-legend" aria-label="Parcel payment legend">
        <span><i class="map-swatch unpaid"></i>Non-payment</span>
        <span><i class="map-swatch partial"></i>Partial payment</span>
        <span><i class="map-swatch paid"></i>Full payment</span>
        <span><i class="map-swatch exempt"></i>Exempt</span>
        <span><i class="map-swatch unbilled"></i>Not yet billed</span>
        <span class="map-legend-note">All issued bills, including arrears</span>
    </div>
    <p id="map-status" class="map-status" role="status">Loading authorized property locations…</p>
</section>
<?php if(in_array(Auth::role(), ['Administrator','Billing Officer'], true)):?>
<section class="panel callout-panel">
    <div><h2>Parcel boundaries</h2><p>Validate GeoJSON before an Administrator approves it. Imports never replace conflicting parcel links.</p></div>
    <a class="button" href="<?=e(url('imports'))?>">Open data imports</a>
    <?php if(Auth::role()==='Administrator'):?><a class="button" href="<?=e(url('settings'))?>#map-settings">Configure map sources</a><?php endif?>
</section>
<?php endif?>
<script defer src="<?=e(config('base_url'))?>/assets/leaflet/leaflet.js"></script>
<script defer src="<?=e(config('base_url'))?>/assets/map.js"></script>
