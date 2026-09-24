<?php
namespace Srms;

final class Navigation
{
    public static function items(): array
    {
        $items = [
            ['route'=>'dashboard','label'=>'Dashboard','icon'=>'DB'],
            ['route'=>'map','label'=>'Property map','icon'=>'MP'],
            ['route'=>'register','label'=>'Properties','icon'=>'PR','params'=>['type'=>'parcels']],
        ];
        if (!Auth::owner()) {
            $items[] = ['route'=>'workflows','label'=>'Workflows','icon'=>'WF'];
            $items[] = ['route'=>'register','label'=>'Property owners','icon'=>'OW','params'=>['type'=>'ratepayers']];
        }
        $items = array_merge($items, [
            ['route'=>'billing','label'=>'Ground rent','icon'=>'GR'],
            ['route'=>'payments','label'=>'Payments','icon'=>'PY'],
            ['route'=>'documents','label'=>'Documents','icon'=>'DC'],
            ['route'=>'reports','label'=>'Reports','icon'=>'RP'],
        ]);
        if (!Auth::owner()) $items[] = ['route'=>'imports','label'=>'Data imports','icon'=>'IM'];
        if (Auth::role() === 'Administrator') {
            $items[] = ['route'=>'users','label'=>'Users & access','icon'=>'UA'];
            $items[] = ['route'=>'settings','label'=>'Settings','icon'=>'ST'];
        }
        return $items;
    }

    public static function active(array $item): bool
    {
        $route = $_GET['r'] ?? 'dashboard';
        if ($item['route'] === 'register' && $route === 'detail') $route = 'register';
        if ($route !== $item['route']) return false;
        $expected = $item['params']['type'] ?? null;
        return $expected === null || Application::normalizeType($_GET['type'] ?? null) === $expected;
    }
}
