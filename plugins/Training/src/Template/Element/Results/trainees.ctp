<?php
	$tableHeaders = isset($attr['tableHeaders']) ? $attr['tableHeaders'] : [];
	$tableCells = isset($attr['tableCells']) ? $attr['tableCells'] : [];
?>
<?php if ($ControllerAction['action'] == 'edit') : ?>
	<div class="input clearfix">
		<label for=""><?= __('Trainees'); ?></label>
<?php endif ?>
	<div class="table-wrapper">
		<div class="table-in-view" autocomplete-ref="trainee_id">
			<table class="table">
				<thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
				<tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
			</table>
		</div>
	</div>	
<?php if ($ControllerAction['action'] == 'edit') : ?>
	</div>
<?php endif ?>
