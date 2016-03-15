<?php
$this->extend('OpenEmis./Layout/Panel');
$this->start('toolbar');
	foreach ($toolbarButtons as $key => $btn) {
		if ($btn['type'] == 'button') {
			echo $this->Html->link($btn['label'], $btn['url'], $btn['attr']);
		} else if ($btn['type'] == 'element') {
			echo $this->element($btn['element'], $btn['data'], $btn['options']);
		}
	}
$this->end();

$this->start('panelBody');
	$template = $this->ControllerAction->getFormTemplate();
	$formOptions = $this->ControllerAction->getFormOptions();
	$formOptions['type'] = 'delete';
	$this->Form->templates($template);
	
	echo $this->Form->create($data, $formOptions);
	echo $this->Form->hidden('id');
	echo $this->Form->input('code_name', ['label' => __('Convert From'), 'readonly']);

	$onChange = "$('#reload').click();return false;";
	echo $this->Form->input('transfer_to', ['label' => __('Convert To'), 'options' => $convertOptions, 'required' => 'required', 'onChange' => $onChange]);
?>

<?php
	$stepHeaders = [__('Step Name (Convert From)'), __('Step Name (Convert To)')];
	$stepCells = [];

	if (isset($steps)) {
		foreach ($steps as $i => $step) {
			$fieldPrefix = $ControllerAction['table']->alias() . '.steps.' . $i;
			$rowData = [];
			$name = $step->name;
			$name .= $this->Form->hidden("$fieldPrefix.workflow_step_id", ['value' => $step->id]);
			$rowData[] = $name;
			$rowData[] = $this->Form->input("$fieldPrefix.convert_workflow_step_id", ['label' => false, 'options' => $convertStepOptions]);
			$stepCells[] = $rowData;
		}
	}
?>

<div class="input">
	<label><?= __('Steps') ?></label>
	<div class="input-form-wrapper">
		<div class="table-wrapper">
			<div class="table-in-view">
				<table class="table">
					<thead><?= $this->Html->tableHeaders($stepHeaders) ?></thead>
					<tbody><?php echo $this->Html->tableCells($stepCells) ?></tbody>
				</table>
			</div>
		</div>
	</div>
</div>

<div class="input">
	<label><?= __('Apply To') ?></label>
	<div class="input-form-wrapper">
		<div class="table-wrapper">
			<div class="table-in-view">
				<table class="table">
					<thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
					<tbody><?php echo $this->Html->tableCells($tableCells) ?></tbody>
				</table>
			</div>
		</div>
	</div>
</div>

<?php
	echo $this->ControllerAction->getFormButtons();
	echo $this->Form->end();
$this->end();
?>
