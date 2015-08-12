<?php if ($action == 'add' || $action == 'edit') : ?>

<div class="input clearfix">
	<div class="clearfix">
	<?php
		echo $this->Form->input('Add Payment', [
			'label' => __('Payments'),
			'type' => 'button',
			'class' => 'btn btn-dropdown action-toggle btn-single-action',
			'aria-expanded' => 'true',
			'onclick' => "$('#reload').val('add').click();"
		]);
	?>
	</div>

	<div id="payments-table">
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<?php foreach ($attr['fields'] as $key=>$field) : ?>

						<?php if ($field['type']!='hidden') : ?>
							<th><?= $field['tableHeader'] ?></th>
						<?php endif; ?>
		
					<?php endforeach ?>

					<th class="cell-delete"></th>

				</tr>
			</thead>

			<?php if (isset($attr['data']) || isset($attr['paymentField'])) : ?>

			<tbody>

				<?php if (isset($attr['paymentField']) && !empty($attr['paymentField'])) : ?>

					<tr>
						<?php foreach ($attr['fields'] as $key=>$field) : ?>

							<?php $field['attr']['name'] = $field['model'].'[new]['.$field['field'].']';?>
							<?php $field['fieldName'] = $field['model'].'[new]['.$field['field'].']';?>
							<?php $field['attr']['id'] = strtolower($field['model']).'-new-'.$field['field'];?>
							<?php $field['attr']['value'] = $attr['paymentField']->$field['field'];?>

							<?php if ($field['type']=='hidden') : ?>
								<?= $this->HtmlField->{$field['type']}($action, $attr['paymentField'], $field, $field['attr']);?>
							<?php else: ?>
								<td><?= $this->HtmlField->{$field['type']}($action, $attr['paymentField'], $field, $field['attr']);?></td>
							<?php endif; ?>
			
						<?php endforeach ?>
						
						<td> 
							<button class="btn btn-dropdown action-toggle btn-single-action" type="button" aria-expanded="true" onclick="jsTable.doRemove(this);">
								<?= __('<i class="fa fa-close"></i> Remove') ?>
							</button>
						</td>

					</tr>

				<?php endif; ?>
		
				<?php foreach ($attr['data'] as $i=>$record) : ?>

					<tr>
						<?php foreach ($attr['fields'] as $key=>$field) : ?>

							<?php $field['attr']['name'] = $field['model'].'['.$i.']['.$field['field'].']';?>
							<?php $field['fieldName'] = $field['model'].'['.$i.']['.$field['field'].']';?>
							<?php $field['attr']['id'] = strtolower($field['model']).'-'.$i.'-'.$field['field'];?>
							<?php $field['attr']['value'] = $record->$field['field'];?>

							<?php
							$tdClass = ''; 
							if ($record->errors($field['field'])) {
								$field['attr']['class'] = 'form-error';
								$tdClass = 'error';
							}
							?>

							<?php if ($field['type']=='hidden') : ?>
								<?= $this->HtmlField->{$field['type']}($action, $record, $field, $field['attr']);?>
							<?php else: ?>
							
								<td class="<?= $tdClass ?>"><?= $this->HtmlField->{$field['type']}($action, $record, $field, $field['attr']);?>
									<?php if ($record->errors($field['field'])) : ?>
										<ul class="error-message">
										<?php foreach ($record->errors($field['field']) as $error) : ?>
											<li><?= $error ?></li>
										<?php endforeach ?>
										</ul>
									<?php endif; ?>
								</td>
								
							<?php endif; ?>
			
						<?php endforeach ?>
						
						<td> 
							<button class="btn btn-dropdown action-toggle btn-single-action" type="button" aria-expanded="true" onclick="jsTable.doRemove(this);">
								<?= __('<i class="fa fa-close"></i> Remove') ?>
							</button>
						</td>
					</tr>

				<?php endforeach ?>

			</tbody>
			
			<?php else : ?>

				<tr>&nbsp;</tr>
			
			<?php endif; ?>

		</table>
	</div>
</div>

<?php else : ?>

<div>
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th><?= $attr['fields']['payment_date']['tableHeader'] ?></th>
				<th><?= $attr['fields']['created_user_id']['tableHeader'] ?></th>
				<th><?= $attr['fields']['comments']['tableHeader'] ?></th>
				<th><?= $attr['fields']['amount']['tableHeader'] ?></th>
			</tr>
		</thead>
		<tbody>
		<?php 
		if (isset($attr['data']) && !empty($attr['data'])) :
			foreach ($attr['data'] as $key=>$record) : ?>
			<tr>
				<?php $attr['fields']['created_user_id']['value'] = $record->created_by->name; ?>

				<td><?= $this->HtmlField->{$attr['fields']['payment_date']['type']}($action, $record, $attr['fields']['payment_date'], $attr['fields']['payment_date']['attr']);?></td>
				<td><?= $this->HtmlField->{$attr['fields']['created_user_id']['type']}($action, $record, $attr['fields']['created_user_id'], $attr['fields']['created_user_id']['attr']);?></td>
				<td><?= $this->HtmlField->{$attr['fields']['comments']['type']}($action, $record, $attr['fields']['comments'], $attr['fields']['comments']['attr']);?></td>
				<td><?= $this->HtmlField->{$attr['fields']['amount']['type']}($action, $record, $attr['fields']['amount'], $attr['fields']['amount']['attr']);?></td>
			</tr>
		<?php
			endforeach;
		endif;
		?>
		</tbody>
		<?php if (isset($attr['data']) && !empty($attr['data'])) : ?>
		
		<tfoot>
			<td></td>
			<td></td>
			<td class="cell-number bold"><?php echo $this->Label->get('general.total') ?></td>
			<td class="cell-number bold"><?php echo $attr['total'] ?></td>
		</tfoot>
		
		<?php endif;?>
	</table>
</div>

<?php endif ?>
