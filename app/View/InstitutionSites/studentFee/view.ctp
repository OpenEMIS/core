<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($subheader));

$this->start('contentActions');
echo $this->Html->link(__('List'), array('action' => 'studentFee'), array('class' => 'divider', 'id'=>'back'));
if($_add) {
    echo $this->Html->link(__('Add'), array('action' => 'studentFeeAddTransaction'), array('class' => 'divider', 'id'=>'add'));
}
$this->end();

$this->start('contentBody'); ?>
<div class="row">
    <div class="col-md-3"><?php echo __('School Year');?></div>
    <div class="col-md-6"><?php echo $data['SchoolYear']['name'];?></div>
</div>
<div class="row">
    <div class="col-md-3"><?php echo __('Programme');?></div>
    <div class="col-md-6"><?php echo $data['InstitutionSiteStudentFee']['programme'];?></div>
</div>
<div class="row">
    <div class="col-md-3"><?php echo __('Education Grade');?></div>
    <div class="col-md-6"><?php echo $data['InstitutionSiteStudentFee']['programme'];?></div>
</div>
<div class="row">
    <div class="col-md-3"><?php echo __('Identification No');?></div>
    <div class="col-md-6"><?php echo $data['InstitutionSiteStudentFee']['identification_no'];?></div>
</div>
<div class="row">
    <div class="col-md-3"><?php echo __('Name');?></div>
    <div class="col-md-6"><?php echo $data['InstitutionSiteStudentFee']['name'];?></div>
</div>
<div class="form-group">
<div class="col-md-3"><?php echo __('Transaction');?></div>
<div class="table-responsive col-md-9">
<table class="table table-striped table-hover table-bordered">
<thead >
    <tr>
	 	<th><span class="left"><?php echo __('Date'); ?></span></th>
	  	<th><span class="left"><?php echo __('Created'); ?></span></th>
  		<th><span class="left"><?php echo __('Comments'); ?></span></th>
        <th><span class="left"><?php echo __('Paid'); ?></span></th>
        <th><span class="left"><?php echo __('Outstanding'); ?></span></th>
    </tr>
</thead>
<?php 
$totalFee = $data['InstitutionSiteFee']['total_fee'];
$totalPaid = (isset($studentFeeData['InstitutionSiteStudentFee']['total_paid']) ? $studentFeeData['InstitutionSiteStudentFee']['total_paid'] : 0);
$totalFeeTransaction = $totalFee;
?>
<tbody>
<?php if(!empty($institutionSiteStudentFeeTransactions)){
foreach($institutionSiteStudentFeeTransactions as $key=>$val){ 
$totalFeeTransaction = $totalFeeTransaction - $val['InstitutionSiteStudentFeeTransaction']['paid'];
?>
<tr>
<td><?php echo $this->Html->link($val['InstitutionSiteStudentFeeTransaction']['paid_date'], array('action' => 'studentFeeViewTransaction', $val['InstitutionSiteStudentFeeTransaction']['id']), array('escape' => false)); ?>
</td>
<td><?php echo sprintf('%s %s', $val['CreatedUser']['first_name'], $val['CreatedUser']['last_name']); ?></td>
<td><?php echo $val['InstitutionSiteStudentFeeTransaction']['comments'];  ?></td>
<td><?php echo $val['InstitutionSiteStudentFeeTransaction']['paid']; ?></td>
<td><?php echo number_format($totalFeeTransaction>=0 ? $totalFeeTransaction : 0, 2); ?></td>
</tr>
<?php 
} ?>
<?php }else{ ?>
<tr><td colspan="5" align="center"><?php echo __('No Transaction Records.'); ?></td></tr>
<?php } ?>
</tbody>
 <tfoot>
    <tr>
        <td colspan="2"></td>
        <td class="table_cell cell_label"><?php echo __('Total'); ?></td>
        <td class="table_cell cell_value cell_number total_fee"><?php echo number_format($totalPaid,2); ?></td>
        <td class="table_cell cell_value cell_number total_outstanding"><?php echo number_format($totalFeeTransaction,2); ?></td>
    </tr>
</tfoot>

</table>
</div>
</div>
<?php 
$this->end();
?>
