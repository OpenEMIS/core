<?php
	echo $this->Html->script('Workflow.workflow', ['block' => true]);
?>
<?php if ($ControllerAction['action'] == 'view') : ?>
	<?php
		$tableHeaders = isset($attr['tableHeaders']) ? $attr['tableHeaders'] : [];
	?>
	<?= $this->Html->tableHeaders($tableHeaders) ?>
<?php endif ?>
