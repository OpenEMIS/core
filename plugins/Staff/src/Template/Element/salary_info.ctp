<?php 
$salary_additions = ($data->has('salary_additions'))? $data->salary_additions: [];
$salary_deductions = ($data->has('salary_deductions'))? $data->salary_deductions: [];

$fieldName = (array_key_exists('fieldName', $attr))? $attr['fieldName']: null;
$operation = (array_key_exists('operation', $attr))? $attr['operation']: null;
// pr($attr);
?>

<div class="input">
		<label class="pull-left" for="<?= $attr['id'] ?>"><?= $this->ControllerAction->getLabel($attr['model'], $attr['field'], $attr) ?></label>
		<div class="col-md-6">
			<table class="table table-striped table-hover table-bordered table-checkable table-input">
				<thead>
					<tr>
						<th><?= $this->Label->get('general.type'); ?></th>
						<th><?= $this->Label->get('general.amount'); ?></th>
					</tr>
				</thead>
				<?php //if (!empty($data->custom_field_options)) : ?>
					<tbody>
						<?php //foreach ($data->custom_field_options as $key => $obj) : ?>
							<tr>
								<td>
									<?php
										$optionsArray = [];
										$optionsArray['options'] = $attr['fieldOptions'];
										$optionsArray['label'] = false;
										$optionsArray['before'] = false;
										$optionsArray['between'] = false;
										echo $this->Form->input('something', $optionsArray);
									?>
								</td>
								<td>
									<?php 
										$optionsArray = [];
										$optionsArray['type'] = 'string';
										$optionsArray['maxlength'] = 9;
										$optionsArray['div'] = false;
										// $optionsArray['class'] = 'form-control '.$name.'_amount';
										$optionsArray['label'] = false;
										// $optionsArray['computeType'] = 'total_salary_'.$name.'s';
										$optionsArray['onkeypress'] = 'return utility.floatCheck(event)';
										$optionsArray['onkeyup'] = 'jsTable.computeTotal(this); jsForm.compute(this); ';
										$optionsArray['allowNull'] = true;
										$optionsArray['onfocus'] = '$(this).select();';
										$optionsArray['before'] = false;
										$optionsArray['between'] = false;
										$optionsArray['data-compute-variable'] = "true";
										// $optionsArray['data-compute-operand'] = $operand;
										echo $this->Form->input('something', $optionsArray);

									 ?>
								</td>
								<td> 
									<span class="icon_delete" title="<?php echo $this->Label->get('general.delete'); ?>" onClick="$(this).closest('tr').remove()">WIP delete</span>
								</td>
							</tr>
						<?php //endforeach ?>
					</tbody>
				<?php //endif ?>

				<tfoot>
					<tr>
						<td class="cell-number">Total</td>
						<!-- <td class="total_salary_<?php echo $name; ?>s cell-number"><?php echo $totalAmount; ?></td> -->
						<td/>
					</tr>
				</tfoot>
			</table>
			
			<a class="void icon_plus" onclick="$('#reload').val('<?php echo $operation; ?>').click()"><i class="fa fa-plus"></i></a>
		</div>
	</div>




<?php 
// $tableFooter = array(
// 	array(
// 		array(__('Total'), array('class'=>'cell-number')),
// 		array((isset($this->data[$model][$name.'s'])?$this->data[$model][$name.'s']: 0), array('class'=>'total_salary_'.$name.'s cell-number')),
// 		'&nbsp;'
		// ));
		?>
		<!-- <div class="form-group">
			<label class="col-md-3 control-label"><?php echo $title; ?></label>
			<div class="col-md-9">
				<div class="table-responsive">
					<table class="table table-striped table-hover table-bordered" id='table-additions'>
						<thead>
							<tr><?php echo $this->Html->tableHeaders($tableHeaders); ?></tr>
						</thead> -->

						<?php 
						// switch ($name) {
						// 	case 'addition':
						// 	$operand = 'plus';
						// 	break;

						// 	case 'deduction':
						// 	$operand = 'minus';
						// 	break;
						// }
						// $index = 0;
						// $totalAmount = 0;
						// foreach ($data as $key => $value) {
							?>
							<!-- <tr>
								<td> -->
									<?php 
									// if (array_key_exists('id', $value)) {
									// 	echo $this->Form->input($modelName.'.' . $index . '.id', array('type' => 'hidden', 'class' => $name.'-control-id', 'label' => false, 'value' => $value['id'])); 
									// }
									?>
									<?php 
									// $currFormOption = array(
									// 	'div' => false,
									// 	'label' => false, 
									// 	'options' => $options,
									// 	'before' => false,
									// 	'between' => false
									// 	);
									// // if ($action == 'add') $currFormOption['empty'] = __('--Select');
									// echo $this->Form->input($modelName.'.' . $index . '.'.$foreignKeyName, $currFormOption); 
									?>
								<!-- </td>
								<td> -->
									<?php
									// $totalAmount += $value['amount'];
									// echo $this->Form->input($modelName.'.' . $index . '.amount', array(
									// 	'type' => 'string',
									// 	'maxlength' => 9,
									// 	'div' => false,
									// 	'class' => 'form-control '.$name.'_amount',
									// 	'label' => false,
									// 	'computeType' => 'total_salary_'.$name.'s',
									// 	'onkeypress' => 'return utility.floatCheck(event)',
									// 	'onkeyup' => 'jsTable.computeTotal(this); jsForm.compute(this); ',
									// 	'allowNull' => true,
									// 	'onfocus' => '$(this).select();',
									// 	'before' => false,
									// 	'between' => false, 
									// 	'data-compute-variable' => "true", 
									// 	'data-compute-operand' => $operand
									// 	)
									// );
									?>
								<!-- </td>
								<td> 
									<span class="icon_delete" title="<?php echo $this->Label->get('general.delete'); ?>" onClick="$(this).closest('tr').remove()"></span>
								</td>
							</tr>
 -->
							<?php 
							// $index++;
						// } ?>

						<!-- <tfoot>
							<tr>
								<td class="cell-number">Total</td>
								<td class="total_salary_<?php echo $name; ?>s cell-number"><?php echo $totalAmount; ?></td>
								<td/>
							</tr>
						</tfoot>
					</table>
				</div> -->

				<?php
				// echo $this->Html->link($this->Label->get('general.add'), array(), array('onclick' => "$('#reload').val('".$name."').click();", 'class' => 'void icon_plus'));
				?>

			<!-- </div>
		</div> -->

