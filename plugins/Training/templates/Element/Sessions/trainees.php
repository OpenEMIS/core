<?php
	$tableClass = 'table-in-view';
	$tableHeaders = isset($attr['tableHeaders']) ? $attr['tableHeaders'] : [];
	$tableCells = isset($attr['tableCells']) ? $attr['tableCells'] : [];

?>

<?php if ($ControllerAction['action'] == 'edit' || $ControllerAction['action'] == 'add') : ?>
	<?php $this->Form->unlockField('trainee_id'); ?>
	<?php $tableClass = 'table-responsive'; ?>
	<div class="clearfix"></div>
	<hr>
	<h3><?= __('Trainees') ?></h3>
	<?php
		$url = $this->Url->build([
			'plugin' => $this->request->getParam('plugin'),
		    'controller' => $this->request->getParam('controller'),
		    'action' => $this->request->getParam('action'),
		    'ajaxTraineeAutocomplete',
            $this->ControllerAction->paramsEncode(['id' => $data->id]) // encode the ID
		]);

		$alias = $ControllerAction['table']->getAlias();

		echo $this->Form->input("$alias.trainee_search", [
			'label' => __('Add Trainee'),
			'type' => 'text',
			'class' => 'autocomplete',
			'value' => '',
			'autocomplete-url' => $url,
			'autocomplete-no-results' => __('No Trainee found.'),
			'autocomplete-class' => 'error-message',
			'autocomplete-target' => 'trainee_id',
			'autocomplete-submit' => "$('#reload').val('addTrainee').click();"
		]);
		echo $this->Form->hidden("$alias.trainee_id", ['autocomplete-value' => 'trainee_id']);

        $url = $this->Url->build([
            'plugin' => $this->request->getParam('plugin'),
            'controller' => $this->request->getParam('controller'),
            'action' => 'ImportTrainees',
            'trainingId' => $this->request->getParam('pass')[1],
            0 => 'add'
        ]);

        // echo $this->Form->input('<i class="fa kd-import"></i> <span>'.__('Import Trainees').'</span>', [
        //     'label' => __('Import Trainees'),
        //     'type' => 'button',
        //     'class' => 'btn btn-default',
        //     'aria-expanded' => 'true',
        //     'onclick' => "window.location.href = '$url'"
        // ]); //POCOR-8347
		echo $this->Form->input(__('Import Trainees'), [
            'label' => __('Import Trainees'),
            'type' => 'button',
            'class' => 'btn btn-default',
            'aria-expanded' => 'true',
            'onclick' => "window.location.href = '$url'"
        ]);
	?>
	<div class="clearfix"></div>
	<hr>
<?php endif ?>

<div class="<?= $tableClass; ?>" autocomplete-ref="trainee_id">
	<table class="table">
		<thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
		<tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
	</table>
</div>
