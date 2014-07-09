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
	<div class="col-md-3"><?php echo __('School Year');?></div>
	<div class="col-md-6"><?php echo $yearOptions[$data['InstitutionSiteFee']['school_year_id']];?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Programme');?></div>
	<div class="col-md-6"><?php echo $data['InstitutionSiteFee']['programme'];?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Education Grade');?></div>
	<div class="col-md-6"><?php echo $data['InstitutionSiteFee']['grade'];?></div>
</div>

<div class="form-group">

<duv class="col-md-3"><?php echo __('Type');?></div>
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
echo $val['InstitutionSiteFeeType']['fee'];?>
</td>
</tr>
<?php 
} ?>
</tbody>
 <tfoot>
 	 <tr>
        <td class="table_cell cell_label"><?php echo __('Total'); ?></td>
        <td class="table_cell cell_value cell_number total_fee"><?php echo $totalFee; ?></td>
    </tr>
</tfoot>
<?php } ?>
</table>
</div>
</div>
<?php
$this->end();
?>
