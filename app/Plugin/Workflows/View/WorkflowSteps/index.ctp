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
			echo $this->Form->input('wf_workflow_id', array(
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
				<th><?php echo __('Name'); ?></th>
				<th><?php echo __('Actions'); ?></th>
				<th><?php echo __('Workflow'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($data as $obj) : ?>
				<tr>
					<td><?php echo $this->Html->link($obj['WorkflowStep']['name'], array('action' => 'view', $obj['WorkflowStep']['id'])); ?></td>
					<td>
						<?php
							foreach ($obj['WorkflowAction'] as $action) {
								echo $action['name'] . " - " . $action['NextWorkflowStep']['name'] . "<BR>";
							}
						?>
					</td>
					<td><?php echo $obj['Workflow']['name']; ?></td>
				</tr>
			<?php endforeach ?>
		</tbody>
	</table>
</div>

<?php
$this->end();
?>
