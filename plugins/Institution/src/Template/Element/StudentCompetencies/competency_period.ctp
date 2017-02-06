<?php
	$competencyPeriodCount = isset($attr['competencyPeriodCount']) ? $attr['competencyPeriodCount'] : 0;
	$tableHeaders = isset($attr['tableHeaders']) ? $attr['tableHeaders'] : [];
	$tableCells = isset($attr['tableCells']) ? $attr['tableCells'] : [];
?>
<?php if ($ControllerAction['action'] == 'view') : ?>
	<?php if ($competencyPeriodCount == 0) : ?>
		<?php echo $this->Label->get('StudentCompetencies.noPeriod'); ?>
	<?php else : ?>
		<div class="table-wrapper">
			<div class="table-in-view">
				<table class="table">
					<thead><?= $this->Html->tableHeaders($tableHeaders); ?></thead>
					<tbody><?= $this->Html->tableCells($tableCells); ?></tbody>
				</table>
			</div>
		</div>
	<?php endif ?>
<?php elseif ($ControllerAction['action'] == 'add' || $ControllerAction['action'] == 'edit') : ?>
	<?php if ($competencyPeriodCount == 0) : ?>
		<?php
			echo $this->Form->input('competency_period', [
				'class' => 'form-control',
				'disabled' => 'disabled',
				'value' => $this->Label->get('StudentCompetencies.noPeriod')
			]);
		?>
	<?php else : ?>
		<div class="input">
			<label><?= $attr['label']; ?></label>
			<div class="input-form-wrapper">
				<div class="table-wrapper">
					<div class="table-responsive">
						<table class="table table-curved row-align-top">
							<thead><?= $this->Html->tableHeaders($tableHeaders); ?></thead>
							<tbody><?= $this->Html->tableCells($tableCells); ?></tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	<?php endif ?>
<?php endif ?>
