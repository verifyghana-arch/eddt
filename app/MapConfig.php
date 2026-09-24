<?php
namespace Srms;

final class MapConfig
{
    public const DEFAULT_OSM = 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';
    public const DEFAULT_ESRI = 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}';

    public static function public(): array
    {
        return [
            'default' => Settings::get('map_default_basemap', 'osm'),
            'basemaps' => [
                'osm' => [
                    'name' => 'OpenStreetMap',
                    'url' => Settings::get('map_osm_tiles', Settings::get('map_tiles', self::DEFAULT_OSM)),
                    'attribution' => Settings::get('map_osm_attribution', Settings::get('map_attribution', '© OpenStreetMap contributors')),
                    'maxZoom' => 20,
                    'maxNativeZoom' => 19,
                ],
                'esri' => [
                    'name' => 'Esri World Imagery',
                    'url' => Settings::get('map_esri_tiles', self::DEFAULT_ESRI),
                    'attribution' => Settings::get('map_esri_attribution', 'Tiles © Esri — Source: Esri, Maxar, Earthstar Geographics and the GIS User Community'),
                    'maxZoom' => 20,
                    'maxNativeZoom' => 19,
                ],
            ],
            'orthophoto' => self::orthophoto(),
        ];
    }

    private static function orthophoto(): ?array
    {
        $url = trim(Settings::get('map_orthophoto_tiles'));
        if ($url === '') {
            return null;
        }
        return [
            'name' => Settings::get('map_orthophoto_name', 'EDDT Orthophoto'),
            'url' => $url,
            'attribution' => Settings::get('map_orthophoto_attribution', 'East Dadekotopon Development Trust'),
            'maxZoom' => (int) Settings::get('map_orthophoto_max_zoom', '22'),
            'minZoom' => (int) Settings::get('map_orthophoto_min_zoom', '0'),
            'opacity' => (float) Settings::get('map_orthophoto_opacity', '1'),
        ];
    }
}
