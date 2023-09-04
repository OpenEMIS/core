<?php
$tableHeaders = isset($attr['tableHeaders']) ? $attr['tableHeaders'] : [];
$tableCells = isset($attr['tableCells']) ? $attr['tableCells'] : [];
?>

<?php if ($ControllerAction['action'] == 'edit' || $ControllerAction['action'] == 'add') : ?>

<div class="clearfix"></div>
<hr>
<h3><?= __('Institutions') ?></h3>
<?php
	$url = $this->Url->build([
		'plugin' => $this->request->params['plugin'],
	    'controller' => $this->request->params['controller'],
	    'action' => $this->request->params['action'],
	    'ajaxInstitutionAutocomplete'
	]);
	$table = $ControllerAction['table']->alias();

	echo $this->Form->input('institution_search', [
		'label' => __('Add Institution'),
		'type' => 'text',
		'class' => 'autocomplete',
		'autocomplete-url' => $url,
		'autocomplete-no-results' => __('No Institution found.'),
		'autocomplete-class' => 'error-message',
		'autocomplete-target' => 'institution_id',
		'autocomplete-submit' => "$('#reload').val('addInstitution').click();"
	]);
	echo $this->Form->hidden('institution_id', ['autocomplete-value' => 'institution_id']);
?>
<div class="clearfix"></div>
<hr>

<?php endif ?>

<div class="table-wrapper">
	<div class="table-responsive" autocomplete-ref="institution_id">
		<table class="table table-curved table-input">
			<thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
			<tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
		</table>
	</div>
</div>