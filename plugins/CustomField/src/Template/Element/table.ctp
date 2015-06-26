<?php
	$customField = $attr['customField'];

	$tableHeaders = [];
	foreach ($customField->custom_table_columns as $key => $obj) {
		$tableHeaders[$obj->id] = $obj->name;
	}
	//pr($attr);
?>

<div class="input table">
	<label><?= $attr['attr']['label']; ?></label>
	<div class="table-responsive">
		<?php $cellCount = 0; ?>
		<table class="table table-striped table-hover table-bordered">
			<thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
			<tbody>
				<?php foreach ($customField->custom_table_rows as $rowKey => $rowObj) : ?>
					<tr>
						<td><?= $rowObj->name; ?></td>
						<?php $colCount = 0; ?>
						<?php foreach ($customField->custom_table_columns as $columnKey => $columnObj) : ?>
							<?php if ($colCount++ == 0) {continue;} ?>
							<td>
								<?php
									$fieldPrefix = $attr['model'] . '.custom_table_cells.' . $cellCount++;
									echo $this->Form->input($fieldPrefix.".text_value", ['label' => false]);
									echo $this->Form->hidden($fieldPrefix.".custom_field_id", ['value' => $attr['customField']->id]);
									echo $this->Form->hidden($fieldPrefix.".custom_table_column_id", ['value' => $columnObj->id]);
									echo $this->Form->hidden($fieldPrefix.".custom_table_row_id", ['value' => $rowObj->id]);
								?>
							</td>
						<?php endforeach ?>
					</tr>
				<?php endforeach ?>
			</tbody>
		</table>
	</div>
</div>
