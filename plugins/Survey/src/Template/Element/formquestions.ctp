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
<?php elseif ($ControllerAction['action'] == 'edit' || $ControllerAction['action'] == 'add') : ?>
	<?php
		$tableHeaders = isset($attr['tableHeaders']) ? $attr['tableHeaders'] : [];
		$tableCells = isset($attr['tableCells']) ? $attr['tableCells'] : [];
		$reorder = isset($attr['reorder']) ? $attr['reorder'] : [];
		echo $this->Html->script('Survey.surveyform', ['block' => true]);

		$displayReorder = isset($reorder) && $reorder && count($tableCells) > 0;
		if ($displayReorder) {
			echo $this->Html->script('ControllerAction.reorder', ['block' => true]);
			$tableHeaders[] = [__('Reorder') => ['class' => 'cell-reorder']];
		}else{
			$tableHeaders[] = [__('') => ['class' => 'cell-reorder']];
			$displayReorder = true;
		}
	?>
	<div class="clearfix"></div>
		<hr>
		<h3><?= __('Survey Questions')?></h3>
		<div class="clearfix">
			<?=
				$this->Form->input($ControllerAction['table']->alias().".survey_question_id", [
					'label' => $this->Label->get('SurveyForms.add_question'),
					'type' => 'select',
					'options' => $attr['options'],
					'value' => 0,
					'onchange' => "$('#reload').val('addQuestion').click();"
				]);
			?>
			<?php
				// echo $this->Form->input($ControllerAction['table']->alias().".section", [
				// 	'label' => $this->Label->get('SurveyForms.add_to_section'),
				// 	'type' => 'select',
				// 	'options' => '',
				// 	'value' => 0,
				// 	'id' => 'sectionDropdown'
				// ]);
			?>
			<?=
				$this->Form->input($ControllerAction['table']->alias().".sectiontxt", [
					'label' => __('Add Section'),
					'type' => 'text',
					'id' => 'sectionTxt'
				]);
			?>
			<div class="form-buttons">
				<div class="button-label"></div><button onclick="SurveyForm.addSection('#sectionTxt');" type="button" class="btn btn-default"><span><?=__('Add Section')?></span></button>
			</div>
			<br/>
		</div>
	<div class="table-wrapper">
		<div class="table-responsive">
		<table class="table table-curved table-input" <?= $displayReorder ? 'id="sortable"' : '' ?>>
			<thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
			<tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
		</table>
		</div>
	</div>
<?php endif ?>
