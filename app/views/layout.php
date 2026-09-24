<?php
use Srms\Navigation;
$route = $_GET['r'] ?? 'dashboard';
$mapPage = $route === 'map' || (($_GET['type'] ?? '') === 'parcels');
?>
<!doctype html>
<html lang="en" data-appearance-key="<?=e($user?substr(hash('sha256',$user['id']),0,24):'guest')?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="#123b59">
    <script src="<?=e(config('base_url'))?>/assets/appearance.js"></script>
    <title><?=e($title??'SRMS')?> · EDDT</title>
    <link rel="icon" href="<?=e(config('base_url'))?>/assets/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="<?=e(config('base_url'))?>/assets/app.css">
    <link rel="stylesheet" href="<?=e(config('base_url'))?>/assets/v3.css">
    <link rel="stylesheet" href="<?=e(config('base_url'))?>/assets/appearance.css">
    <?php if($mapPage):?><link rel="stylesheet" href="<?=e(config('base_url'))?>/assets/leaflet/leaflet.css"><?php endif?>
    <link rel="stylesheet" href="<?=e(config('base_url'))?>/assets/property-enhancements.css">
    <script defer src="<?=e(config('base_url'))?>/assets/app.js"></script>
    <script defer src="<?=e(config('base_url'))?>/assets/correspondence.js"></script>
</head>
<body class="<?=$user?'signed-in':'signed-out'?>" data-route="<?=e($route)?>">
<a class="skip" href="#main">Skip to content</a>
<?php if($user):?>
<aside class="sidebar" id="app-navigation" aria-label="Application navigation">
    <a class="brand" href="<?=e(url('dashboard'))?>"><img src="<?=e(config('base_url'))?>/assets/eddt-logo.png" alt=""><span><strong>EDDT</strong><small>Spatial Revenue</small></span></a>
    <p class="nav-label">Trust workspace</p>
    <nav>
        <?php foreach(Navigation::items() as $item):$active=Navigation::active($item);?>
        <a class="nav-item <?=$active?'active':''?>" <?=$active?'aria-current="page"':''?> href="<?=e(url($item['route'],$item['params']??[]))?>"><span class="nav-icon" aria-hidden="true"><?=e($item['icon'])?></span><span><?=e($item['label'])?></span></a>
        <?php endforeach?>
    </nav>
    <div class="sidebar-foot"><span class="secure-dot"></span><span>Protected Trust workspace</span></div>
</aside>
<div class="nav-scrim" data-nav-close hidden></div>
<div class="shell">
    <header class="topbar">
        <button class="mobile-toggle" type="button" aria-controls="app-navigation" aria-expanded="false"><span aria-hidden="true">☰</span><span class="sr-only">Open navigation</span></button>
        <div class="topbar-title"><span>EDDT SRMS</span><strong><?=e($title??'Workspace')?></strong></div>
        <div class="topbar-right">
            <span class="date"><?=e(date('D, d M Y'))?></span>
            <a class="user-chip" href="<?=e(url('password'))?>"><span class="avatar"><?=e(strtoupper(substr($user['full_name'],0,1)))?></span><span><strong><?=e($user['full_name'])?></strong><small><?=e($user['role_name'])?></small></span></a>
            <form method="post" action="<?=e(url('logout'))?>"><?=csrf_field()?><button class="icon-button" title="Sign out" aria-label="Sign out">↪</button></form>
        </div>
    </header>
<?php endif?>
<main id="main" class="<?=$user?'main-content':'auth-main'?>">
    <?php if(isset($_SESSION['flash'])):$f=$_SESSION['flash'];unset($_SESSION['flash']);?><div class="alert <?=e($f['type'])?>" role="status"><?=e($f['text'])?></div><?php endif?>
    <?=$content?>
</main>
<?php if($user):?><footer class="app-footer"><span>East Dadekotopon Development Trust</span><span>EDDT SRMS · <?=date('Y')?></span></footer></div><?php endif?>
<details class="appearance-controls"><summary>Appearance</summary><div class="appearance-panel"><h2>Appearance</h2><label class="field"><span>Colour theme</span><select data-appearance-theme><option value="trust">Trust red &amp; navy</option><option value="forest">Forest green &amp; cream</option></select></label><label class="field"><span>Display mode</span><select data-appearance-mode><option value="system">Follow device</option><option value="light">Day mode</option><option value="dark">Night mode</option></select></label><p role="status" aria-live="polite">Remembered for this user in this browser.</p></div></details>
</body>
</html>
