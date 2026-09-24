<?php
require_once __DIR__.'/ui.php';
use Srms\Auth;
use Srms\PropertyWorkflow as Flow;

page_heading($title,'Register property → assign owner → assess and approve rent → issue bill → collect payment → archive correspondence.');
?>
<?php if(isset($property)):$r=$property;$number=$r['account_number'];$write=in_array(Auth::role(),['Administrator','Billing Officer'],true);?>
<div class="actions"><a class="button" href="<?=e(Auth::owner()?url('dashboard'):url('workflows'))?>"><?=Auth::owner()?'Back to dashboard':'Back to workflow queues'?></a><a class="button" href="<?=e(url('detail',['type'=>'parcels','id'=>$number]))?>">Open full property record</a></div>
<section class="panel form-panel">
    <div class="panel-heading"><div><h2><?=e($number)?></h2><p><?=e($r['locality']?:'Locality not recorded')?> · <?=e(date('Y'))?> annual workflow</p></div><?=badge($r['stage']==='complete'?'completed':$r['stage'])?></div>
    <div class="workflow-steps" aria-label="Property workflow stages"><?php foreach(Flow::STAGES as $key=>$label):?><span class="pill" <?=$r['stage']===$key?'aria-current="step"':''?>><?=e($label)?></span><?php endforeach?></div>
    <div class="summary-strip">
        <article class="summary-card"><span class="summary-label">Next action</span><strong class="summary-value"><?=e(Flow::STAGES[$r['stage']])?></strong></article>
        <article class="summary-card"><span class="summary-label">Outstanding</span><strong class="summary-value">GHS <?=money($r['outstanding'])?></strong><span class="summary-meta">All bills including arrears</span></article>
        <article class="summary-card"><span class="summary-label">Annual assessment</span><strong class="summary-value"><?=$r['annual_amount']!==null?'GHS '.money($r['annual_amount']):'—'?></strong><span class="summary-meta"><?=e($r['assessment_status']?:'Not assessed')?></span></article>
        <article class="summary-card"><span class="summary-label">Current bill</span><strong class="summary-value"><?=e($r['bill_number']?:'—')?></strong></article>
    </div>
    <?php if($write):switch($r['stage']):case 'owner':?>
        <?php if(Auth::role()==='Administrator'):?><div class="callout"><strong>Administrator action</strong><p>Select an existing owner from the property record. The same owner may be linked to multiple properties.</p></div><a class="button primary" href="<?=e(url('detail',['type'=>'parcels','id'=>$number]))?>">Assign property owner</a><a class="button" href="<?=e(url('edit',['type'=>'ratepayers']))?>">Create owner record</a><?php else:?><p>An Administrator must assign a current owner before assessment.</p><?php endif?>
    <?php break;case 'assessment':form_start('workflow-action');hidden('account_number',$number);hidden('step','assessment');field('annual_amount','Annual ground rent (GHS)','','number',true);field('notes','Assessment notes','','textarea');submit('Submit for approval');break;
    case 'approval':if(Auth::role()==='Administrator'){form_start('workflow-action');hidden('account_number',$number);hidden('step','approval');submit('Approve assessment');}else echo '<p>Waiting for Administrator approval.</p>';break;
    case 'billing':form_start('workflow-action');hidden('account_number',$number);hidden('step','billing');field('due_date','Payment deadline',date('Y-m-d',strtotime('+30 days')),'date',true);submit('Issue annual bill');break;
    case 'payment':?><a class="button primary" href="<?=e(url('payment-new',['account_number'=>$number]))?>">Record payment</a><?php break;
    case 'complete':?><p>Issued bills are fully paid. Receipts and correspondence remain archived against this Account number.</p><?php break;
    case 'exempt':?><p>This property is exempt from new bills. Existing debt and history remain attached to the property.</p><?php break;endswitch;endif?>
</section>
<section class="panel action-panel"><div><h2>Property records</h2><p>Owners, bills, payments, photographs and documents remain linked to the permanent Account number.</p></div><div class="actions"><a class="button" href="<?=e(url('reports',['type'=>'billing','account_number'=>$number]))?>">Bills</a><a class="button" href="<?=e(url('payments',['q'=>$number]))?>">Payments</a><?php if($write):?><a class="button" href="<?=e(url('letter-batches',['accounts'=>$number,'type'=>'demand_notice']))?>">Letters</a><?php endif?></div></section>
<?php else:?>
<div class="summary-strip"><?php foreach(Flow::STAGES as $key=>$label):?><a class="summary-card" href="<?=e(url('workflows',['stage'=>$key]))?>"><span class="summary-label"><?=e($label)?></span><strong class="summary-value"><?=number_format($counts[$key]??0)?></strong><span class="summary-meta">Open queue</span></a><?php endforeach?></div>
<section class="panel">
    <form class="toolbar" method="get"><input type="hidden" name="r" value="workflows"><?php field('q','Account number or locality',$_GET['q']??'','search');field('stage','Next action',$stage,'select',false,Flow::STAGES);?><button class="button primary">Apply filters</button><a class="button" href="<?=e(url('workflows'))?>">Clear</a></form>
    <div class="panel-heading"><div><h2>Property workflow queue</h2><p><?=number_format(count($matching))?> matching active properties</p></div></div>
    <?php if(!$matching):data_table([]);else:?><div class="table-wrap"><table><thead><tr><th>Account number</th><th>Locality</th><th>Next action</th><th>Outstanding</th><th></th></tr></thead><tbody><?php foreach(array_slice($matching,($page-1)*25,25) as $row):?><tr><td><strong><?=e($row['account_number'])?></strong></td><td><?=e($row['locality']?:'—')?></td><td><?=e(Flow::STAGES[$row['stage']])?></td><td>GHS <?=money($row['outstanding'])?></td><td><a class="button small" href="<?=e(url('property-workflow',['account_number'=>$row['account_number']]))?>">Open</a></td></tr><?php endforeach?></tbody></table></div><?php endif?>
    <div class="pagination"><span>Page <?=$page?> of <?=max(1,ceil(count($matching)/25))?></span><div class="actions"><?php if($page>1):?><a class="button small" href="<?=e(url('workflows',['stage'=>$stage,'q'=>$_GET['q']??'','page'=>$page-1]))?>">Previous</a><?php endif?><?php if($page*25<count($matching)):?><a class="button small" href="<?=e(url('workflows',['stage'=>$stage,'q'=>$_GET['q']??'','page'=>$page+1]))?>">Next</a><?php endif?></div></div>
</section>
<?php endif?>
