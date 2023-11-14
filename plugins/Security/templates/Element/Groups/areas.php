<?php
$tableHeaders = isset($attr['tableHeaders']) ? $attr['tableHeaders'] : [];
$tableCells = isset($attr['tableCells']) ? $attr['tableCells'] : [];
?>

<?php if ($ControllerAction['action'] == 'edit' || $ControllerAction['action'] == 'add') : ?>

<div class="clearfix"></div>
<hr>
<h3><?= __('Areas (Education)') ?></h3>
<?php
	$url = $this->Url->build([
		'plugin' => $this->request->params['plugin'],
	    'controller' => $this->request->params['controller'],
	    'action' => $this->request->params['action'],
	    'ajaxAreaAutocomplete'
	]);
	$table = $ControllerAction['table']->alias();

	echo $this->Form->input('area_search', [
		'label' => __('Add Area'),
		'type' => 'text',
		'class' => 'autocomplete',
		'autocomplete-url' => $url,
		'autocomplete-no-results' => __('No Area found.'),
		'autocomplete-class' => 'error-message',
		'autocomplete-target' => 'area_id',
		'length' => 2,
		'autocomplete-submit' => "$('#reload').val('addArea').click();"
	]);
	echo $this->Form->hidden('area_id', ['autocomplete-value' => 'area_id']);
?>
<div class="clearfix"></div>
<hr>

<?php endif ?>

<div class="table-wrapper">
	<div class="table-responsive" autocomplete-ref="area_id">
		<table class="table table-curved table-input">
			<thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
			<tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
		</table>
	</div>
</div>
