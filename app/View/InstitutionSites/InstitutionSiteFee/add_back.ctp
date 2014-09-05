<?php
echo $this->Html->script('institution_site_fee', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($subheader));

$this->start('contentActions');
if(isset($this->request->data['InstitutionSiteFee']['id'])){
	echo $this->Html->link($this->Label->get('general.back'), array('action' => 'feeView', $this->request->data['InstitutionSiteFee']['id']), array('class' => 'divider'));
}else{
	echo $this->Html->link($this->Label->get('general.back'), array('action' => 'fee'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions();
echo $this->Form->create($model, $formOptions);
echo isset($this->request->data['InstitutionSiteFee']['id']) ? $this->Form->hidden('id') : '';
echo $this->Form->hidden('school_year_id');
echo $this->Form->hidden('programme_id');
echo $this->Form->hidden('education_grade_id');

echo $this->Form->input('school_year', array('disabled'=>'disabled'));
echo $this->Form->input('programme', array('disabled'=>'disabled'));
echo $this->Form->input('education_grade', array('disabled'=>'disabled'));

?>
<div class="form-group">
<label class="col-md-3 control-label"><?php echo $this->Label->get('general.type'); ?></label>
<div class="table-responsive col-md-6">
<table class="table table-striped table-hover table-bordered">
<thead >
    <tr>
        <th><span class="left"><?php echo $this->Label->get('general.type'); ?></span></th>
        <th><span class="left"><?php echo sprintf('%s (%s)',$this->Label->get('FinanceFee.fee'), $currency); ?></span></th>
    </tr>
</thead>
<?php if(isset($this->request->data['InstitutionSiteFeeType'])){ ?>
<tbody>
<?php 
$totalFee = $this->request->data['InstitutionSiteFee']['total_fee'];
foreach($this->request->data['InstitutionSiteFeeType'] as $key=>$val){ ?>
<?php echo $this->Form->input('InstitutionSiteFeeType.'.$key.'.id', array('type'=> 'hidden'));?>
<?php echo $this->Form->input('InstitutionSiteFeeType.'.$key.'.fee_type_name', array('type'=> 'hidden'));?>
<?php echo $this->Form->input('InstitutionSiteFeeType.'.$key.'.fee_type_id', array('type'=> 'hidden'));?>
<tr>
	<td><?php echo $val['fee_type_name']; ?></td>
	<td>
	<?php
	echo $this->Form->input('InstitutionSiteFeeType.'.$key.'.fee', array(
		'type' => 'text',
		'div'=>false, 'label'=>false, 'between'=>false, 'after'=>false,
		'computeType' => 'total_fee',
		'allowNull' => true,
		'maxlength' => 10,
		'onkeypress' => 'return utility.integerCheck(event)',
		'onkeyup' => 'jsTable.computeTotal(this)'
	));
	?>
	</td>
</tr>
<?php 
} ?>

</tbody>
 <tfoot>
 	<?php echo $this->Form->input('total_fee', array('type'=> 'hidden', 'value'=>$totalFee));?>
    <tr>
        <td class="cell_label"><?php echo sprintf('%s (%s)',$this->Label->get('FinanceFee.total'), $currency); ?></td>
        <td class="cell_value cell_number total_fee"><?php echo $totalFee; ?></td>
    </tr>
</tfoot>
<?php } ?>
</table>
</div>
</div>
<?php 
echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'fee')));
echo $this->Form->end();

$this->end();
?>
