<?php if ($ControllerAction['action'] == 'index') : ?>
	<?= isset($attr['value']) ? $attr['value'] : 0; ?>
<?php elseif ($ControllerAction['action'] == 'view') : ?>
	<?php
		$tableHeaders = isset($attr['tableHeaders']) ? $attr['tableHeaders'] : [];
		$tableCells = isset($attr['tableCells']) ? $attr['tableCells'] : [];
	?>
	<div class="table-wrapper">
		<div class="table-in-view">
			<table class="table">
				<thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
				<tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
			</table>
		</div>
	</div>
<?php elseif ($ControllerAction['action'] == 'edit') : ?>
	<?php
		$tableHeaders = isset($attr['tableHeaders']) ? $attr['tableHeaders'] : [];
		$tableCells = isset($attr['tableCells']) ? $attr['tableCells'] : [];
	?>
	<div class="clearfix"></div>
		<hr>
		<h3><?= $this->Label->get('Assessments.assessmentItems'); ?></h3>
		<div class="clearfix">
			<?= 
				// pr($options);
				$this->Form->input($ControllerAction['table']->alias().".new_education_subject_id", [
					'label' => $this->Label->get('Assessments.addAssessmentItem'),
					'type' => 'select',
					'options' => $attr['options'],
					'value' => 0,
					'onchange' => "$('#reload').val('addSubject').click();"
				]);
			?>
		</div>

	<div class="table-wrapper">
		<div class="table-responsive">
			<table class="table table-curved table-input">
				<thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
				<tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
			</table>
		</div>
	</div>
<?php endif ?>
