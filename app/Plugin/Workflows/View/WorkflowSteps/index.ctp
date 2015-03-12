<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', (!empty($contentHeader) ? $contentHeader : $this->Label->get("$model.title")));

$this->start('contentActions');
if ($_add) {
	$actionParams = $_triggerFrom == 'Controller' ? array('action' => 'add') : array('action' => $model, 'add');
    echo $this->Html->link($this->Label->get('general.add'), $actionParams, array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
?>
<div class="row page-controls">
	<?php
		$baseUrl = $this->params['controller'] . '/' . $this->request->action;

		if(isset($workflowOptions)) {
			echo $this->Form->input('workflow_id', array(
				'class' => 'form-control',
				'label' => false,
				'options' => $workflowOptions,
				'default' => 'workflow:' . $selectedWorkflow,
				'div' => 'col-md-3',
				'url' => $baseUrl,
				'onchange' => 'jsForm.change(this)'
			));
		}
	?>
</div>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th><?php echo $this->Label->get('general.name'); ?></th>
				<th><?php echo $this->Label->get('WorkflowStep.security_roles'); ?></th>
				<th><?php echo $this->Label->get('WorkflowStep.actions'); ?></th>
				<th><?php echo $this->Label->get('WorkflowStep.workflow_id'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($data as $obj) : ?>
				<tr>
					<td><?php echo $this->Html->link($obj['WfWorkflowStep']['name'], array('action' => 'view', $obj['WfWorkflowStep']['id'])); ?></td>
					<td>
						<?php
							$securityRoles = array();
							foreach ($obj['SecurityRole'] as $securityRole) {
								$securityRoles[] = $securityRole['name'];
							}
							echo implode('<br>', $securityRoles);
						?>
					</td>
					<td>
						<?php
							$workflowActions = array();
							foreach ($obj['WorkflowAction'] as $workflowAction) {
								$workflowActionName = isset($workflowAction['name']) ? $workflowAction['name'] : '';
								$nextWorkflowStepName = isset($workflowAction['NextWorkflowStep']['name']) ? $workflowAction['NextWorkflowStep']['name'] : '';
								if ($workflowAction['visible'] == 1) {
									$workflowActions[] = $workflowActionName . " - " . $nextWorkflowStepName;
								}
							}
							echo implode('<br>', $workflowActions);
						?>
					</td>
					<td><?php echo $obj['WfWorkflow']['name']; ?></td>
				</tr>
			<?php endforeach ?>
		</tbody>
	</table>
</div>

<?php
$this->end();
?>
