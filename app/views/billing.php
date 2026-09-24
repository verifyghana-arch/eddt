<?php
require_once __DIR__.'/ui.php';
use Srms\Auth;
$write=in_array(Auth::role(),['Administrator','Billing Officer'],true);
page_heading('Ground-rent billing','Move properties from assessment through approval and annual billing without duplicating financial history.');
?>
<div class="actions">
    <a class="button primary" href="<?=e(url('workflows',['stage'=>'assessment']))?>">Properties awaiting assessment</a>
    <?php if(Auth::role()==='Administrator'):?><a class="button" href="<?=e(url('workflows',['stage'=>'approval']))?>">Approval queue</a><?php endif?>
    <a class="button" href="<?=e(url('workflows',['stage'=>'billing']))?>">Ready to bill</a>
    <?php if($write):?><a class="button" href="<?=e(url('letter-batches'))?>">Batch letters</a><?php endif?>
</div>
<div class="summary-strip">
    <article class="summary-card"><span class="summary-label">Assessment year</span><strong class="summary-value"><?=e($year)?></strong><span class="summary-meta"><?=number_format((int)$summary['assessments'])?> assessments</span></article>
    <article class="summary-card"><span class="summary-label">Awaiting approval</span><strong class="summary-value"><?=number_format((int)$summary['pending'])?></strong><span class="summary-meta">Administrator action</span></article>
    <article class="summary-card"><span class="summary-label">Bills issued</span><strong class="summary-value"><?=number_format((int)$summary['issued'])?></strong><span class="summary-meta">For <?=e($year)?></span></article>
    <article class="summary-card"><span class="summary-label">Outstanding</span><strong class="summary-value">GHS <?=money($summary['outstanding'])?></strong><span class="summary-meta">Selected year</span></article>
</div>
<section class="panel">
    <form class="toolbar" method="get">
        <input type="hidden" name="r" value="billing">
        <?php field('year','Billing year',$year,'number',true);field('status','Stage',$status,'select',false,['pending'=>'Awaiting approval','approved'=>'Approved','issued'=>'Bill issued']);field('q','Account, locality or bill',$q,'search'); ?>
        <button class="button primary">Apply filters</button><a class="button" href="<?=e(url('billing'))?>">Clear</a>
    </form>
    <div class="panel-heading"><div><h2>Assessment and billing register</h2><p><?=number_format($total)?> matching records</p></div></div>
    <?php if(!$rows):data_table([]);else:?>
    <div class="table-wrap"><table><thead><tr><th>Account</th><th>Locality</th><th>Assessment</th><th>Stage</th><th>Bill</th><th>Paid</th><th>Outstanding</th><th>Action</th></tr></thead><tbody>
    <?php foreach($rows as $row):?><tr>
        <td><a href="<?=e(url('property-workflow',['account_number'=>$row['account_number']]))?>"><strong><?=e($row['account_number'])?></strong></a></td>
        <td><?=e($row['locality']?:'—')?></td><td>GHS <?=money($row['annual_amount'])?></td><td><?=badge($row['bill_id']?'issued':$row['assessment_status'])?></td>
        <td><?=e($row['bill_number']?:'—')?></td><td>GHS <?=money($row['paid_amount']??'0')?></td><td>GHS <?=money($row['outstanding']??'0')?></td>
        <td><?php if($row['assessment_status']==='pending'&&Auth::role()==='Administrator'):?><form method="post" action="<?=e(url('approve'))?>"><?=csrf_field()?><?php hidden('id',$row['assessment_id']);?><button class="button small">Approve</button></form><?php elseif($row['assessment_status']==='approved'&&!$row['bill_id']&&$write):?><form class="inline-form" method="post" action="<?=e(url('issue'))?>"><?=csrf_field()?><?php hidden('id',$row['assessment_id']);?><input type="date" name="due_date" value="<?=e(date('Y-m-d',strtotime('+30 days')))?>" aria-label="Payment deadline" required><button class="button small">Issue</button></form><?php else:?><a href="<?=e(url('detail',['type'=>'parcels','id'=>$row['account_number']]))?>">View</a><?php endif?></td>
    </tr><?php endforeach?></tbody></table></div>
    <?php endif?>
    <div class="pagination"><span>Page <?=$page?> of <?=max(1,ceil($total/$size))?></span><div class="actions"><?php if($page>1):?><a class="button small" href="<?=e(url('billing',['year'=>$year,'status'=>$status,'q'=>$q,'page'=>$page-1]))?>">Previous</a><?php endif?><?php if($page*$size<$total):?><a class="button small" href="<?=e(url('billing',['year'=>$year,'status'=>$status,'q'=>$q,'page'=>$page+1]))?>">Next</a><?php endif?></div></div>
</section>
<?php if($write):?>
<details class="panel form-panel"><summary>Issue all approved assessments for a year</summary><p>Existing bills are preserved. Available property credit is applied automatically.</p><?php form_start('batch');field('year','Approved assessment year',$year,'number',true);field('due_date','Payment deadline',date('Y-m-d',strtotime('+30 days')),'date',true);submit('Issue approved assessments');?></details>
<?php $billOptions=[];foreach($rows as $row)if($row['bill_id'])$billOptions[$row['bill_id']]=$row['bill_number'].' · '.$row['account_number'];?>
<details class="panel form-panel"><summary>Record a penalty or adjustment</summary><p>Select an issued bill from the filtered register. Adjustments preserve the bill and audit history.</p><?php if($billOptions):form_start('adjust');field('id','Issued bill','','select',true,$billOptions);field('adjustment_type','Type','','select',true,['penalty'=>'Penalty','adjustment'=>'Adjustment']);field('amount','Amount (GHS)','','number',true);field('reason','Reason','','textarea',true);submit('Record adjustment');else:?><p class="muted">No issued bills are visible in this result set.</p><?php endif?></details>
<?php endif?>
