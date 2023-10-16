<?php 

$fieldName = (array_key_exists('fieldName', $attr))? $attr['fieldName']: null;
$operation = (array_key_exists('operation', $attr))? $attr['operation']: null;
$totalAmount = 0;
$addBtnName = "";

switch ($fieldName) {
	case 'salary_additions':
	$operand = 'plus';
	$optionName = 'salary_addition';
	$totalAmount = $data->additions;
	$addBtnName = "Addition";
	break;

	case 'salary_deductions':
	$operand = 'minus';
	$optionName = 'salary_deduction';
	$totalAmount = $data->deductions;
	$addBtnName = "Deduction";
	break;
}	
?>
<script type="text/javascript">
	$(function(){ 
		$(".total_salary_<?= $fieldName; ?>s").val(<?= $totalAmount; ?>);

		//calculate the row values added upon loading
		jsTable.computeTotalForMoney('total_salary_<?= $fieldName; ?>s');
		jsForm.compute(this);
	});
</script>
<div class="input">
	<label for="<?= $attr['id'] ?>"><?= isset($attr['label']) ? $attr['label'] : $attr['field'] ?></label>
	<div class="input-form-wrapper">
		<div class="table-toolbar">
			<button class="btn btn-default btn-xs" onclick="$('#reload').val('<?php echo $operation.'Row'; ?>').click(); return false;">
				<i class="fa fa-plus"></i> 
				<span><?= __('Add '.$addBtnName) ?></span>
			</button>
		</div>
		<div class="table-wrapper">
			<div class="table-in-view">
				<table class="table table-checkable table-input">
					<thead>
						<tr>
							<th><?= $this->Label->get('general.type'); ?></th>
							<th><?= $this->Label->get('general.amount'); ?></th>
							<th></th>
						</tr>
					</thead>
					<?php if (!empty($data->{$fieldName})) : ?>
						<tbody id='table_total_salary_<?php echo $fieldName; ?>s'>
							<?php foreach ($data->{$fieldName} as $key => $obj) : ?>
								<tr>
									<td>
										<?php 
										if (array_key_exists('amount', $obj)) {
											$totalAmount += $obj['amount'];
										}
										if (array_key_exists('id', $obj)) {
											echo $this->Form->input('Salaries.'.$fieldName.'.'.$key.'.id', array('type' => 'hidden', 'class' => $fieldName.'-control-id', 'label' => false, 'value' => $obj['id'])); 
										}
										?>
										<?php
											$optionsArray = [];
											$optionsArray['options'] = $attr['fieldOptions'];
											$optionsArray['label'] = false;
											$optionsArray['before'] = false;
											$optionsArray['between'] = false;
											$this->Form->unlockField('Salaries.'.$fieldName.'.'.$key.'.'.$optionName.'_type_id', $optionsArray);
											echo $this->Form->input('Salaries.'.$fieldName.'.'.$key.'.'.$optionName.'_type_id', $optionsArray);
										?>
									</td>
									<td>
										<?php 
											$optionsArray = [];
											$optionsArray['type'] = 'string';
											$optionsArray['maxlength'] = 9;
											$optionsArray['div'] = false;
											$optionsArray['class'] = 'form-control '.$fieldName.'_amount';
											$optionsArray['label'] = false;
											$optionsArray['computeType'] = 'total_salary_'.$fieldName.'s';
											$optionsArray['onkeypress'] = 'return utility.floatCheck(event)';
											$optionsArray['onkeyup'] = 'jsTable.computeTotalForMoney("total_salary_'.$fieldName.'s"); jsForm.compute(this); ';
											$optionsArray['allowNull'] = true;
											$optionsArray['onfocus'] = '$(this).select();';
											$optionsArray['before'] = false;
											$optionsArray['between'] = false;
											$optionsArray['data-compute-variable'] = "true";
											$optionsArray['data-compute-operand'] = $operand;
											$this->Form->unlockField('Salaries.'.$fieldName.'.'.$key.'.amount', $optionsArray);
											echo $this->Form->input('Salaries.'.$fieldName.'.'.$key.'.amount', $optionsArray);
										 ?>
									</td>
									<td> 
										<button onclick="jsTable.doRemove(this);jsTable.computeTotalForMoney('total_salary_<?php echo $fieldName; ?>s');jsForm.compute(this);" title="Delete" style="cursor: pointer;" class="btn btn-dropdown action-toggle btn-single-action">
											<i class="fa fa-trash"></i>
											<span>Delete</span>
										</button>
									</td>
								</tr>
							<?php endforeach ?>
						</tbody>
					<?php endif ?>

					<tfoot>
						<tr>
							<td class="cell-number"><?= __('Total') ?></td>
							<td class="total_salary_<?php echo $fieldName; ?>s cell-number"><?php echo $totalAmount; ?></td>
							<td/>
						</tr>
					</tfoot>
				</table>
			</div>
		</div>
	</div>
</div>