<?php
require_once __DIR__.'/ui.php';
page_heading('Record payment','Find the property, confirm its Account number, then enter the payment.');
?>
<div class="actions"><a class="button" href="<?=e(url('payments'))?>">Back to payment history</a></div>
<section class="panel form-panel">
<h2>Find a property</h2>
<form method="get" action="<?=e(config('base_url'))?>/index.php" class="toolbar">
<input type="hidden" name="r" value="payment-new">
<label class="field" style="flex:1"><span>Account number, owner name or locality</span><input type="search" name="q" value="<?=e($query)?>" minlength="2" maxlength="100" required placeholder="For example EDDT03011010 or owner name"></label>
<button class="button primary" type="submit">Search properties</button>
</form>
<?php if($selected):?><p class="muted">The selected property is shown below. Search again to choose a different property.</p><?php elseif(mb_strlen($query)<2):?><p class="muted">Enter at least two characters to search. You can also open this page from a property record.</p>
<?php elseif(!$matches):?><p role="status">No properties match your search. Check the Account number or try an owner name.</p>
<?php else:?><p role="status">Choose a property below. Showing <?=count($matches)?> results on page <?=$page?>.</p>
<ul class="payment-property-results">
<?php foreach($matches as $match):?><li><a href="<?=e(url('payment-new',['q'=>$query,'page'=>$page,'account_number'=>$match['account_number']]))?>"><strong><?=e($match['account_number'])?></strong><span><?=e($match['owners']?:'Owner not yet recorded')?></span><small><?=e($match['locality']?:'Locality not recorded')?> · <?=e(ucfirst($match['status']))?></small><span class="text-link">Select property →</span></a></li><?php endforeach?>
</ul><div class="pagination">
<?php if($page>1):?><a class="button" href="<?=e(url('payment-new',['q'=>$query,'page'=>$page-1]))?>">Previous results</a><?php endif?>
<?php if($hasMore):?><a class="button" href="<?=e(url('payment-new',['q'=>$query,'page'=>$page+1]))?>">Next results</a><?php endif?>
</div><?php endif?>
</section>
<?php if($selected):?>
<section class="panel form-panel" id="selected-property">
<h2>Selected property: <?=e($selected['account_number'])?></h2>
<p><?=e(implode(', ',array_map(fn($o)=>$o['organization_name']?:$o['full_name'],$owners))?:'Owner not yet recorded')?> · <?=e($selected['locality']?:'Locality not recorded')?></p>
<p>Outstanding bills, including arrears: <strong>GHS <?=money($outstanding)?></strong> · Available credit: <strong>GHS <?=money($selected['credit_amount'])?></strong></p>
<a href="<?=e(url('detail',['type'=>'parcels','id'=>$selected['account_number']]))?>">View property details</a>
<h2>Payment details</h2><p>Payments settle the oldest bills first. Any excess remains as account credit.</p>
<?php form_start('payment');hidden('account_number',$selected['account_number']);echo token_field();field('amount','Amount (GHS)','','number',true);field('payment_date','Date paid',today(),'date',true);field('payment_method','Payment method','','select',true,['cash'=>'Cash','bank'=>'Bank transfer','cheque'=>'Cheque','mobile_money'=>'Mobile money']);field('payer_name','Payer name');field('external_reference','Bank / transaction reference');field('notes','Notes','','textarea');submit('Post payment');?>
</section><?php endif?>
