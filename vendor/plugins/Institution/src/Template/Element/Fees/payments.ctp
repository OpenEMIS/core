<?php
	$this->Form->unlockField("StudentFeesAbstract");
?>

<?php if ($action == 'add' || $action == 'edit') : ?>
<?php $action = 'edit'; ?>
<script type="text/javascript">
	$(function() {
		fees.load('total_payments');
		jsTable.computeTotalForMoney('total_payments');
		jsForm.compute(this);
	});
</script>
<div class="input clearfix">
	<div class="clearfix">
	<?php
		echo $this->Form->input('<i class="fa fa-plus"></i> <span>'.__('Add New Payment').'</span>', [
			'label' => __('Payments'),
			'type' => 'button',
			'class' => 'btn btn-default',
			'aria-expanded' => 'true',
			'onclick' => "$('#reload').val('reload').click();"
		]);
	?>
	</div>
	<div class="table-wrapper full-width">
		<div class="table-responsive">
		    <table class="table table-curved table-checkable table-input">
				<thead>
					<tr>
						<?php foreach ($attr['fields'] as $key=>$field) : ?>

							<?php if (isset($field['field'])) : ?>
								<?php if ($field['type']!='hidden') : ?>
									<th><?= $field['tableHeader'] ?></th>
									<th></th>
								<?php endif; ?>
							<?php endif; ?>

						<?php endforeach ?>

						<th class="cell-delete"></th>
					</tr>
				</thead>

				<tbody id='table_total_payments'>
				<?php if (isset($attr['data']) || isset($attr['paymentFields'])) : ?>

					<?php $recordKey = 0; ?>
					<?php foreach ($attr['paymentFields'] as $i=>$record) : ?>
						<?php $recordKey = $i; ?>
						<tr>
							<?php foreach ($attr['fields'] as $key=>$field) : ?>

								<?php if (isset($field['field'])) : ?>
									<?php
										$field['attr']['name'] = $field['model'].'['.$recordKey.']['.$field['field'].']';
										$field['attr']['id'] = strtolower($field['model']).'-'.$recordKey.'-'.$field['field'];
										$field['attr']['value'] = $record->{$field['field']};
										$field['fieldName'] = $field['model'].'['.$recordKey.']['.$field['field'].']';
										$tdClass = '';
										if ($record->errors($field['field'])) {
											$field['attr']['class'] = 'form-error';
											$tdClass = 'error';
										}
									?>

									<?php if ($field['type']=='hidden') : ?>
										<?= $this->HtmlField->{$field['type']}($action, $record, $field, $field['attr']);?>
									<?php else: ?>

										<td class="<?= $tdClass ?>">
											<?= $this->HtmlField->{$field['type']}($action, $record, $field, $field['attr']);?>
										</td>

										<td class="<?= $tdClass ?>">
											<?php if ($record->errors($field['field'])) : ?>
												<ul class="error-message">
												<?php foreach ($record->errors($field['field']) as $error) : ?>
													<li><?= $error ?></li>
												<?php endforeach ?>
												</ul>
											<?php else: ?>
												&nbsp;
											<?php endif; ?>
										</td>

									<?php endif; ?>

								<?php endif; ?>

							<?php endforeach ?>

							<td>
								<?php
								echo $this->Form->input('<i class="fa fa-trash"></i> <span>Delete</span>', [
									'label' => false,
									'type' => 'button',
									'class' => 'btn btn-dropdown action-toggle btn-single-action',
									'title' => "Delete",
									'aria-expanded' => 'true',
									'onclick' => "jsTable.doRemove(this); jsTable.computeTotalForMoney('total_payments'); jsForm.compute(this); "
								]);
								?>
							</td>
						</tr>

					<?php endforeach ?>

				<?php else : ?>

					<tr>
						<?php foreach ($attr['fields'] as $key=>$field) : ?>

							<?php if (isset($field['field'])) : ?>
								<?php if ($field['type']!='hidden') : ?>
									<td></td>
									<td></td>
								<?php endif; ?>
							<?php endif; ?>

						<?php endforeach ?>
						<td></td>
					</tr>

				<?php endif; ?>

				</tbody>

				<?php
					$tdClass = '';
					$ulClass = 'hidden';
					$spanClass = '';
					$errorMessage = '';
					if (array_key_exists($model, $this->request->data) && is_array($this->request->data[$model]['amount_paid'])) {
						$tdClass = 'error';
						$ulClass = '';
						$spanClass = 'error-message';
						$errorMessage = $this->request->data[$model]['amount_paid']['error'];
					}
				?>
				<tfoot>
					<tr>
						<td class="cell-number"><?php echo $this->Label->get('general.total') ?></td>
						<td></td>
						<td class="<?= $tdClass ?>">
							<span class="<?= $spanClass ?>"><?= $attr['currency']?></span>
							<span class="total_payments cell-number <?= $spanClass ?>"><?= $attr['amount_paid']; ?></span>
						</td>
						<td class="<?= $tdClass ?>">
							<ul class="error-message <?= $ulClass ?>">
								<li><?= $errorMessage ?></li>
							</ul>
						</td>
						<td colspan="3"></td>
					</tr>
				</tfoot>
			</table>
		</div>
	</div>
</div>

<?php else : ?>

<div class="table-wrapper">
	<div class="table-in-view">
		<table class="table">
			<thead>
				<tr>
					<th><?= $attr['fields']['payment_date']['tableHeader'] ?></th>
					<th class="text-right"><?= $attr['fields']['amount']['tableHeader'] ?></th>
					<th><?= $attr['fields']['comments']['tableHeader'] ?></th>
					<th><?= $attr['fields']['created_user_id']['tableHeader'] ?></th>
				</tr>
			</thead>
			<tbody>
			<?php
			if (isset($attr['data']) && !empty($attr['data'])) :
				foreach ($attr['data'] as $key=>$record) : ?>
				<tr>
					<?php $attr['fields']['created_user_id']['value'] = $record->created_by->name; ?>

					<td><?= $this->HtmlField->{$attr['fields']['payment_date']['type']}($action, $record, $attr['fields']['payment_date'], $attr['fields']['payment_date']['attr']);?></td>
					<td class="text-right"><?= $this->HtmlField->{$attr['fields']['amount']['type']}($action, $record, $attr['fields']['amount'], $attr['fields']['amount']['attr']);?></td>
					<td><?= $this->HtmlField->{$attr['fields']['comments']['type']}($action, $record, $attr['fields']['comments'], $attr['fields']['comments']['attr']);?></td>
					<td><?= $this->HtmlField->{$attr['fields']['created_user_id']['type']}($action, $record, $attr['fields']['created_user_id'], $attr['fields']['created_user_id']['attr']);?></td>
				</tr>
			<?php
				endforeach;
			endif;
			?>
			</tbody>
			<?php if (isset($attr['data']) && !empty($attr['data'])) : ?>

			<tfoot>
				<td class="bold"><?php echo $this->Label->get('general.total') ?></td>
				<td class="text-right bold"><?php echo $attr['total'] ?></td>
				<td></td>
				<td></td>
			</tfoot>

			<?php endif;?>
		</table>
	</div>
</div>

<?php endif ?>
