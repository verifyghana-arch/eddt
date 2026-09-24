<?php
require_once __DIR__.'/ui.php';
use Srms\Auth;
page_heading('Documents & correspondence','Find secure originals, photographs, archived notices, invitations and receipts by Account number.');
?>
<?php require __DIR__.'/correspondence-list.php';?>
<?php if(in_array(Auth::role(),['Administrator','Billing Officer'],true)):?><div class="actions"><a class="button primary" href="<?=e(url('letter-batches'))?>">Batch demand notices / invitations</a></div><?php endif?>
<section class="panel">
    <form class="toolbar" method="get"><input type="hidden" name="r" value="documents"><?php field('q','Account, locality or filename',$q,'search');field('document_type','Document type',$type,'select',false,$types);?><button class="button primary">Apply filters</button><a class="button" href="<?=e(url('documents'))?>">Clear</a></form>
    <div class="panel-heading"><div><h2>Document library</h2><p><?=number_format($total)?> matching files</p></div></div>
    <?php if(!$rows):data_table([]);else:?><div class="table-wrap"><table><thead><tr><th>Document</th><th>Account</th><th>Type</th><th>Version</th><th>Visibility</th><th>Uploaded</th><th>Actions</th></tr></thead><tbody>
    <?php foreach($rows as $doc):?><tr><td><strong><?=e($doc['original_filename'])?></strong><small class="cell-sub"><?=e(number_format($doc['file_size_bytes']/1024,1))?> KB · <?=e($doc['mime_type'])?></small></td><td><a href="<?=e(url('detail',['type'=>'parcels','id'=>$doc['account_number']]))?>"><?=e($doc['account_number'])?></a><small class="cell-sub"><?=e($doc['locality']?:'')?></small></td><td><?=e(ucwords(str_replace('_',' ',$doc['document_type'])))?></td><td><?=e($doc['version_number'])?><?=$doc['is_current_version']?'':' · previous'?></td><td><?=e($doc['owner_visible']?'Owner visible':'Internal')?></td><td><?=e(substr($doc['created_at'],0,10))?></td><td><a href="<?=e(url('download',['id'=>$doc['id'],'inline'=>1]))?>" target="_blank" rel="noopener">Preview</a> · <a href="<?=e(url('download',['id'=>$doc['id']]))?>">Download</a></td></tr><?php endforeach?></tbody></table></div><?php endif?>
    <div class="pagination"><span>Page <?=$page?> of <?=max(1,ceil($total/$size))?></span><div class="actions"><?php if($page>1):?><a class="button small" href="<?=e(url('documents',['q'=>$q,'document_type'=>$type,'page'=>$page-1]))?>">Previous</a><?php endif?><?php if($page*$size<$total):?><a class="button small" href="<?=e(url('documents',['q'=>$q,'document_type'=>$type,'page'=>$page+1]))?>">Next</a><?php endif?></div></div>
</section>
<?php if(in_array(Auth::role(),['Administrator','Billing Officer'],true)):?><section class="panel callout-panel"><div><h2>Upload files and photographs</h2><p>Open a property record to upload files, set owner visibility, link versions, and manage photographs.</p></div><a class="button" href="<?=e(url('register',['type'=>'parcels']))?>">Find a property</a></section><?php endif?>
