<?php
declare(strict_types=1);

require dirname(__DIR__).'/app/bootstrap.php';

use Srms\Database;
use Srms\Registry;

$path = $argv[1] ?? SRMS_ROOT.'/simages/BillingRoll.geojson';
if (!is_file($path)) {
    fwrite(STDERR, "GeoJSON file not found: {$path}\n");
    exit(1);
}

try {
    $collection = json_decode((string) file_get_contents($path), true, 64, JSON_THROW_ON_ERROR);
    if (($collection['type'] ?? '') !== 'FeatureCollection') {
        throw new DomainException('The file must contain a GeoJSON FeatureCollection.');
    }

    $features = $collection['features'] ?? [];
    if (!$features) {
        throw new DomainException('The GeoJSON FeatureCollection contains no features.');
    }

    $result = Database::transaction(function () use ($features, $path): array {
        $layer = Database::one('SELECT id FROM spatial_layers WHERE name=?', ['Billing roll']);
        $layerId = $layer['id'] ?? Database::insert('spatial_layers', [
            'name' => 'Billing roll',
            'layer_type' => 'parcel',
            'description' => 'Property boundaries imported from the EDDT billing roll.',
            'source_reference' => basename($path),
            'display_order' => 10,
            'is_visible_by_default' => 1,
            'is_active' => 1,
        ]);

        $imported = 0;
        $skipped = 0;
        foreach ($features as $index => $feature) {
            $properties = $feature['properties'] ?? [];
            $number = trim((string) ($properties['parcel_number'] ?? $properties['Account'] ?? ''));
            if ($number === '') {
                throw new DomainException('Feature '.($index + 1).' has no Account or parcel_number.');
            }

            $geometry = Registry::geometry(json_encode($feature['geometry'] ?? [], JSON_THROW_ON_ERROR));
            if (Database::one('SELECT id FROM parcels WHERE parcel_number=?', [$number])) {
                $skipped++;
                continue;
            }

            Database::insert('parcels', [
                'spatial_layer_id' => $layerId,
                'parcel_number' => $number,
                'locality' => $properties['locality'] ?? null,
                'status' => 'active',
                'boundary_geojson' => $geometry,
                'notes' => 'Imported from '.basename($path).'.',
            ]);
            $imported++;
        }

        return [$imported, $skipped, count($features)];
    });

    printf(
        "Billing roll import complete: %d imported, %d already existed, %d total.\n",
        $result[0],
        $result[1],
        $result[2]
    );
} catch (Throwable $e) {
    fwrite(STDERR, "Billing roll import failed: {$e->getMessage()}\n");
    exit(1);
}
