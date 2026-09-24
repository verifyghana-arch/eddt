<?php
require_once __DIR__.'/ui.php';
use Srms\Auth;
use Srms\Application as App;

$active_count = 0;
foreach ($rows as $row) {
    if (strtolower((string)($row['status'] ?? '')) === 'active') {
        $active_count++;
    }
}
?>
<div class="property-wrap">
    <div class="property-page-header">
        <div>
            <div class="property-pills">
                <span class="property-badge muted">LAND &amp; PEOPLE</span>
                <span class="property-badge account"><?=e(App::label($type))?></span>
            </div>
            <h1 class="property-title"><?=e($type === 'parcels' ? 'Properties' : $title)?></h1>
            <p class="property-subtitle">Search and manage the Trust’s <?=e(strtolower($title))?>.<span>•</span><?=number_format($total)?> total records</p>
        </div>
        <?php if(in_array(Auth::role(),['Administrator','Billing Officer'],true)):?>
            <div class="property-actions">
                <a class="button primary" href="<?=e(url('edit',['type'=>$type]))?>">＋ Add record</a>
            </div>
        <?php endif?>
    </div>

    <div class="summary-strip">
        <div class="summary-card">
            <div class="summary-label">Total</div>
            <div class="summary-value"><?=number_format($total)?></div>
            <div class="summary-meta">Registered records</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Active on page</div>
            <div class="summary-value"><?=number_format($active_count)?></div>
            <div class="summary-meta">On this page</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Current page</div>
            <div class="summary-value"><?=number_format(count($rows))?></div>
            <div class="summary-meta">Visible records</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Page</div>
            <div class="summary-value"><?=$page?></div>
            <div class="summary-meta">of <?=max(1,ceil($total/25))?></div>
        </div>
    </div>

    <nav class="property-tabs" aria-label="Register sections">
        <a class="active" href="#property-register" aria-current="page">All <?= e(strtolower($type === 'parcels' ? 'properties' : $title)) ?></a>
        <?php if ($type === 'parcels'): ?><a href="<?= e(url('map')) ?>">Map view ↗</a><?php endif ?>
    </nav>

    <div class="property-card" id="property-register">
        <div class="card-header">
            <h2><?= $type === 'parcels' ? 'Property register' : e($title) ?></h2>
            <div class="property-actions">
            <?php if (!Auth::owner() && $type === 'parcels'): ?>
            <a class="button tiny" href="<?=e(url('export',['type'=>'property_contacts','format'=>'csv']))?>">Export CSV</a>
            <a class="button tiny" href="<?=e(url('export',['type'=>'property_contacts','format'=>'xlsx']))?>">Export Excel</a>
            <a class="button tiny" href="<?=e(url('reports',['type'=>'property_contacts']))?>">Owner contacts</a>
            <?php endif ?></div>
        </div>

        <form class="register-toolbar" method="get">
            <input type="hidden" name="r" value="register">
            <input type="hidden" name="type" value="<?=e($type)?>">
            <input class="search-box" aria-label="Search register" type="search" name="q" value="<?=e($q)?>" placeholder="Search names, numbers, or locality…">
            <button class="button">Search</button>
            <?php if ($q !== ''): ?><a class="text-link" href="<?=e(url('register',['type'=>$type]))?>">Clear</a><?php endif ?>
            <span class="register-total"><?=$total?> records</span>
        </form>

        <?php if(!$rows): ?>
            <?php data_table([]); ?>
        <?php else: ?>
            <?php $cols = match($type){
                'properties' => ['property_number'=>'Property','land_id'=>'Land ID','locality'=>'Locality','property_type'=>'Type','status'=>'Status'],
                'ratepayers' => ['ratepayer_number'=>'Owner no.','full_name'=>'Name','ratepayer_type'=>'Type','organization_name'=>'Organization','status'=>'Status'],
                'accounts' => ['account_number'=>'Account','land_account_number'=>'Land account','opened_on'=>'Opened','credit_amount'=>'Credit (GHS)','status'=>'Status'],
                'parcels' => ['account_number'=>'Account number','division_code'=>'Division','block_code'=>'Block','parcel_code'=>'Parcel','locality'=>'Locality','property_type'=>'Type','status'=>'Status'],
                'spatial_layers' => ['name'=>'Name','layer_type'=>'Type','description'=>'Description'],
                default => ['id'=>'ID','status'=>'Status']
            }; ?>
            <div class="table-wrap register-table-wrap">
                <table class="register-table">
                    <thead>
                    <tr>
                        <?php foreach($cols as $label): ?>
                            <th><?=e($label)?></th>
                        <?php endforeach ?>
                        <th><span class="sr-only">Actions</span></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach($rows as $row): ?>
                        <tr>
                            <?php foreach($cols as $key=>$label): ?>
                                <td><?=$key==='status'?badge($row[$key]):e(($row[$key] ?? '') ?: '—')?></td>
                            <?php endforeach ?>
                            <td><a class="text-link" href="<?=e(url('detail',['type'=>$type,'id'=>$row['id']]))?>">View →</a></td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        <?php endif ?>

        <div class="pagination">
            <span>Page <?=$page?> of <?=max(1,ceil($total/25))?></span>
            <div>
                <?php if($page>1): ?>
                    <a class="button tiny" href="<?=e(url('register',['type'=>$type,'q'=>$q,'page'=>$page-1]))?>">← Previous</a>
                <?php endif ?>
                <?php if($page*25<$total): ?>
                    <a class="button tiny" href="<?=e(url('register',['type'=>$type,'q'=>$q,'page'=>$page+1]))?>">Next →</a>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>

