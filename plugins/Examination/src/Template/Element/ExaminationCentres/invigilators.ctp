<?php
	$tableClass = 'table-in-view';
	$tableHeaders = isset($attr['tableHeaders']) ? $attr['tableHeaders'] : [];
	$tableCells = isset($attr['tableCells']) ? $attr['tableCells'] : [];
	$this->Form->unlockField('invigilator_id');
?>

<?php if ($ControllerAction['action'] == 'view' || $ControllerAction['action'] == 'edit') : ?>
	<?php if ($ControllerAction['action'] == 'edit') : ?>
		<?php $tableClass = 'table-responsive'; ?>
		<div class="clearfix"></div>
		<hr>
		<h3><?= __('Invigilators') ?></h3>
		<?php
			$url = $this->Url->build([
				'plugin' => $this->request->params['plugin'],
			    'controller' => $this->request->params['controller'],
			    'action' => $this->request->params['action'],
			    'ajaxInvigilatorAutocomplete'
			]);
			$alias = $ControllerAction['table']->alias();

			echo $this->Form->input("$alias.invigilator_search", [
				'label' => __('Add Invigilator'),
				'type' => 'text',
				'class' => 'autocomplete',
				'value' => '',
				'autocomplete-url' => $url,
				'autocomplete-no-results' => __('No Invigilator found.'),
				'autocomplete-class' => 'error-message',
				'autocomplete-target' => 'invigilator_id',
				'autocomplete-submit' => "$('#reload').val('addInvigilator').click();"
			]);
			echo $this->Form->hidden("$alias.invigilator_id", ['autocomplete-value' => 'invigilator_id']);
		?>
	<?php endif ?>

	<div class="<?= $tableClass; ?>" autocomplete-ref="invigilator_id">
		<table class="table">
			<thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
			<tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
		</table>
	</div>
<?php endif ?>
