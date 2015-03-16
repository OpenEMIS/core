<?php
$paramValues = array();
if (isset($params)) {
	foreach ($params as $key => $value) {
		if (is_int($key)) {
			$paramValues[] = $value;
		}
	}
}

$formAction = $_triggerFrom == 'Controller' ? array('action' => $this->action) : array('action' => $this->action, $action);
$formAction[] = $this->request->data['WorkflowRecord']['model_reference'];

if (isset($params)) {
	$formAction = array_merge($formAction, $paramValues);
}
//pr($this->request->data);
$formOptions = $this->FormUtility->getFormOptions($formAction);
echo $this->Form->create($model, $formOptions);
echo $this->Form->hidden('WorkflowTransition.prev_workflow_step_id');
echo $this->Form->hidden('WorkflowTransition.workflow_record_id');
?>

<hr />

<div class="row">
	<h5><?php echo $workflowStepName; ?></h5>
</div>

<div class="row">
	<div class="btn-group">
		<?php foreach ($buttons as $key => $button) : ?>
			<button type="submit" class="btn btn-default btn-sm" role="button" name="WorkflowTransition[workflow_step_id]" value="<?php echo $button['value']; ?>"><?php echo $button['text']; ?></button>
		<?php endforeach ?>
	</div>
</div>

<div class="row">
	<ul class="nav nav-tabs">
		<?php foreach ($tabs as $key => $tab) : ?>
			<li class="<?php echo $tab['class']; ?>"><a href="#" onclick="$('#reload').val('<?php echo $key; ?>').click();return false;"><?php echo $tab['name']; ?></a></li>
		<?php endforeach ?>
	</ul>
</div>

<?php
echo $this->element('/Workflow/' . $selectedTab);
echo $this->Form->button('reload', array('id' => 'reload', 'type' => 'submit', 'name' => 'submit', 'value' => 'reload', 'class' => 'hidden'));
echo $this->Form->end();
?>
