<?php
	echo $this->Html->script('Workflow.workflow', ['block' => true]);
	echo $this->Html->script('ControllerAction.../plugins/chosen/js/chosen.jquery.min', ['block' => true]);
	echo $this->Html->css('ControllerAction.../plugins/chosen/css/chosen.min', ['block' => true]);
?>
<?php if ($ControllerAction['action'] == 'view') : ?>
	<?php
		$tableHeaders = isset($attr['tableHeaders']) ? $attr['tableHeaders'] : [];
		$tableCells = isset($attr['tableCells']) ? $attr['tableCells'] : [];
	?>
	<div class="table-wrapper">
		<div class="table-responsive">
			<table class="table table-curved">
				<thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
				<tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
			</table>
		</div>
	</div>
<?php endif ?>
