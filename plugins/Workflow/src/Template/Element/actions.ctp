<?= $this->Html->css('OpenEmis.../plugins/icheck/skins/minimal/blue', ['block' => true]) ?>
<?= $this->Html->script('OpenEmis.../plugins/icheck/jquery.icheck.min', ['block' => true]) ?>
<?= $this->Html->script('OpenEmis.../plugins/tableCheckable/jquery.tableCheckable', ['block' => true]) ?>

<?php if ($action == 'view') : ?>
	<div class="table-responsive">
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<th><?= $this->Label->get('general.visible'); ?></th>
					<th><?= $this->Label->get('general.name'); ?></th>
					<th><?= $this->Label->get('WorkflowActions.next_step'); ?></th>
					<th><?= $this->Label->get('WorkflowActions.comment_required'); ?></th>
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
						<td><?= $obj->next_workflow_step->name; ?></td>
						<td>
							<?php if ($obj->comment_required == 1) : ?>
								<i class="fa fa-check"></i>
							<?php else : ?>
								<i class="fa fa-close"></i>
							<?php endif ?>
						</td>
					</tr>
					<?php endforeach ?>
				</tbody>
			<?php endif ?>
		</table>
	</div>
<?php elseif ($action == 'add' || $action == 'edit') : ?>
	<div class="input">
		<label class="pull-left" for="<?= $attr['id'] ?>"><?= $this->ControllerAction->getLabel($attr['model'], $attr['field'], $attr) ?></label>
		<div class="table-in-view col-md-4 table-responsive">
			<table class="table table-striped table-hover table-bordered table-checkable table-input">
				<thead>
					<tr>
						<?php if ($action == 'edit') : ?>
							<th><?= $this->Label->get('general.visible'); ?></th>
						<?php endif ?>
						<th><?= $this->Label->get('general.name'); ?></th>
						<th><?= $this->Label->get('WorkflowActions.next_step'); ?></th>
						<th><?= $this->Label->get('WorkflowActions.comment_required'); ?></th>
						<th></th>
					</tr>
				</thead>
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
											if(isset($obj->id)) {
												echo $this->Form->hidden("WorkflowSteps.workflow_actions.$key.id");
											}
											echo $this->Form->input("WorkflowSteps.workflow_actions.$key.name", ['label' => false]);
										?>
									</td>
									<td>
										<?= $this->Form->input("WorkflowSteps.workflow_actions.$key.next_workflow_step_id", ['label' => false, 'options' => $nextStepOptions]); ?>
									</td>
									<td>
										<?= $this->Form->checkbox("WorkflowSteps.workflow_actions.$key.comment_required", ['class' => 'icheck-input', 'checked' => $obj->comment_required]); ?>
									</td>
									<td>
										<button class="btn btn-dropdown action-toggle btn-single-action" style="cursor: pointer;" title="Remove" onclick="jsTable.doRemove(this);"><i class="fa fa-close"></i> Remove</button>
									</td>
							</tr>
						<?php endforeach ?>
					</tbody>
				<?php endif ?>
			</table>
			<span class="btn btn-default fa fa-plus" style="cursor: pointer;" onclick="$('#reload').val('addAction').click();"></span>
		</div>
	</div>
<?php endif ?>
