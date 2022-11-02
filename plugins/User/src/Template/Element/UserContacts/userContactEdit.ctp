<?php pr($data->toArray()); ?>

<div class="table-wrapper">
	<div class="table-responsive">
		<table class="table table-curved">
			<thead class="table_head">
				<tr>
					<td class="table_cell"><?php echo __('Type'); ?></td>
					<td class="table_cell"><?php echo __('Description'); ?></td>
					<td class="table_cell"><?php echo __('Value'); ?></td>
					<td class="table_cell"><?php echo __('Preferred'); ?></td>
					<td class="table_cell"><?php echo __(''); ?></td>
				</tr>
			</thead>

			<tbody class="table_body">
				<?php foreach ($this->request->data['UserContact'] as $key => $value) { ?>
				<tr class="table_row">
					<td class="table_cell">
						<?php
						echo $this->Form->input('UserContact.' . $key . '.contact_type_id', array(
							'class' => 'form-control',
							'label' => false,
							'options' => $contactTypeOptions,
							'div' => false,
							'between' => '<div class="input text">'));
							?>
					</td>
					<td class="table_cell">
						<?php
						echo $this->Form->input('UserContact.' . $key . '.ContactType.contact_option_id', array(
							'class' => 'form-control',
							'label' => false,
							'options' => $contactOptionOptions,
							'div' => false,
							'between' => '<div class="input text">'));
							?>
					</td>
					<td class="table_cell">
						<?php
						echo $this->Form->input('UserContact.' . $key . '.value', array(
							'class' => 'form-control deduction_amount',
							'label' => false,
							'div' => false,
							'between' => '<div class="input text">',
							// 'computeType' => 'total_salary_additions',
							// 'onkeypress' => 'return utility.integerCheck(event)',
							// 'onkeyup' => 'jsTable.computeTotal(this)'
							)
						);
						?>
					</td>
					<td class="table_cell">
						<?php
							echo $this->Form->checkbox('UserContact.' . $key . '.preferred', ['class' => 'no-selection-label', 'kd-checkbox-radio' => '']);
						?>
					</td>
					<td>
						<span class="icon_delete" title="<?php echo $this->Label->get('general.delete'); ?>" onClick="$(this).closest('tr').remove()"></span>
					</td>

				</tr>
				<?php } ?>
			</tbody>
		</table>
	</div>
</div>
<?php
	echo $this->Html->link($this->Label->get('general.add'), array(), array('onclick' => "$('#reload').val('Add').click();", 'class' => 'void icon_plus'));

 ?>


