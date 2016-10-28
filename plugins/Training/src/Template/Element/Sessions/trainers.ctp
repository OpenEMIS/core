<?= $this->Html->script('Training.training', ['block' => true]); ?>
<?php
	$model = $ControllerAction['table'];
	$trainerOptions = isset($attr['options']) ? $attr['options'] : [];
	$this->Form->unlockField('TrainingSessions.trainers');
?>
<?php if ($ControllerAction['action'] == 'view') : ?>
	<div class="table-wrapper">
		<div class="table-in-view">
			<table class="table">
				<thead>
					<tr>
						<th><?= $this->Label->get($model->aliasField('trainer_type')); ?></th>
						<th><?= $this->Label->get($model->aliasField('trainer')); ?></th>
					</tr>
				</thead>
				<?php if (!empty($data->trainers)) : ?>
					<tbody>
						<?php foreach ($data->trainers as $key => $obj) : ?>
						<tr>
							<td><?= $trainerTypeOptions[$obj->type]; ?></td>
							<td><?= isset($obj->user->name_with_id) ? $obj->user->name_with_id : $obj->name; ?></td>
						</tr>
						<?php endforeach ?>
					</tbody>
				<?php endif ?>
			</table>
		</div>
	</div>
<?php elseif ($ControllerAction['action'] == 'add' || $ControllerAction['action'] == 'edit') : ?>
	<div class="input">
		<label for="<?= $attr['id'] ?>"><?= isset($attr['label']) ? $attr['label'] : $attr['field'] ?></label>
		<div class="input-form-wrapper">
			<div class="table-toolbar">
				<button onclick="$('#reload').val('addTrainer').click();return false;" class="btn btn-default btn-xs">
					<i class="fa fa-plus"></i>
					<span><?= __('Add External Trainer');?></span>
				</button>
			</div>
			<div class="table-wrapper">
				<div class="table-in-view">
					<table class="table">
						<thead>
							<tr>
								<th><?= $this->Label->get($model->aliasField('trainer_type')); ?></th>
								<th><?= $this->Label->get($model->aliasField('trainer')); ?></th>
								<th></th>
							</tr>
							<?php if (!empty($data->trainers)) : ?>
								<tbody>
									<?php foreach ($data->trainers as $key => $obj) : ?>
										<?php
											$prefix = $model->alias().'.trainers.'.$key;
										?>
										<tr>
											<td>
												<?php
													// id is not needed as will always delete and re-insert trainers
													// if(isset($obj['id'])) {	// edit
														// echo $this->Form->hidden("$prefix.id");
													// }
													echo $trainerTypeOptions[$obj->type];
													echo $this->Form->hidden("$prefix.type", ['value' => $obj->type]);
												?>
											</td>
											<td>
												<?php
													if (isset($obj->trainer_id)) {
														echo $trainerOptions[$obj->trainer_id];
														echo $this->Form->hidden("$prefix.trainer_id", ['value' => $obj->trainer_id]);
														echo $this->Form->hidden("$prefix.name");
													} else {
														echo $this->Form->hidden("$prefix.trainer_id");
														echo $this->Form->input("$prefix.name", ['label' => false]);
													}
												?>
											</td>
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
		</div>
	</div>
<?php endif ?>
