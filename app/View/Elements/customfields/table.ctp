<?php if (count($obj[$modelRow]) > 0 || count($obj[$modelColumn]) > 0) : ?>
<?php
	$modelId = $obj[$model]['id'];
	$dataCells = array();
	if(isset($dataValues[$modelId])) {
		foreach ($dataValues[$modelId] as $key => $val) {
			$dataCells[$modelId][$val['survey_table_row_id']][$val['survey_table_column_id']] = $val['value'];
		}
	}
?>
<fieldset class="section_group">
	<legend><?php echo $obj[$model]['name']; ?></legend>
	<div class="table-responsive">
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<th></th>
					<?php
					if(isset($obj[$modelColumn])) :
						foreach ($obj[$modelColumn] as $tableColumn) {
							if($tableColumn['visible'] == 1) :
					?>
							<th><?php echo $tableColumn['name']; ?></th>
					<?php
							endif;
						}
					endif;
					?>
				</tr>
			</thead>
			<tbody>
				<?php
				$index = 1;
				if(isset($obj[$modelRow])) :
					foreach ($obj[$modelRow] as $tableRow) {
						if($tableRow['visible'] == 1) :
				?>
						<tr>
							<td><?php echo $tableRow['name']; ?></td>
							<?php
							if(isset($obj[$modelColumn])) :
								foreach ($obj[$modelColumn] as $tableColumn) {
									if($tableColumn['visible'] == 1) :
							?>
									<td>
										<?php
											$value = '';
											if(isset($dataCells[$modelId][$tableRow['id']][$tableColumn['id']])) {
												$value = $dataCells[$modelId][$tableRow['id']][$tableColumn['id']];
											}
											if($action == 'view') {
												echo $value;
											} else {
												echo $this->Form->hidden("$modelCell.table.$modelId.$index.survey_question_id", array('value' => $modelId));
												echo $this->Form->hidden("$modelCell.table.$modelId.$index.survey_table_row_id", array('value' => $tableRow['id']));
												echo $this->Form->hidden("$modelCell.table.$modelId.$index.survey_table_column_id", array('value' => $tableColumn['id']));
												echo $this->Form->input("$modelCell.table.$modelId.$index.value", array(
													'label' => false, 
													'div' => false, 
													'before' => false, 
													'between' => false, 
													'after' => false, 
													'value' => $value
												));
											}
											$index++;
										?>
									</td>
							<?php
									endif;
								}
							endif;
							?>
						</tr>
				<?php
						endif;
					}
				endif;
				?>
			</tbody>
		</table>
	</div>
</fieldset>
<?php endif ?>