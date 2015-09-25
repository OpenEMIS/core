<?php
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
	echo $this->Form->input('name', ['label' => __('Convert From'), 'readonly']);
	echo $this->Form->input('transfer_to', ['label' => __('Convert To'), 'options' => $convertOptions, 'required' => 'required']);

	$tableData = [];
	foreach ($associations as $row) {
		$tableData[] = [$row['model'], $row['count']];
	}
?>

<div class="input clearfix">
	<label class="pull-left"><?= __('Apply To') ?></label>
	<div class="table-in-view">
		<table class="table table-striped table-hover table-bordered">
			<thead><?= $this->Html->tableHeaders([__('Feature'), __('No of records')]) ?></thead>
			<tbody><?php echo $this->Html->tableCells($tableData) ?></tbody>
		</table>
	</div>
</div>

<?php
	echo $this->ControllerAction->getFormButtons();
	echo $this->Form->end();
$this->end();
?>
