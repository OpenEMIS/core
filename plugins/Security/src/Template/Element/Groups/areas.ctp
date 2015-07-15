<?php
$tableHeaders = isset($attr['tableHeaders']) ? $attr['tableHeaders'] : [];
$tableCells = isset($attr['tableCells']) ? $attr['tableCells'] : [];
?>

<?php if ($ControllerAction['action'] == 'edit') : ?>

<div class="clearfix"></div>
<hr>
<h3><?= __('Areas') ?></h3>
<?php
	$url = $this->Url->build([
		'plugin' => $this->request->params['plugin'],
	    'controller' => $this->request->params['controller'],
	    'action' => $this->request->params['action'],
	    'ajaxAreaAutocomplete'
	]);

	echo $this->Form->input($ControllerAction['table']->alias().".area_id", [
		'label' => __('Add Area'),
		'type' => 'text',
		'class' => 'autocomplete',
		'autocomplete-no-results' => __('No Areas found.'),
		'autocomplete-class' => 'error-message',
		'autocomplete-url' => $url
	]);
?>
<div class="clearfix"></div>
<hr>

<?php endif ?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered table-input">
		<thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
		<tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
	</table>
</div>
