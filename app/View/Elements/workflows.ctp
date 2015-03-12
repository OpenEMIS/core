<?php
//$formAction = array('action' => 'WorkflowAction');
$paramValues = array();

if (isset($params)) {
	foreach ($params as $key => $value) {
		if (is_int($key)) {
			$paramValues[] = $value;
		}
	}
}

$formAction = $_triggerFrom == 'Controller' ? array('action' => $this->action) : array('action' => $model, $this->action);
$formAction[] = $this->request->data['WfWorkflowLog']['model_reference'];
if (isset($params)) {
	$formAction = array_merge($formAction, $paramValues);
}

$formOptions = $this->FormUtility->getFormOptions($formAction);
echo $this->Form->create($model, $formOptions);
//pr($this->request->data);
echo $this->Form->hidden('WfWorkflowLog.model');
echo $this->Form->hidden('WfWorkflowLog.model_reference');
echo $this->Form->hidden('WfWorkflowLog.prev_workflow_step_id');
?>
<hr />
<div class="row">
	<h5><?php echo $workflowStepName; ?></h5>
</div>

<div class="row">
	<div class="btn-group">
		<?php foreach ($buttons as $key => $button) : ?>
			<!--?php echo $this->Html->link($button['text'], $button['url'], array('class' => 'btn btn-default btn-sm')); ?-->
			<!--button type="button" class="btn btn-default" role="button"><?php //echo $this->Html->link($button['text'], $button['url']); ?></button-->
			<button type="submit" class="btn btn-default btn-sm" role="button" name="WfWorkflowLog[workflow_step_id]" value="<?php echo $button['value'];?>"><?php echo $button['text']; ?></button>
		<?php endforeach ?>
		<!--button type="submit" class="btn btn-default btn-sm" role="button" name="WfWorkflowLog[workflow_step_id]" value="1">Approve</button>
		<button type="submit" class="btn btn-default btn-sm" role="button" name="WfWorkflowLog[workflow_step_id]" value="2">Reject</button-->
	</div>
</div>

<div class="row">
	<?php
		//pr($tabs);
	?>
	<ul class="nav nav-tabs">
		<li class="active"><a href="comments">Comments</a></li>
		<li class=""><a href="transitions">Transitions</a></li>
	</ul>
</div>

<?php
echo $this->Form->button('reload', array('id' => 'reload', 'type' => 'submit', 'name' => 'submit', 'value' => 'reload', 'class' => 'hidden'));
echo $this->Form->end();
?>
