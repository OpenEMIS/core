<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($subheader));

$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.list'), array('action' => 'fee'), array('class' => 'divider'));
if($_edit) {
	echo $this->Html->link($this->Label->get('general.edit'), array('action' => 'feeEdit', $data[$model]['id']), array('class' => 'divider'));
}
if($_delete) {
	echo $this->Html->link($this->Label->get('general.delete'), array('action' => 'feeDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
}
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

<div class="form-group">

<duv class="col-md-3"><?php echo $this->Label->get('general.type'); ?></div>
<div class="table-responsive col-md-5">
<table class="table table-striped table-hover table-bordered">
<thead >
    <tr>
        <th><span class="left"><?php echo __('Type'); ?></span></th>
        <th><span class="left"><?php echo __('Fee'); ?></span></th>
    </tr>
</thead>
<?php if(!empty($institutionSiteFeeTypes)){ ?>
<tbody>
<?php 
$totalFee = $data['InstitutionSiteFee']['total_fee'];
foreach($institutionSiteFeeTypes as $key=>$val){ ?>
<tr>
<td><?php echo $val['FeeType']['name']; ?></td>
<td>
<?php
echo $val['fee'];?>
</td>
</tr>
<?php 
} ?>
</tbody>
 <tfoot>
 	 <tr>
        <td class="table_cell cell_label"><?php echo __('Total'); ?></td>
        <td class="table_cell cell_value cell_number total_fee" width="15%"><?php echo $totalFee; ?></td>
    </tr>
</tfoot>
<?php } ?>
</table>
</div>
</div>
<?php
$this->end();
?>
