<?php
$letters=\Srms\CorrespondenceOutput::listing($letterAccount??null,(int)($_GET['letters_page']??1),isset($letterAccount)?'':(string)($_GET['q']??''));
?>
<section class="panel correspondence-list" aria-label="Property correspondence">
 <div class="panel-heading"><div><h2>Correspondence</h2><p><?=number_format($letters['total'])?> saved records · PDFs prepared on demand</p></div></div>
 <?php if(!$letters['rows']):?><div class="empty-state"><strong>No correspondence yet</strong><p>Notices, invitations and receipts will appear here.</p></div><?php endif?>
 <?php foreach($letters['rows'] as $letter):?><article class="correspondence-item">
  <h3><?=e($letter['subject']?:ucwords(str_replace('_',' ',$letter['correspondence_type'])))?></h3>
  <strong><?=e($letter['reference_number'])?></strong>
  <p><?=e($letter['account_number'])?><?=$letter['billing_year']?' · '.e($letter['billing_year']):''?></p>
  <p>Created <?=e(date('d M Y, H:i',strtotime($letter['created_at'])))?> by <?=e($letter['creator'])?><br>Print requests: <?=e($letter['print_count'])?><br>Last requested: <?=e($letter['last_printed']?date('d M Y, H:i',strtotime($letter['last_printed'])):'Never')?></p>
  <form method="post" target="_blank" action="<?=e(url('correspondence-output'))?>" class="correspondence-actions">
   <?=csrf_field()?><?php hidden('id',$letter['id']);hidden('request_key',uuid());?>
   <button class="button small" name="action" value="preview">Preview</button><button class="button small" name="action" value="print">Print</button><button class="button small" name="action" value="download">Download</button>
  </form>
 </article><?php endforeach?>
 <p class="muted">Print opens a PDF for printing. Use the PDF viewer’s print control. Counts record requests, not confirmed physical prints.</p>
 <div class="pagination"><span>Page <?=$letters['page']?> of <?=max(1,ceil($letters['total']/10))?></span><div>
 <?php foreach([-1=>'Previous',1=>'Next'] as $offset=>$label):$target=$letters['page']+$offset;if($target<1||$target>ceil($letters['total']/10))continue;$query=$_GET;$route=$query['r']??'documents';unset($query['r']);$query['letters_page']=$target;?><a class="button small" href="<?=e(url($route,$query))?>"><?=e($label)?></a><?php endforeach?>
 </div></div>
</section>
