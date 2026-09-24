<?php
require_once __DIR__.'/ui.php';
use Srms\Database as DB;
use Srms\Auth;
use Srms\Application as App;

$id = $record['id'];
$write = in_array(Auth::role(), ['Administrator', 'Billing Officer'], true);

if ($type === 'parcels') {
    $owners = DB::all('SELECT o.*, r.full_name, r.ratepayer_type FROM property_ratepayers o JOIN ratepayers r ON r.id = o.ratepayer_id WHERE o.account_number = ? ORDER BY o.start_date DESC', [$id]);
    $currentOwners = array_values(array_filter($owners, fn($o) => empty($o['end_date'])));
    $bills = DB::all('SELECT * FROM ground_rent_bills WHERE account_number = ? ORDER BY billing_year DESC', [$id]);
    $payments = DB::all('SELECT * FROM payments WHERE account_number = ? ORDER BY payment_date DESC', [$id]);
    $linked = DB::all('SELECT d.* FROM documents d JOIN document_links l ON l.document_id = d.id WHERE l.account_number = ? ORDER BY d.created_at DESC', [$id]);
    $linked = array_values(array_filter($linked, fn($d) => \Srms\Documents::allowed($d)));
    $photos = DB::all("SELECT ph.*,d.original_filename,d.owner_visible FROM property_photos ph JOIN documents d ON d.id=ph.document_id WHERE ph.account_number=? AND d.is_current_version=1 ORDER BY ph.is_primary DESC,ph.display_order,ph.created_at", [$id]);
    if (Auth::owner()) $photos = array_values(array_filter($photos, fn($photo) => !empty($photo['owner_visible'])));
    $outstanding = 0.0;
    foreach ($bills as $bill) {
        $outstanding += (float)($bill['principal_amount'] + $bill['penalty_amount'] + $bill['adjustment_amount'] - $bill['paid_amount']);
    }
    $currentBill = $bills[0] ?? null;
    $geometry = json_decode($record['boundary_geojson'] ?? 'null', true);
    if (!$geometry && is_numeric($record['latitude'] ?? null) && is_numeric($record['longitude'] ?? null)) {
        $geometry = ['type' => 'Point', 'coordinates' => [(float)$record['longitude'], (float)$record['latitude']]];
    }
    $ownerVisible = count(array_filter($linked, fn($d) => !empty($d['owner_visible'])));
    $currentBillValue = $currentBill ? (float)($currentBill['principal_amount'] + $currentBill['penalty_amount'] + $currentBill['adjustment_amount']) : 0.0;
?>
<div class="property-with-correspondence"><div class="property-wrap">
  <div class="property-page-header">
    <div>
      <div class="property-pills">
        <span class="property-badge account"><?= e($record['account_number'] ?? '—') ?></span>
        <span class="property-badge state"><?= e(ucfirst($record['status'] ?? 'active')) ?></span>
        <span class="property-badge muted"><?= e($record['property_type'] ?: 'Type not set') ?></span>
      </div>
      <h1 class="property-title"><?= e(($record['locality'] ?: 'Property').' — Parcel '.($record['parcel_code'] ?? '—').' / Block '.($record['block_code'] ?? '—')) ?></h1>
      <p class="property-subtitle"><?= e($record['land_id'] ? 'Land ID '.$record['land_id'] : 'Land ID not assigned') ?><span> · </span>Division <?= e($record['division_code'] ?? '—') ?><span> · </span><?= e($record['plot_size'] ? $record['plot_size'].' '.$record['plot_size_unit'] : 'Size not set') ?></p>
    </div>
    <div class="property-actions">
      <a class="button secondary" href="<?= e(url('register', ['type' => 'parcels'])) ?>">← Back</a>
      <?php if ($currentOwners && !Auth::owner()): ?>
      <a class="button quiet" href="<?= e(url('detail', ['type' => 'ratepayers', 'id' => $currentOwners[0]['ratepayer_id']])) ?>">View owner</a>
      <?php endif ?>
      <?php if ($write): ?>
        <a class="button secondary" href="<?= e(url('edit', ['type' => 'parcels', 'id' => $id])) ?>">Edit property</a>
        <a class="button primary" href="<?= e(url('payment-new', ['account_number' => $id])) ?>">Record payment</a>
      <?php endif ?>
    </div>
  </div>

  <div class="summary-strip">
    <div class="summary-card">
      <div class="summary-label">Outstanding balance</div>
      <div class="summary-value"><?= e(money($outstanding)) ?></div>
      <div class="summary-meta"><?= e(count($bills) ? count($bills).' bill(s) on record' : 'No bills on record') ?></div>
    </div>
    <div class="summary-card">
      <div class="summary-label"><?= e($currentBill ? $currentBill['billing_year'].' bill' : 'Latest bill') ?></div>
      <div class="summary-value"><?= e($currentBill ? money($currentBillValue) : '—') ?></div>
      <div class="summary-meta"><?= $currentBill ? badge($currentBill['status']) : 'No bills issued' ?></div>
    </div>
    <div class="summary-card">
      <div class="summary-label">Owners</div>
      <div class="summary-value"><?= e((string)count($currentOwners)) ?></div>
      <div class="summary-meta"><?= e($currentOwners[0]['full_name'] ?? 'Unassigned') ?></div>
    </div>
    <div class="summary-card">
      <div class="summary-label">Documents</div>
      <div class="summary-value"><?= e((string)count($linked)) ?></div>
      <div class="summary-meta"><?= e($ownerVisible.' owner-visible') ?></div>
    </div>
  </div>

  <nav class="property-tabs" data-property-tabs aria-label="Property sections">
    <a class="active" href="#overview">Overview</a>
    <a href="#bills">Bills (<?= count($bills) ?>)</a>
    <a href="#payments">Payments (<?= count($payments) ?>)</a>
    <a href="#ownership">Ownership</a>
    <a href="#photos">Photos (<?= count($photos) ?>)</a>
    <a href="#documents">Documents (<?= count($linked) ?>)</a>
    <?php if ($write): ?><a href="#correspondence">Correspondence</a><?php endif ?>
  </nav>

  <div id="overview" data-property-panel>
  <div class="detail-two-col">
    <section class="property-card">
      <div class="card-header"><h2>Parcel details</h2></div>
      <dl class="detail-list">
        <div><dt>Account</dt><dd><?= e($record['account_number'] ?? '—') ?></dd></div>
        <div><dt>Land ID</dt><dd><?= e($record['land_id'] ?? '—') ?></dd></div>
        <div><dt>Locality</dt><dd><?= e($record['locality'] ?? '—') ?></dd></div>
        <div><dt>Coordinates</dt><dd><?= e(($record['latitude'] ?? '—').', '.($record['longitude'] ?? '—')) ?></dd></div>
        <div><dt>Geometry</dt><dd><span class="mini-badge"><?= e($geometry['type'] ?? 'Not set') ?></span></dd></div>
        <div><dt>Size</dt><dd><?= e($record['plot_size'] ? $record['plot_size'].' '.$record['plot_size_unit'] : '—') ?></dd></div>
      </dl>
    </section>

    <section class="property-card">
      <div class="card-header">
        <h2>Current ownership</h2>
        <?php if (Auth::role() === 'Administrator'): ?>
          <a class="button tiny" href="#ownership" data-transfer>Transfer</a>
        <?php endif ?>
      </div>
      <table class="ownership-table">
        <thead>
          <tr><th>Owner</th><th>Type</th><th>Share</th><th>Since</th></tr>
        </thead>
        <tbody>
          <?php if ($currentOwners && !Auth::owner()): foreach ($currentOwners as $owner): ?>
            <tr>
              <td><?= e($owner['full_name'] ?? '—') ?></td>
              <td><?= e(ucfirst($owner['ratepayer_type'] ?? '—')) ?></td>
              <td><?= e(isset($owner['ownership_percentage']) ? (float)$owner['ownership_percentage'].'%' : 'Not declared') ?></td>
              <td><?= e($owner['start_date'] ?? '—') ?></td>
            </tr>
          <?php endforeach; else: ?>
            <tr><td colspan="4">No active owners recorded.</td></tr>
          <?php endif ?>
        </tbody>
      </table>
    </section>
  </div>

  <section class="property-map-card">
    <div class="map-header">
      <h3>Mini map</h3>
      <a class="button tiny" href="<?= e(url('map')) ?>">Full map →</a>
    </div>
    <?php if ($geometry): ?>
    <div id="parcel-mini-map" data-geometry="<?= e(json_encode($geometry)) ?>" data-tiles="<?= e(\Srms\Settings::get('map_tiles', 'https://tile.openstreetmap.org/{z}/{x}/{y}.png')) ?>" data-attribution="<?= e(\Srms\Settings::get('map_attribution', '© OpenStreetMap contributors')) ?>" aria-label="Property location map"></div>
    <p id="parcel-map-notice" class="muted" role="status" hidden>Background map unavailable. The recorded property geometry is still shown.</p>
    <?php else: ?>
    <div class="property-map-empty"><strong>No location recorded</strong><p>Add coordinates or a boundary to show this property on the map.</p></div>
    <?php endif ?>
  </section>
  <?php if (!Auth::owner() && $record['status'] === 'active'): ?><p><a class="button secondary" href="<?= e(url('property-workflow', ['account_number' => $id])) ?>">Open property workflow →</a></p><?php endif ?>
  </div>
  <div id="ownership" data-property-panel>
  <section class="panel">
    <div class="panel-heading"><h2>Ownership history</h2></div>
    <?php data_table($owners, ['full_name' => 'Owner', 'ownership_percentage' => 'Share (%)', 'start_date' => 'From', 'end_date' => 'Until']); $share = 0; foreach ($owners as $o) { if (!$o['end_date']) { $share += (float)($o['ownership_percentage'] ?? 0); } } if ($share && abs($share - 100) > 0.001): ?>
      <div class="alert">Declared current ownership shares total <?= e($share) ?>%, rather than 100%. Review the ownership records.</div>
    <?php endif ?>
  </section>

  <?php if (Auth::role() === 'Administrator'): ?>
    <details class="panel form-panel">
      <summary>Add owner or record a transfer</summary>
      <p>Select an existing owner to link another property to the same person.</p>
      <?php form_start('ownership'); hidden('account_number', $id); field('ratepayer_id', 'New / additional owner', '', 'select', true, App::options('ratepayers')); field('previous_ratepayer_id', 'Replace this owner (leave blank for joint owner)', '', 'select', false, array_column(array_filter($owners, fn($o) => !$o['end_date']), 'full_name', 'ratepayer_id')); field('start_date', 'Effective date', today(), 'date', true); field('ownership_percentage', 'Share (%)', '', 'number'); field('transfer_reference', 'Transfer reference'); submit('Record ownership'); ?>
    </details>
  <?php endif ?>

  </div>
  <section class="panel" id="photos" data-property-panel>
    <div class="panel-heading"><div><p class="eyebrow">Property record</p><h2>Photographs</h2></div></div>
    <?php if (!$photos): ?>
      <div class="empty-state"><strong>No property photographs yet</strong><p>Upload a JPEG or PNG from the Documents section.</p></div>
    <?php else: ?>
      <div class="photo-gallery">
      <?php foreach ($photos as $photo): ?>
        <article class="photo-card">
          <a data-gallery-photo href="<?= e(url('download', ['id' => $photo['document_id'], 'inline' => 1])) ?>" target="_blank" rel="noopener"><img src="<?= e(url('download', ['id' => $photo['document_id'], 'inline' => 1])) ?>" alt="<?= e($photo['caption'] ?: 'Property photograph') ?>" loading="lazy"></a>
          <div class="photo-card-body"><strong><?= e($photo['caption'] ?: $photo['original_filename']) ?></strong><small><?= e($photo['taken_at'] ? substr($photo['taken_at'], 0, 10) : 'Capture date not recorded') ?><?= $photo['is_primary'] ? ' · Primary' : '' ?></small></div>
          <?php if ($write): ?><details class="photo-edit"><summary>Edit details</summary><?php form_start('photo'); hidden('id', $photo['id']); field('caption', 'Caption', $photo['caption']); field('taken_at', 'Capture date', $photo['taken_at'] ? substr($photo['taken_at'], 0, 10) : '', 'date'); field('display_order', 'Order', $photo['display_order'], 'number'); field('is_primary', 'Primary photograph', (bool)$photo['is_primary'], 'checkbox'); submit('Save photo'); ?></details><?php endif ?>
        </article>
      <?php endforeach ?>
      </div>
    <?php endif ?>
  </section>
  <section class="panel" id="bills" data-property-panel>
    <div class="panel-heading"><h2>Annual bills</h2></div>
    <?php data_table($bills, ['bill_number' => 'Bill', 'billing_year' => 'Year', 'principal_amount' => 'Principal', 'penalty_amount' => 'Penalty', 'adjustment_amount' => 'Adjustment', 'paid_amount' => 'Paid', 'due_date' => 'Due', 'status' => 'Status']); ?>
  </section>

  <section class="panel" id="payments" data-property-panel>
    <div class="panel-heading"><h2>Payments</h2></div>
    <?php data_table($payments, ['receipt_number' => 'Receipt', 'payment_date' => 'Date', 'amount' => 'Amount (GHS)', 'payment_method' => 'Method', 'status' => 'Status']); ?>
  </section>

  <?php if ($write): ?>
    <div id="correspondence" data-property-panel><details class="panel form-panel" open>
      <summary>Generate correspondence</summary>
      <?php form_start('correspondence'); hidden('account_number', $id); field('type', 'Document type', '', 'select', true, ['demand_notice' => 'Demand notice', 'invitation' => 'Invitation letter', 'receipt' => 'Payment receipt']); field('bill_id', 'Bill (required for demand notice)', '', 'select', false, array_column($bills, 'bill_number', 'id')); field('payment_id', 'Payment (required for receipt)', '', 'select', false, array_column($payments, 'receipt_number', 'id')); hidden('visibility_submitted','1'); field('owner_visible', 'Visible to authorized owners', true, 'checkbox'); field('printed_date', 'Issue date', today(), 'date', true); field('period_from', 'Invitation period starts', '', 'date'); field('period_to', 'Invitation period ends', '', 'date'); submit('Save correspondence record'); ?>
    </details></div>
  <?php endif ?>

  <div id="documents" data-property-panel>
  <section class="panel">
    <div class="panel-heading"><h2>Linked documents</h2></div>
    <?php foreach ($linked as $doc): ?>
      <a class="record-link" href="<?= e(url('download', ['id' => $doc['id']])) ?>"><?= e($doc['original_filename']) ?><span>Version <?= e($doc['version_number']) ?> ↓</span></a>
    <?php endforeach ?>
    <?php if (!$linked): data_table([]); endif ?>
  </section>

  <?php if ($write): ?>
    <details class="panel form-panel">
      <summary>Upload a document or photograph</summary>
      <?php form_start('upload', true); hidden('entity_type', $type); hidden('entity_id', $id); field('file', 'PDF, JPEG or PNG', '', 'file', true); field('document_type', 'Document type', 'attachment'); field('owner_visible', 'Visible to property owners', false, 'checkbox'); field('previous_version_id', 'Replaces document version', '', 'select', false, array_column(array_filter($linked, fn($d) => $d['is_current_version']), 'original_filename', 'id')); if ($type === 'parcels') { field('photo', 'Property photograph', false, 'checkbox'); field('caption', 'Photo caption'); field('taken_at', 'Capture date', '', 'date'); field('display_order', 'Photo order', 0, 'number'); field('is_primary', 'Primary property photo', false, 'checkbox'); } submit('Upload document'); ?>
    </details>
  <?php endif ?>

  </div>
  <?php if (Auth::role() === 'Administrator'): ?>
    <details class="panel form-panel">
      <summary>Manage ground-rent exemption</summary>
      <p>Exempt parcels appear white on the map. An exemption prevents new annual bills; it does not cancel existing bills, arrears or payments. Every change requires a reason and is audited.</p>
      <?php form_start('exemption'); hidden('account_number', $id); field('is_exempt', 'Exempt from new ground-rent bills', (string)$record['is_exempt'], 'select', true, ['0' => 'No', '1' => 'Yes']); field('reason', 'Reason for this decision', '', 'textarea', true); submit('Save exemption decision'); ?>
    </details>
  <?php endif ?>
</div>
<script defer src="<?= e(config('base_url')) ?>/assets/leaflet/leaflet.js"></script>
<script defer src="<?= e(config('base_url')) ?>/assets/property-pages.js"></script>
<script defer src="<?= e(config('base_url')) ?>/assets/property-gallery.js"></script>
<aside class="property-correspondence"><?php $letterAccount=$id;require __DIR__.'/correspondence-list.php';?></aside></div>
<?php } else { ?>
<div class="page-heading">
  <div>
    <p class="eyebrow"><?= e(App::label($type)) ?></p>
    <h1><?= e($record['property_number'] ?? $record['account_number'] ?? $record['full_name'] ?? $record['parcel_number'] ?? $record['name']) ?></h1>
    <p><?= e($record['land_id'] ?? $record['land_account_number'] ?? $record['locality'] ?? 'Record details') ?></p>
  </div>
  <?php if ($write): ?>
    <a class="button primary" href="<?= e(url('edit', ['type' => $type, 'id' => $id])) ?>">Edit record</a>
  <?php endif ?>
</div>

<section class="panel form-panel">
  <div class="detail-grid">
    <?php foreach ($record as $key => $value): if ((Auth::owner() && in_array($key, ['notes', 'exemption_reason'], true)) || in_array($key, ['id', 'boundary_geojson', 'property_number', 'parcel_number', 'land_account_number'], true) || str_ends_with($key, '_id')) continue; ?>
      <div>
        <small><?= e(ucwords(str_replace('_', ' ', $key))) ?></small>
        <strong><?= e($key === 'is_exempt' ? ($value ? 'Yes' : 'No') : ($value ?? '—')) ?></strong>
      </div>
    <?php endforeach ?>
  </div>
</section>

<?php if ($type === 'parcels' && $record['status'] === 'active'): ?>
  <p><a class="button primary" href="<?= e(url('property-workflow', ['account_number' => $id])) ?>">Open property workflow</a></p>
<?php endif ?>

<?php if ($type === 'parcels'): $owners = DB::all('SELECT o.*, r.full_name, r.ratepayer_type FROM property_ratepayers o JOIN ratepayers r ON r.id = o.ratepayer_id WHERE o.account_number = ? ORDER BY o.start_date DESC', [$id]); ?>
  <section class="panel">
    <div class="panel-heading"><h2>Ownership history</h2></div>
    <?php data_table($owners, ['full_name' => 'Owner', 'ownership_percentage' => 'Share (%)', 'start_date' => 'From', 'end_date' => 'Until']); $share = 0; foreach ($owners as $o) { if (!$o['end_date']) { $share += (float)($o['ownership_percentage'] ?? 0); } } if ($share && abs($share - 100) > 0.001): ?>
      <div class="alert">Declared current ownership shares total <?= e($share) ?>%, rather than 100%. Review the ownership records.</div>
    <?php endif ?>
  </section>
  <?php if (Auth::role() === 'Administrator'): ?>
    <details class="panel form-panel">
      <summary>Add owner or record a transfer</summary>
      <p>Select an existing owner to link another property to the same person.</p>
      <?php form_start('ownership'); hidden('account_number', $id); field('ratepayer_id', 'New / additional owner', '', 'select', true, App::options('ratepayers')); field('previous_ratepayer_id', 'Replace this owner (leave blank for joint owner)', '', 'select', false, array_column(array_filter($owners, fn($o) => !$o['end_date']), 'full_name', 'ratepayer_id')); field('start_date', 'Effective date', today(), 'date', true); field('ownership_percentage', 'Share (%)', '', 'number'); field('transfer_reference', 'Transfer reference'); submit('Record ownership'); ?>
    </details>
  <?php endif ?>
<?php endif ?>

<?php if ($type === 'parcels'): $bills = DB::all('SELECT * FROM ground_rent_bills WHERE account_number = ? ORDER BY billing_year DESC', [$id]); $payments = DB::all('SELECT * FROM payments WHERE account_number = ? ORDER BY payment_date DESC', [$id]); ?>
  <section class="panel">
    <div class="panel-heading"><h2>Annual bills</h2></div>
    <?php data_table($bills, ['bill_number' => 'Bill', 'billing_year' => 'Year', 'principal_amount' => 'Principal', 'penalty_amount' => 'Penalty', 'adjustment_amount' => 'Adjustment', 'paid_amount' => 'Paid', 'due_date' => 'Due', 'status' => 'Status']); ?>
  </section>
  <section class="panel">
    <div class="panel-heading"><h2>Payments</h2></div>
    <?php data_table($payments, ['receipt_number' => 'Receipt', 'payment_date' => 'Date', 'amount' => 'Amount (GHS)', 'payment_method' => 'Method', 'status' => 'Status']); ?>
  </section>
  <?php if ($write): ?>
    <div class="actions"><a class="button primary" href="<?= e(url('payment-new', ['account_number' => $id])) ?>">Record payment for this property</a></div>
    <details class="panel form-panel">
      <summary>Generate correspondence</summary>
      <?php form_start('correspondence'); hidden('account_number', $id); field('type', 'Document type', '', 'select', true, ['demand_notice' => 'Demand notice', 'invitation' => 'Invitation letter', 'receipt' => 'Payment receipt']); field('bill_id', 'Bill (required for demand notice)', '', 'select', false, array_column($bills, 'bill_number', 'id')); field('payment_id', 'Payment (required for receipt)', '', 'select', false, array_column($payments, 'receipt_number', 'id')); field('printed_date', 'Issue date', today(), 'date', true); field('period_from', 'Invitation period starts', '', 'date'); field('period_to', 'Invitation period ends', '', 'date'); submit('Save correspondence record'); ?>
    </details>
  <?php endif ?>
<?php elseif ($type === 'ratepayers'): $phones = DB::all('SELECT * FROM ratepayer_phone_numbers WHERE ratepayer_id = ?', [$id]); ?>
  <section class="panel">
    <div class="panel-heading"><h2>Contact & login numbers</h2></div>
    <?php data_table($phones, ['phone_number' => 'Phone', 'phone_label' => 'Label', 'is_primary' => 'Primary', 'is_verified' => 'Verified', 'can_login' => 'Login enabled']); ?>
  </section>
  <?php if ($write): ?>
    <details class="panel form-panel" open>
      <summary>Add or update a phone number</summary>
      <p>Enter an existing number to update its flags. Administrator verification confirms that phone ownership was checked.</p>
      <?php form_start('phone'); hidden('ratepayer_id', $id); field('phone_number', 'Phone number', '', 'tel', true); field('phone_label', 'Label'); field('is_primary', 'Primary phone', false, 'checkbox'); if (Auth::role() === 'Administrator') { field('is_verified', 'Ownership verified', false, 'checkbox'); field('can_login', 'Enable login', false, 'checkbox'); } submit('Save phone'); ?>
    </details>
  <?php endif ?>
<?php endif ?>

<?php if (in_array($type, ['parcels'], true)): $linked = DB::all('SELECT d.* FROM documents d JOIN document_links l ON l.document_id = d.id WHERE l.account_number = ? ORDER BY d.created_at DESC', [$id]); $linked = array_filter($linked, fn($d) => \Srms\Documents::allowed($d)); ?>
  <section class="panel">
    <div class="panel-heading"><h2>Linked documents</h2></div>
    <?php foreach ($linked as $doc): ?>
      <a class="record-link" href="<?= e(url('download', ['id' => $doc['id']])) ?>"><?= e($doc['original_filename']) ?><span>Version <?= e($doc['version_number']) ?> ↓</span></a>
    <?php endforeach ?>
    <?php if (!$linked): data_table([]); endif ?>
  </section>
  <?php if ($write): ?>
    <details class="panel form-panel">
      <summary>Upload a document or photograph</summary>
      <?php form_start('upload', true); hidden('entity_type', $type); hidden('entity_id', $id); field('file', 'PDF, JPEG or PNG', '', 'file', true); field('document_type', 'Document type', 'attachment'); field('owner_visible', 'Visible to property owners', false, 'checkbox'); field('previous_version_id', 'Replaces document version', '', 'select', false, array_column(array_filter($linked, fn($d) => $d['is_current_version']), 'original_filename', 'id')); if ($type === 'parcels') { field('photo', 'Property photograph', false, 'checkbox'); field('caption', 'Photo caption'); field('taken_at', 'Capture date', '', 'date'); field('display_order', 'Photo order', 0, 'number'); field('is_primary', 'Primary property photo', false, 'checkbox'); } submit('Upload document'); ?>
    </details>
  <?php endif ?>
<?php endif ?>

<?php if ($type === 'parcels' && Auth::role() === 'Administrator'): ?>
  <details class="panel form-panel">
    <summary>Manage ground-rent exemption</summary>
    <p>Exempt parcels appear white on the map. An exemption prevents new annual bills; it does not cancel existing bills, arrears or payments. Every change requires a reason and is audited.</p>
    <?php form_start('exemption'); hidden('account_number', $id); field('is_exempt', 'Exempt from new ground-rent bills', (string)$record['is_exempt'], 'select', true, ['0' => 'No', '1' => 'Yes']); field('reason', 'Reason for this decision', '', 'textarea', true); submit('Save exemption decision'); ?>
  </details>
<?php endif ?>
<?php } ?>
