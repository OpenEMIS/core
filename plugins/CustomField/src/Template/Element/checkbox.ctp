<?= $this->Html->css('OpenEmis.../plugins/icheck/skins/minimal/blue', ['block' => true]) ?>
<?= $this->Html->script('OpenEmis.../plugins/icheck/jquery.icheck.min', ['block' => true]) ?>
<?= $this->Html->script('OpenEmis.../plugins/tableCheckable/jquery.tableCheckable', ['block' => true]) ?>

<?php $CustomFields = $attr['model']; ?>
<?php if ($action == 'view') : ?>
	<div class="table-responsive">
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<th><?= $this->Label->get('general.visible'); ?></th>
					<th><?= $this->Label->get('general.name'); ?></th>
				</tr>
			</thead>
			<?php if (!empty($data->custom_field_options)) : ?>
				<tbody>
					<?php foreach ($data->custom_field_options as $key => $obj) : ?>
					<tr>
						<td>
							<?php if ($obj->visible == 1) : ?>
								<i class="fa fa-check"></i>
							<?php else : ?>
								<i class="fa fa-close"></i>
							<?php endif ?>
						</td>
						<td><?= $obj->name; ?></td>
					</tr>
					<?php endforeach ?>
				</tbody>
			<?php endif ?>
		</table>
	</div>
<?php else : ?>
	<div class="input">
		<label class="pull-left" for="<?= $attr['id'] ?>"><?= $this->ControllerAction->getLabel($attr['model'], $attr['field'], $attr) ?></label>
		<div class="col-md-6">
			<table class="table table-striped table-hover table-bordered table-checkable table-input">
				<thead>
					<tr>
						<?php if ($action == 'edit') : ?>
							<th><?= $this->Label->get('general.visible'); ?></th>
						<?php endif ?>
						<th><?= $this->Label->get('general.name'); ?></th>
						<th><?= $this->Label->get('general.delete'); ?></th>
					</tr>
				</thead>
				<?php if (!empty($data->custom_field_options)) : ?>
					<tbody>
						<?php foreach ($data->custom_field_options as $key => $obj) : ?>
							<tr>
								<?php if ($action == 'edit') : ?>
									<td class="checkbox-column">
										<?= $this->Form->checkbox("$CustomFields.custom_field_options.$key.visible", ['class' => 'icheck-input', 'checked' => $obj->visible]); ?>
									</td>
								<?php endif ?>
								<td>
									<?php
										if(isset($obj->id)) {
											echo $this->Form->hidden("$CustomFields.custom_field_options.$key.id");
										}
										echo $this->Form->input("$CustomFields.custom_field_options.$key.name", ['label' => false]);
										echo $this->Form->hidden("$CustomFields.custom_field_options.$key.is_default", ['value' => 0]);
									?>
								</td>
								<td>
									<span class="fa fa-minus-circle" style="cursor: pointer;" title="<?php echo $this->Label->get('general.delete'); ?>" onclick="jsTable.doRemove(this);"></span>
								</td>
							</tr>
						<?php endforeach ?>
					</tbody>
				<?php endif ?>
			</table>
			<span class="fa fa-plus" style="cursor: pointer;" onclick="$('#reload').val('addOption').click();"></span>
		</div>
	</div>
<?php endif ?>
