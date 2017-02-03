<?php
	$tableHeaders = isset($attr['tableHeaders']) ? $attr['tableHeaders'] : [];
	$tableCells = isset($attr['tableCells']) ? $attr['tableCells'] : [];
?>
<?php if ($ControllerAction['action'] == 'view') : ?>
	<div class="table-wrapper">
		<div class="table-in-view">
			<table class="table">
				<thead><?= $this->Html->tableHeaders($tableHeaders); ?></thead>
				<tbody><?= $this->Html->tableCells($tableCells); ?></tbody>
			</table>
		</div>
	</div>
<?php elseif ($ControllerAction['action'] == 'add' || $ControllerAction['action'] == 'edit') : ?>
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
