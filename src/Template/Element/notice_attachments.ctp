<?php if ($ControllerAction['action'] == 'index') : ?>
	<?= isset($attr['value']) ? $attr['value'] : 0; ?>
<?php elseif ($ControllerAction['action'] == 'view') : ?>
	<?php
		$tableHeaders = isset($attr['tableHeaders']) ? $attr['tableHeaders'] : [];
		$tableCells = isset($attr['tableCells']) ? $attr['tableCells'] : [];
	?>
	<div class="table-in-view">
		<table class="table table-striped table-hover table-bordered">
			<thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
			<tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
		</table>
	</div>
<?php elseif ($ControllerAction['action'] == 'edit' || $ControllerAction['action'] == 'add') : ?>
	<?php
		$tableHeaders = isset($attr['tableHeaders']) ? $attr['tableHeaders'] : [];
		$tableCells = isset($attr['tableCells']) ? $attr['tableCells'] : [];
	?>

	<div class="clearfix"></div>
		<hr>
		<h3><?= __('Survey Questions')?></h3>
		<div class="clearfix">
			<?= 
				$this->Form->input($ControllerAction['table']->alias().".attachment", [
					'label' => $this->Label->get('SurveyForms.add_question'),
					'type' => 'select',
					'options' => $attr['options'],
					'value' => 0,
					'onchange' => "$('#reload').val('addQuestion').click();"
				]);
			?>
			<div class="form-buttons">
				<div class="button-label"></div><button onclick="SurveyForm.addSection('#sectionTxt');" type="button" class="btn btn-default"><span><?=__('Add Section')?></span></button>
			</div>
			<br/>
		</div>
	<div class="table-responsive">
		<table class="table table-striped table-hover table-bordered table-input">
			<thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
			<tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
		</table>
	</div>
<?php endif ?>
