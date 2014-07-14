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
    <div class="col-md-3"><?php echo $this->Label->get('general.school_year'); ?></div>
    <div class="col-md-6"><?php echo $data['SchoolYear']['name'];?></div>
</div>
<div class="row">
    <div class="col-md-3"><?php echo $this->Label->get('EducationProgramme.name'); ?></div>
    <div class="col-md-6"><?php echo $data['InstitutionSiteStudentFee']['programme'];?></div>
</div>
<div class="row">
    <div class="col-md-3"><?php echo $this->Label->get('EducationGrade.name'); ?></div>
    <div class="col-md-6"><?php echo $data['EducationGrade']['name'];?></div>
</div>
<div class="row">
    <div class="col-md-3"><?php echo $this->Label->get('general.openemisId'); ?></div>
    <div class="col-md-6"><?php echo $data['InstitutionSiteStudentFee']['identification_no'];?></div>
</div>
<div class="row">
    <div class="col-md-3"><?php echo $this->Label->get('general.name'); ?></div>
    <div class="col-md-6"><?php echo $data['InstitutionSiteStudentFee']['name'];?></div>
</div>
<div class="form-group">
<div class="col-md-3"><?php echo $this->Label->get('FinanceFee.paid'); ?></div>
<div class="table-responsive col-md-9">
<table class="table table-striped table-hover table-bordered">
<thead >
    <tr>
	 	<th><span class="left"><?php echo $this->Label->get('general.date'); ?></span></th>
	  	<th><span class="left"><?php echo $this->Label->get('FinanceFee.created'); ?></span></th>
  		<th><span class="left"><?php echo $this->Label->get('general.comment'); ?></span></th>
        <th><span class="left"><?php echo sprintf('%s (%s)',$this->Label->get('FinanceFee.paid'), $currency); ?></span></th>
        <th><span class="left"><?php echo sprintf('%s (%s)',$this->Label->get('FinanceFee.outstanding'), $currency); ?></span></th>
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
<tr><td colspan="5" align="center"><?php echo $this->Label->get('FinanceFee.no_payment'); ?></td></tr>
<?php } ?>
</tbody>
 <tfoot>
    <tr>
        <td colspan="2"></td>
        <td class="table_cell cell_label"><?php echo sprintf('%s (%s)',__('Total'), $currency); ?></td>
        <td class="table_cell cell_value cell_number total_fee" width="15%"><?php echo number_format($totalPaid,2); ?></td>
        <td class="table_cell cell_value cell_number total_outstanding" width="22%"><?php echo number_format($totalFeeTransaction,2); ?></td>
    </tr>
    <tr>
        <td colspan="2"></td>
        <td class="table_cell cell_label"><?php echo sprintf('%s (%s)',__('Total Fee'), $currency); ?></td>
        <td class="table_cell cell_value cell_number total_fee" colspan="2" align="center" width="15%"><?php echo number_format($totalPaid+$totalFeeTransaction,2); ?></td>
    </tr>
</tfoot>

</table>
</div>
</div>
<?php 
$this->end();
?>
