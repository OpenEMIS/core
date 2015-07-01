<?php 

$fieldName = (array_key_exists('fieldName', $attr))? $attr['fieldName']: null;
$operation = (array_key_exists('operation', $attr))? $attr['operation']: null;

switch ($fieldName) {
	case 'salary_additions':
	$operand = 'plus';
	$optionName = 'salary_addition';
	break;

	case 'salary_deductions':
	$operand = 'minus';
	$optionName = 'salary_deduction';
	break;
}
$totalAmount = 0;

?>

<div class="input">
	<label class="pull-left" for="<?= $attr['id'] ?>"><?= isset($attr['label']) ? $attr['label'] : $attr['field'] ?></label>
	<div class="col-md-6">
		<table class="table table-striped table-hover table-bordered table-checkable table-input">
			<thead>
				<tr>
					<th><?= $this->Label->get('general.type'); ?></th>
					<th><?= $this->Label->get('general.amount'); ?></th>
				</tr>
			</thead>
			<?php if (!empty($data->$fieldName)) : ?>
				<tbody>
					<?php foreach ($data->$fieldName as $key => $obj) : ?>
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
									$optionsArray['onkeyup'] = 'jsTable.computeTotal(this); jsForm.compute(this); ';
									$optionsArray['allowNull'] = true;
									$optionsArray['onfocus'] = '$(this).select();';
									$optionsArray['before'] = false;
									$optionsArray['between'] = false;
									$optionsArray['data-compute-variable'] = "true";
									$optionsArray['data-compute-operand'] = $operand;
									echo $this->Form->input('Salaries.'.$fieldName.'.'.$key.'.amount', $optionsArray);
								 ?>
								
								
							</td>
							<td> 
								<span class="fa fa-minus-circle" style="cursor: pointer;" title="<?php echo $this->Label->get('general.delete'); ?>" onclick="$(this).closest('tr').remove();"></span>
							</td>
						</tr>
					<?php endforeach ?>
				</tbody>
			<?php endif ?>

			<tfoot>
				<tr>
					<td class="cell-number">Total</td>
					<td class="total_salary_<?php echo $fieldName; ?>s cell-number"><?php echo $totalAmount; ?></td>
					<td/>
				</tr>
			</tfoot>
		</table>
		
		<a class="void icon_plus" onclick="$('#reload').val('<?php echo $operation.'Row'; ?>').click()"><i class="fa fa-plus"></i></a>
	</div>
</div>