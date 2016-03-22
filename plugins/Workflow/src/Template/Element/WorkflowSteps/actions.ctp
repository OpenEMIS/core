<?= $this->Html->script('OpenEmis.../plugins/tableCheckable/jquery.tableCheckable', ['block' => true]) ?>

<style>
.vertical-align-top {
	vertical-align: top !important;
}
</style>

<?php if ($action == 'view') : ?>
	<div class="table-wrapper">
		<div class="table-in-view">
			<table class="table">
				<thead>
					<tr>
						<th><?= $this->Label->get('general.visible'); ?></th>
						<th><?= $this->Label->get('general.name'); ?></th>
						<th><?= $this->Label->get('general.description'); ?></th>
						<th><?= $this->Label->get('WorkflowActions.next_step'); ?></th>
						<th class="center"><?= $this->Label->get('WorkflowActions.comment_required'); ?></th>
						<!-- Hide Event until it is in use -->
						<!-- <th><?= $this->Label->get('WorkflowActions.event'); ?></th> -->
					</tr>
				</thead>
				<?php if (!empty($data->workflow_actions)) : ?>
					<tbody>
						<?php foreach ($data->workflow_actions as $key => $obj) : ?>
						<tr>
							<td class="vertical-align-top">
								<?php if ($obj->visible == 1) : ?>
									<i class="fa fa-check"></i>
								<?php else : ?>
									<i class="fa fa-close"></i>
								<?php endif ?>
							</td>
							<td class="vertical-align-top"><?= $obj->name; ?></td>
							<td class="vertical-align-top"><?= nl2br($obj->description); ?></td>
							<td class="vertical-align-top">
								<?php
									if (isset($obj->next_workflow_step)) {
										echo $obj->next_workflow_step->name;
									}
								?>
							</td>
							<td class="center vertical-align-top">
								<?php if ($obj->comment_required == 1) : ?>
									<i class="fa fa-check"></i>
								<?php else : ?>
									<i class="fa fa-close"></i>
								<?php endif ?>
							</td>
							<!-- Hide Event until it is in use -->
							<!-- <td class="vertical-align-top">
								<?= !empty($obj->event_key) ? $eventOptions[$obj->event_key] : ''; ?>
							</td> -->
						</tr>
						<?php endforeach ?>
					</tbody>
				<?php endif ?>
			</table>
		</div>
	</div>
<?php elseif ($action == 'add' || $action == 'edit') : ?>
	<div class="clearfix"></div>
	<hr>
	<h3><?= isset($attr['label']) ? $attr['label'] : $attr['field']; ?></h3>
	<div class="clearfix">
		<div class="input select">
			<label><?= __('Add Action');?></label>
			<div class="input-form-wrapper">
				<button onclick="$('#reload').val('addAction').click();return false;" class="btn btn-default btn-xs">
					<i class="fa fa-plus"></i>
					<span><?= __('Add');?></span>
				</button>
			</div>
		</div>
		<div class="table-responsive">
			<table class="table table-curved table-checkable">
				<thead>
					<tr>
						<?php if ($action == 'edit') : ?>
							<th class="checkbox-column"><input type="checkbox" class="icheck-input" /></th>
						<?php endif ?>
						<th><?= $this->Label->get('general.name'); ?></th>
						<th><?= $this->Label->get('general.description'); ?></th>
						<th><?= $this->Label->get('WorkflowActions.next_step'); ?></th>
						<th class="center"><?= $this->Label->get('WorkflowActions.comment_required'); ?></th>
						<!-- Hide Event until it is in use -->
						<!-- <th><?= $this->Label->get('WorkflowActions.event'); ?></th> -->
						<th></th>
					</tr>
				</thead>
				<?php if (!empty($data->workflow_actions)) : ?>
					<tbody>
						<?php foreach ($data->workflow_actions as $key => $obj) : ?>
							<tr>
								<?php if ($action == 'edit') : ?>
									<td class="checkbox-column vertical-align-top">
										<?= $this->Form->checkbox("WorkflowSteps.workflow_actions.$key.visible", ['class' => 'icheck-input', 'checked' => $obj->visible]); ?>
									</td>
								<?php endif ?>
									<td class="vertical-align-top">
										<?php
											if(isset($obj->id)) {	// edit
												echo $this->Form->hidden("WorkflowSteps.workflow_actions.$key.id");
											} else {	// add
												echo $this->Form->hidden("WorkflowSteps.workflow_actions.$key.visible", ['value' => 1]);
											}
											echo $this->Form->input("WorkflowSteps.workflow_actions.$key.name", ['label' => false, 'value' => $obj->name]);
										?>
									</td>
									<td class="vertical-align-top">
										<?= $this->Form->input("WorkflowSteps.workflow_actions.$key.description", ['type' => 'textarea', 'style' => 'min-width: 100%;width: 200px !important;', 'label' => false, 'value' => $obj->description]); ?>
									</td>
									<td class="vertical-align-top">
										<?= $this->Form->input("WorkflowSteps.workflow_actions.$key.next_workflow_step_id", ['label' => false, 'options' => $nextStepOptions]); ?>
									</td>
									<td class="center vertical-align-top">
										<?= $this->Form->checkbox("WorkflowSteps.workflow_actions.$key.comment_required", ['class' => 'icheck-input', 'checked' => $obj->comment_required]); ?>
									</td>
									<!-- Hide Event until it is in use -->
									<!-- <td class="vertical-align-top">
										<?= $this->Form->input("WorkflowSteps.workflow_actions.$key.event_key", ['label' => false, 'options' => $eventOptions]); ?>
									</td> -->
									<td class="vertical-align-top">
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
			</table>
		</div>
	</div>
<?php endif ?>
