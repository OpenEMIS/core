<?php
	$tableHeaders = isset($attr['tableHeaders']) ? $attr['tableHeaders'] : [];
	$tableCells = isset($attr['tableCells']) ? $attr['tableCells'] : [];
?>

<?php if ($ControllerAction['action'] == 'edit' || $ControllerAction['action'] == 'add') : ?>
	<div class="clearfix"></div>
	<hr>
	<h3><?= __('Trainees') ?></h3>
	<?php
		$url = $this->Url->build([
			'plugin' => $this->request->params['plugin'],
		    'controller' => $this->request->params['controller'],
		    'action' => $this->request->params['action'],
		    'ajaxTraineeAutocomplete'
		]);
		$table = $ControllerAction['table']->alias();

		echo $this->Form->input('trainee_search', [
			'label' => __('Add Trainee'),
			'type' => 'text',
			'class' => 'autocomplete',
			'autocomplete-url' => $url,
			'autocomplete-no-results' => __('No Trainee found.'),
			'autocomplete-class' => 'error-message',
			'autocomplete-target' => 'trainee_id',
			'autocomplete-submit' => "$('#reload').val('addTrainee').click();"
		]);
		echo $this->Form->hidden('trainee_id', ['autocomplete-value' => 'trainee_id']);
	?>
	<div class="clearfix"></div>
	<hr>
<?php endif ?>

<div class="table-in-view" autocomplete-ref="trainee_id">
	<table class="table table-striped table-hover table-bordered table-input">
		<thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
		<tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
	</table>
</div>
