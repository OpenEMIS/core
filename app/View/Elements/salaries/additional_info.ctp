<?php 
$tableFooter = array(
	array(
		array(__('Total'), array('class'=>'cell-number')),
		array((isset($this->data[$model][$name.'s'])?$this->data[$model][$name.'s']: 0), array('class'=>'total_salary_'.$name.'s cell-number')),
		'&nbsp;'
		));
		?>
		<div class="form-group">
			<label class="col-md-3 control-label"><?php echo $title; ?></label>
			<div class="col-md-9">
				<div class="table-responsive">
					<table class="table table-striped table-hover table-bordered" id='table-additions'>
						<thead>
							<tr><?php echo $this->Html->tableHeaders($tableHeaders); ?></tr>
						</thead>

						<?php 
						switch ($name) {
							case 'addition':
							$operand = 'plus';
							break;

							case 'deduction':
							$operand = 'minus';
							break;
						}
						$index = 0;
						foreach ($data as $key => $value) {
							?>
							<tr>
								<td>
									<?php 
									if (array_key_exists('id', $value)) {
										echo $this->Form->input($modelName.'.' . $index . '.id', array('type' => 'hidden', 'class' => $name.'-control-id', 'label' => false, 'value' => $value['id'])); 
									}
									?>
									<?php 
									$currFormOption = array(
										'div' => false,
										'label' => false, 
										'options' => $options,
										'before' => false,
										'between' => false
										);
									if ($action == 'add') $currFormOption['empty'] = __('--Select');
									echo $this->Form->input($modelName.'.' . $index . '.'.$foreignKeyName, $currFormOption); 
									?>
								</td>
								<td>
									<?php
									echo $this->Form->input($modelName.'.' . $index . '.amount', array(
										'type' => 'string',
										'maxlength' => 11,
										'div' => false,
										'class' => 'form-control '.$name.'_amount',
										'label' => false,
										'computeType' => 'total_salary_'.$name.'s',
										'onkeypress' => 'return utility.floatCheck(event)',
										'onkeyup' => 'jsTable.computeTotal(this); jsForm.compute(this); ',
										'before' => false,
										'between' => false, 
										'data-compute-variable' => "true", 
										'data-compute-operand' => $operand
										)
									);
									?>
								</td>
								<td> 
									<span class="icon_delete" title="<?php echo $this->Label->get('general.delete'); ?>" onClick="$(this).closest('tr').remove()"></span>
								</td>
							</tr>

							<?php 
							$index++;
						} ?>

						<tfoot>
							<tr>
								<td class="cell-number">Total</td>
								<td class="total_salary_<?php echo $name; ?>s cell-number">0</td>
								<td/>
							</tr>
						</tfoot>
					</table>
				</div>

				<?php
				echo $this->Html->link($this->Label->get('general.add'), array(), array('onclick' => "$('#reload').val('".$name."').click();", 'class' => 'void icon_plus'));
				?>

			</div>
		</div>

