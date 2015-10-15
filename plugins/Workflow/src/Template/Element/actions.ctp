<?= $this->Html->script('OpenEmis.../plugins/tableCheckable/jquery.tableCheckable', ['block' => true]) ?>

<?php if ($action == 'view') : ?>
	<div class="table-in-view">
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<th><?= $this->Label->get('general.visible'); ?></th>
					<th><?= $this->Label->get('general.name'); ?></th>
					<th><?= $this->Label->get('WorkflowActions.next_step'); ?></th>
					<th class="center"><?= $this->Label->get('WorkflowActions.comment_required'); ?></th>
					<th><?= $this->Label->get('WorkflowActions.event'); ?></th>
				</tr>
			</thead>
			<?php if (!empty($data->workflow_actions)) : ?>
				<tbody>
					<?php foreach ($data->workflow_actions as $key => $obj) : ?>
					<tr>
						<td>
							<?php if ($obj->visible == 1) : ?>
								<i class="fa fa-check"></i>
							<?php else : ?>
								<i class="fa fa-close"></i>
							<?php endif ?>
						</td>
						<td><?= $obj->name; ?></td>
						<td>
							<?php
								if (isset($obj->next_workflow_step)) {
									echo $obj->next_workflow_step->name;
								}
							?>
						</td>
						<td class="center">
							<?php if ($obj->comment_required == 1) : ?>
								<i class="fa fa-check"></i>
							<?php else : ?>
								<i class="fa fa-close"></i>
							<?php endif ?>
						</td>
						<td>
							<?= !empty($obj->event_key) ? $eventOptions[$obj->event_key] : ''; ?>
						</td>
					</tr>
					<?php endforeach ?>
				</tbody>
			<?php endif ?>
		</table>
	</div>
<?php elseif ($action == 'add' || $action == 'edit') : ?>
	<div class="input">
		<label class="pull-left" for="<?= $attr['id'] ?>"><?= isset($attr['label']) ? $attr['label'] : $attr['field'] ?></label>
		<div class="table-toolbar">
			<button onclick="$('#reload').val('addAction').click();return false;" class="btn btn-default btn-xs">
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
						<th><?= $this->Label->get('general.name'); ?></th>
						<th><?= $this->Label->get('WorkflowActions.next_step'); ?></th>
						<th class="center"><?= $this->Label->get('WorkflowActions.comment_required'); ?></th>
						<th><?= $this->Label->get('WorkflowActions.event'); ?></th>
						<th></th>
					</tr>
					<?php if (!empty($data->workflow_actions)) : ?>
						<tbody>
							<?php foreach ($data->workflow_actions as $key => $obj) : ?>
								<tr>
									<?php if ($action == 'edit') : ?>
										<td class="checkbox-column">
											<?= $this->Form->checkbox("WorkflowSteps.workflow_actions.$key.visible", ['class' => 'icheck-input', 'checked' => $obj->visible]); ?>
										</td>
									<?php endif ?>
										<td>
											<?php
												if(isset($obj->id)) {	// edit
													echo $this->Form->hidden("WorkflowSteps.workflow_actions.$key.id");
												} else {	// add
													echo $this->Form->hidden("WorkflowSteps.workflow_actions.$key.visible", ['value' => 1]);
												}
												echo $this->Form->input("WorkflowSteps.workflow_actions.$key.name", ['label' => false, 'value' => $obj->name]);
											?>
										</td>
										<td>
											<?= $this->Form->input("WorkflowSteps.workflow_actions.$key.next_workflow_step_id", ['label' => false, 'options' => $nextStepOptions]); ?>
										</td>
										<td class="center">
											<?= $this->Form->checkbox("WorkflowSteps.workflow_actions.$key.comment_required", ['class' => 'icheck-input', 'checked' => $obj->comment_required]); ?>
										</td>
										<td>
											<?= $this->Form->input("WorkflowSteps.workflow_actions.$key.event_key", ['label' => false, 'options' => $eventOptions]); ?>											
										</td>
										<td>
											<?php if (is_null($obj->action)) : ?>
												<button class="btn btn-dropdown action-toggle btn-single-action" style="cursor: pointer;" title="<?= $this->Label->get('general.delete.label'); ?>" onclick="jsTable.doRemove(this);">
													<i class="fa fa-trash"></i>&nbsp;<span><?= __('Delete')?></span>
												</button>
											<?php else : ?>
												<?php if ($obj->action == 0) : ?>
													<?= __('Approve'); ?>
												<?php elseif ($obj->action == 1) : ?>
													<?= __('Reject'); ?>
												<?php endif ?>
											<?php endif ?>
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
