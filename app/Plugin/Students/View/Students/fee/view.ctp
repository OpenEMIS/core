<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($subheader));

$this->start('contentActions');
echo $this->Html->link(__('List'), array('action' => 'fee'), array('class' => 'divider', 'id'=>'back'));
$this->end();

$this->start('contentBody'); ?>
<div class="row">
    <div class="col-md-3"><?php echo $this->Label->get('general.school_year'); ?></div>
    <div class="col-md-6"><?php echo $data['SchoolYear']['name'];?></div>
</div>
<div class="row">
    <div class="col-md-3"><?php echo $this->Label->get('EducationProgramme.name'); ?></div>
    <div class="col-md-6"><?php echo $data['EducationGrade']['EducationProgramme']['name'];?></div>
</div>
<div class="row">
    <div class="col-md-3"><?php echo $this->Label->get('EducationGrade.name'); ?></div>
    <div class="col-md-6"><?php echo $data['EducationGrade']['name'];?></div>
</div>
<div class="row">
    <div class="col-md-3"><?php echo $this->Label->get('general.openemisId'); ?></div>
    <div class="col-md-6"><?php echo $data['InstitutionSiteStudentFee']['Student']['identification_no'];?></div>
</div>
<div class="row">
    <div class="col-md-3"><?php echo $this->Label->get('general.name'); ?></div>
    <div class="col-md-6"><?php echo sprintf('%s %s', $data['InstitutionSiteStudentFee']['Student']['first_name'], $data['InstitutionSiteStudentFee']['Student']['last_name']);?></div>
</div>
<div class="form-group">
<div class="col-md-3"><?php echo $this->Label->get('FinanceFee.fees'); ?></div>
<div class="table-responsive col-md-9">
<table class="table table-striped table-hover table-bordered">
<thead >
    <tr>
        <th><span class="left"><?php echo $this->Label->get('general.type'); ?></span></th>
        <th><span class="left"><?php echo $this->Label->get('FinanceFee.amount'); ?></span></th>
    </tr>
</thead>
<?php 
$totalFee = $data['InstitutionSiteFee']['total_fee'];
?>
<tbody>
<?php if(!empty($data['InstitutionSiteFeeType'])){
foreach($data['InstitutionSiteFeeType'] as $key=>$val){ ?>
<tr>
<td><?php echo $val['FeeType']['name']; ?></d>
<td><?php echo $val['fee']; ?></td>
</tr>
<?php 
} ?>
<?php }else{ ?>
<tr><td colspan="5" align="center"><?php echo $this->Label->get('FinanceFee.no_fees'); ?></td></tr>
<?php } ?>
</tbody>
 <tfoot>
    <tr>
        <td class="table_cell cell_label"><?php echo __('Total'); ?></td>
        <td class="table_cell cell_value cell_number total_fee" width="15%"><?php echo number_format($totalFee,2); ?></td>
    </tr>
</tfoot>
</table>
</div>
</div>



<div class="form-group">
<div class="col-md-3"><?php echo $this->Label->get('FinanceFee.paid'); ?></div>
<div class="table-responsive col-md-9">
<table class="table table-striped table-hover table-bordered">
<thead >
    <tr>
	 	<th><span class="left"><?php echo $this->Label->get('general.date'); ?></span></th>
        <th><span class="left"><?php echo $this->Label->get('FinanceFee.paid'); ?></span></th>
        <th><span class="left"><?php echo $this->Label->get('FinanceFee.outstanding'); ?></span></th>
    </tr>
</thead>
<?php 
$totalPaid = (isset($data['InstitutionSiteStudentFee']['total_paid']) ? $data['InstitutionSiteStudentFee']['total_paid'] : 0);
$totalFeeTransaction = $totalFee;
?>
<tbody>
<?php if(!empty($institutionSiteStudentFeeTransactions)){
foreach($institutionSiteStudentFeeTransactions as $key=>$val){ 
$totalFeeTransaction = $totalFeeTransaction - $val['InstitutionSiteStudentFeeTransaction']['paid'];
?>
<tr>
<td><?php echo $val['InstitutionSiteStudentFeeTransaction']['paid_date']; ?>
</td>
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
        <td class="table_cell cell_label"><?php echo __('Total'); ?></td>
        <td class="table_cell cell_value cell_number total_paid" width="15%"><?php echo number_format($totalPaid,2); ?></td>
        <td class="table_cell cell_value cell_number total_outstanding" width="15%"><?php echo number_format($totalFeeTransaction,2); ?></td>
    </tr>
    <tr>
        <td class="table_cell cell_label"><?php echo __('Total Fee'); ?></td>
        <td class="table_cell cell_value cell_number total_fee" colspan="2" align="center"><?php echo number_format($totalPaid+$totalFeeTransaction,2); ?></td>
    </tr>
</tfoot>
</table>
</div>
</div>
<?php 
$this->end();
?>
