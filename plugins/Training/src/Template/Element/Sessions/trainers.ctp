<?= $this->Html->script('OpenEmis.../plugins/tableCheckable/jquery.tableCheckable', ['block' => true]) ?>
<?php
	$model = $ControllerAction['table'];
	$trainerOptions = isset($attr['options']) ? $attr['options'] : [];
?>
<?php if ($action == 'view') : ?>
	<div class="table-in-view">
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<th><?= $this->Label->get('general.visible'); ?></th>
					<th><?= $this->Label->get($model->aliasField('trainer_type')); ?></th>
					<th><?= $this->Label->get($model->aliasField('internal_trainer')); ?></th>
					<th><?= $this->Label->get($model->aliasField('external_trainer')); ?></th>
				</tr>
			</thead>
			<?php if (!empty($data->trainers)) : ?>
				<tbody>
					<?php foreach ($data->trainers as $key => $obj) : ?>
					<tr>
						<td>
							<?php if ($obj->visible == 1) : ?>
								<i class="fa fa-check"></i>
							<?php else : ?>
								<i class="fa fa-close"></i>
							<?php endif ?>
						</td>
						<td><?= $trainerTypeOptions[$obj->type]; ?></td>
						<td><?= isset($obj->user->name_with_id) ? $obj->user->name_with_id : ''; ?></td>
						<td><?= $obj->name; ?></td>
					</tr>
					<?php endforeach ?>
				</tbody>
			<?php endif ?>
		</table>
	</div>
<?php elseif ($action == 'add' || $action == 'edit') : ?>
	<div class="input">
		<label for="<?= $attr['id'] ?>"><?= isset($attr['label']) ? $attr['label'] : $attr['field'] ?></label>
		<div class="table-toolbar">
			<button onclick="$('#reload').val('addTrainer').click();return false;" class="btn btn-default btn-xs">
				<i class="fa fa-plus"></i>
				<span><?= __('Add');?></span>
			</button>
		</div>
		<div class="table-in-view">
			<table class="table table-striped table-hover table-bordered table-checkable table-input">
				<thead>
					<tr>
						<?php if ($action == 'edit') : ?>
							<th class="checkbox-column"><input type="checkbox" class="icheck-input" /></th>
						<?php endif ?>
						<th><?= $this->Label->get($model->aliasField('trainer_type')); ?></th>
						<th><?= $this->Label->get($model->aliasField('internal_trainer')); ?></th>
						<th><?= $this->Label->get($model->aliasField('external_trainer')); ?></th>
						<th></th>
					</tr>
					<?php if (!empty($data->trainers)) : ?>
						<tbody>
							<?php foreach ($data->trainers as $key => $obj) : ?>
								<?php
									$prefix = $model->alias().'.trainers.'.$key;
								?>
								<tr>
									<?php if ($action == 'edit') : ?>
										<td class="checkbox-column">
											<?= $this->Form->checkbox("$prefix.visible", ['class' => 'icheck-input', 'checked' => $obj['visible']]); ?>
										</td>
									<?php endif ?>
										<td>
											<?php
												if(isset($obj['id'])) {	// edit
													echo $this->Form->hidden("$prefix.id");
												} else {	// add
													echo $this->Form->hidden("$prefix.visible", ['value' => 1]);
												}
												echo $this->Form->input("$prefix.type", ['label' => false, 'options' => $trainerTypeOptions]);
											?>
										</td>
										<td><?= $this->Form->input("$prefix.trainer_id", ['label' => false, 'options' => $trainerOptions]); ?></td>
										<td><?= $this->Form->input("$prefix.name", ['label' => false]); ?></td>
										<td>
											<button class="btn btn-dropdown action-toggle btn-single-action" style="cursor: pointer;" title="<?= $this->Label->get('general.delete.label'); ?>" onclick="jsTable.doRemove(this);">
												<i class="fa fa-trash"></i>&nbsp;<span><?= __('Delete')?></span>
											</button>
										</td>
								</tr>
							<?php endforeach ?>
						</tbody>
					<?php endif ?>
				</thead>
			</table>
		</div>
	</div>
<?php endif ?>
