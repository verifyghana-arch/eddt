<?php
namespace Srms;

final class AppSchema
{
    public const VERSION = '008_application_rebuild';

    public static function migrate(): void
    {
        if (Database::scalar('SELECT COUNT(*) FROM schema_migrations WHERE version=?', [self::VERSION])) {
            return;
        }
        Database::transaction(function (): void {
            $defaults = [
                'map_default_basemap' => 'osm',
                'map_osm_tiles' => MapConfig::DEFAULT_OSM,
                'map_osm_attribution' => '© OpenStreetMap contributors',
                'map_esri_tiles' => MapConfig::DEFAULT_ESRI,
                'map_esri_attribution' => 'Tiles © Esri — Source: Esri, Maxar, Earthstar Geographics and the GIS User Community',
                'map_orthophoto_name' => 'EDDT Orthophoto',
                'map_orthophoto_tiles' => '',
                'map_orthophoto_attribution' => 'East Dadekotopon Development Trust',
                'map_orthophoto_min_zoom' => '0',
                'map_orthophoto_max_zoom' => '22',
                'map_orthophoto_opacity' => '1',
            ];
            foreach ($defaults as $key => $value) {
                if (!Database::scalar('SELECT COUNT(*) FROM settings WHERE setting_key=?', [$key])) {
                    Database::query('INSERT INTO settings (setting_key,setting_value) VALUES (?,?)', [$key, $value]);
                }
            }
            Database::query('INSERT INTO schema_migrations (version,applied_at) VALUES (?,CURRENT_TIMESTAMP)', [self::VERSION]);
        });
    }
}
