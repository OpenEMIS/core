<?php
echo $this->Html->script('institution_site_fee', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($subheader));

$this->start('contentActions');
if($_add) {
    echo $this->Html->link(__('Add'), array('action' => 'studentFeeAddTransaction'), array('class' => 'divider', 'id'=>'add'));
}
echo $this->Html->link(__('Back'), array('action' => 'feeStudent'), array('class' => 'divider', 'id'=>'back'));
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $this->params['action']));
echo $this->Form->create($model, $formOptions);
echo isset($this->request->data['InstitutionSiteFeeStudent']['id']) ? $this->Form->hidden('id') : '';
echo $this->Form->input('school_year_id', array('options' => $yearOptions, 'default'=>$selected_year, 'readonly'=>'readonly'));
echo $this->Form->input('programme', array('readonly' => 'readonly'));
echo $this->Form->input('education_grade', array('readonly' => 'readonly'));
echo $this->Form->input('identification_no', array('readonly' => 'readonly'));
echo $this->Form->input('name', array('readonly' => 'readonly'));
?>
<div class="form-group">
<label class="col-md-3 control-label"><?php echo __('Transaction');?></label>
<div class="table-responsive col-md-9">
<table class="table table-striped table-hover table-bordered">
<thead >
    <tr>
	 	<th><span class="left"><?php echo __('Date'); ?></span></th>
	  	<th><span class="left"><?php echo __('Created'); ?></span></th>
  		<th><span class="left"><?php echo __('Comments'); ?></span></th>
        <th><span class="left"><?php echo __('Type'); ?></span></th>
        <th><span class="left"><?php echo __('Paid'); ?></span></th>
        <th><span class="left"><?php echo __('Outstanding'); ?></span></th>
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
	//'class' => $record_tag,
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
        <td class="table_cell cell_label"><?php echo __('Total'); ?></td>
        <td class="table_cell cell_value cell_number total_fee"><?php echo $totalFee; ?></td>
    </tr>
</tfoot>
<?php } ?>
</table>
</div>
</div>
<?php 
echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'studentFee')));
echo $this->Form->end();

$this->end();
?>
