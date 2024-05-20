<?php
	// POCOR-8256 add type fields
	$tableClass = 'table-in-view';
	$tableHeaders = isset($attr['tableHeaders']) ? $attr['tableHeaders'] : [];
	$tableCells = isset($attr['tableCells']) ? $attr['tableCells'] : [];
	$evaluatorTypeOptions = isset($attr['evaluatorTypeOptions']) ? $attr['evaluatorTypeOptions'] : [];
	$this->Form->unlockField('evaluator_id');
?>

<?php if ($ControllerAction['action'] == 'edit' || $ControllerAction['action'] == 'add') : ?>
	<?php $tableClass = 'table-responsive'; ?>
	<div class="clearfix"></div>
	<hr>
	<h3><?= __('Evaluators') ?></h3>
	<?php
		$url = $this->Url->build([
			'plugin' => $this->request->params['plugin'],
		    'controller' => $this->request->params['controller'],
		    'action' => $this->request->params['action'],
		    'ajaxEvaluatorAutocomplete'
		]);
		$alias = $ControllerAction['table']->alias();

		
		echo $this->Form->input("$alias.types", [
			'label' => __('Type'),
			'type' => 'select',
			'options' => $evaluatorTypeOptions,
 			'onchange' => "$('#reload').val('types').click();"
		]);

		$requestData = $this->request->data[$alias];
		$evaluatorType = (array_key_exists('types', $requestData)) ? $requestData['types']: 'Staff';
		echo $this->Form->input("$alias.evaluator_search", [
			'label' => __('Add Evaluator'),
			'type' => 'text',
			'class' => 'autocomplete',
			'value' => '',
			'autocomplete-url' => $url,
			'autocomplete-no-results' => __('No Evaluators found.'),
			'autocomplete-class' => 'error-message',
			'autocomplete-target' => 'evaluator_id',
			'autocomplete-submit' => "$('#reload').val('addEvaluator').click();",
			'autocomplete-before-search' => 'Autocomplete.extra["types"] = "' . $evaluatorType . '"'
		]);
		echo $this->Form->hidden("$alias.evaluator_id", ['autocomplete-value' => 'evaluator_id']);
	?>
	<div class="clearfix"></div>
	<hr>
<?php endif ?>

<div class="<?= $tableClass; ?>" autocomplete-ref="evaluator_id">
	<table class="table">
		<thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
		<tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
	</table>
</div>

