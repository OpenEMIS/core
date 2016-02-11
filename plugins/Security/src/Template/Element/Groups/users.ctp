<?php
$tableHeaders = isset($attr['tableHeaders']) ? $attr['tableHeaders'] : [];
$tableCells = isset($attr['tableCells']) ? $attr['tableCells'] : [];
?>

<?php if ($ControllerAction['action'] == 'edit' || $ControllerAction['action'] == 'add') : ?>

<div class="clearfix"></div>
<hr>
<h3><?= __('Users') ?></h3>
<?php
	$url = $this->Url->build([
		'plugin' => $this->request->params['plugin'],
	    'controller' => $this->request->params['controller'],
	    'action' => $this->request->params['action'],
	    'ajaxUserAutocomplete'
	]);
	$table = $ControllerAction['table']->alias();

	echo $this->Form->input('user_search', [
		'label' => __('Add User'),
		'type' => 'text',
		'class' => 'autocomplete',
		'autocomplete-url' => $url,
		'autocomplete-no-results' => __('No User found.'),
		'autocomplete-class' => 'error-message',
		'autocomplete-target' => 'user_id',
		'autocomplete-submit' => "$('#reload').val('addUser').click();"
	]);
	echo $this->Form->hidden('user_id', ['autocomplete-value' => 'user_id']);
?>
<div class="clearfix"></div>
<hr>

<?php endif ?>

<div class="table-wrapper">
	<div class="table-responsive" autocomplete-ref="user_id">
	<table class="table table-curved table-input">
		<thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
		<tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
	</table>
	</div>
</div>
