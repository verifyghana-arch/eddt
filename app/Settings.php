<?php
namespace Srms;

final class Settings
{
    public static function all(): array
    {
        return array_column(Database::all('SELECT * FROM settings ORDER BY setting_key'), 'setting_value', 'setting_key');
    }

    public static function get(string $key, string $default = ''): string
    {
        $value = Database::scalar('SELECT setting_value FROM settings WHERE setting_key=?', [$key]);
        return $value === false || $value === null ? $default : (string) $value;
    }

    public static function save(array $data): void
    {
        Auth::admin();
        $old = self::all();
        foreach ($old as $key => $current) {
            if (!array_key_exists($key, $data)) continue;
            $value = trim((string) $data[$key]);
            self::validate($key, $value);
            Database::query('UPDATE settings SET setting_value=? WHERE setting_key=?', [$value, $key]);
        }
        Audit::log('settings_updated', 'settings', null, $old, self::all());
    }

    private static function validate(string $key, string $value): void
    {
        if (in_array($key, ['import_max_rows', 'import_chunk_rows'], true)) {
            $max = $key === 'import_chunk_rows' ? 100 : 10000;
            if (!ctype_digit($value) || (int) $value < 1 || (int) $value > $max) {
                throw new \DomainException("$key must be between 1 and $max.");
            }
        }
        if ($key === 'currency' && $value !== 'GHS') throw new \DomainException('This application uses GHS.');
        if ($key === 'upload_mb' && (!ctype_digit($value) || (int) $value < 1 || (int) $value > 100)) {
            throw new \DomainException('Upload limit must be between 1 and 100 MB.');
        }
        if (in_array($key, ['map_tiles', 'map_osm_tiles', 'map_esri_tiles'], true)) self::tileUrl($value, false);
        if ($key === 'map_orthophoto_tiles') self::tileUrl($value, true);
        if ($key === 'map_default_basemap' && !in_array($value, ['osm', 'esri'], true)) {
            throw new \DomainException('Default base map must be OpenStreetMap or Esri World Imagery.');
        }
        if (in_array($key, ['map_orthophoto_min_zoom', 'map_orthophoto_max_zoom'], true)
            && (!ctype_digit($value) || (int) $value < 0 || (int) $value > 24)) {
            throw new \DomainException('Orthophoto zoom levels must be between 0 and 24.');
        }
        if ($key === 'map_orthophoto_opacity'
            && (!is_numeric($value) || (float) $value < 0.1 || (float) $value > 1)) {
            throw new \DomainException('Orthophoto opacity must be between 0.1 and 1.');
        }
        if ($key === 'number_prefix' && !preg_match('/^[A-Z0-9]{1,10}$/', $value)) {
            throw new \DomainException('Prefix must be 1–10 uppercase letters or digits.');
        }
    }

    private static function tileUrl(string $value, bool $optional): void
    {
        if ($optional && $value === '') return;
        if (!str_starts_with($value, '/') && !preg_match('#^https://[^\s]+$#', $value)) {
            throw new \DomainException('Map tile URLs must use HTTPS or an application-relative / path.');
        }
        foreach (['{z}', '{x}', '{y}'] as $token) {
            if (!str_contains($value, $token)) throw new \DomainException('Map tile URLs must include {z}, {x}, and {y}.');
        }
    }

    public static function number(string $kind): string
    {
        return self::get('number_prefix', 'EDDT').'-'.$kind.'-'.date('Y').'-'.strtoupper(bin2hex(random_bytes(5)));
    }
}
