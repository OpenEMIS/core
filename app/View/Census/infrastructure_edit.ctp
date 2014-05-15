<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);
echo $this->Html->script('infrastructure', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Infrastructure'));

$this->start('contentActions');
echo $this->Html->link(__('View'), array('action' => 'infrastructure', $selectedYear), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

echo $this->Form->create('CensusInfrastructure', array(
		'id' => 'submitForm',
		//'onsubmit' => 'return false',
		'inputDefaults' => array('label' => false, 'div' => false),
		'url' => array('controller' => 'Census', 'action' => 'infrastructureEdit')
			)
	);

echo $this->element('census/year_options');
?>

<input type="hidden" id="is_edit" value="true">
<div id="infrastructure" class="content_wrapper edit">
	<?php
	foreach ($data as $infraname => $arrval) {
		$total = 0;
		?>
		<fieldset class="section_group" id="<?php echo $infraname; ?>">
			<legend><?php
				echo __($infraname);
				$infranameSing = __(Inflector::singularize($infraname));
				?></legend>

			<?php if (count(@$data[$infraname]['materials']) > 0) { ?>
				<?php if ($infraname === 'Buildings' || $infraname === 'Sanitation') { ?>
					<select name="data[Census<?php echo $infranameSing; ?>][material]" id="<?php echo $infraname; ?>category" class="form-control" style="margin-bottom: 10px;">
						<?php foreach ($arrval['materials'] as $key => $value) { ?>
							<option value="<?php echo $key; ?>"><?php echo $value; ?></option>
						<?php } ?>
					</select>
					<?php
				}
			}
			?>
			<?php if ($infraname === 'Sanitation') { ?>
				<select name="data[Census<?php echo $infranameSing; ?>][gender]" id="SanitationGender" class="form-control" style="margin: 0 0 10px 10px;">
					<option value="male"><?php echo __('Male'); ?></option>
					<option value="female"><?php echo __('Female'); ?></option>
					<option value="unisex" ><?php echo __('Unisex'); ?></option>

				</select>
			<?php } ?>

			<table class="table table-striped table-hover table-bordered">
				<thead class="table_head">
					<tr>
						<th class="table_cell cell_category"><?php echo __('Category'); ?></th>
						<?php
						$statusCount = 0;
						foreach ($arrval['status'] as $statVal) {
							$statusCount++;
							?>
							<th class="table_cell"><?php echo $statVal; ?></th>
						<?php } ?>
						<th class="table_cell"><?php echo __('Total'); ?></th>
					</tr>
				</thead>

				<tbody class="table_body" id="<?php echo $infraname; ?>_section">
					<?php
					$ctrModel = 1;
					foreach ($arrval['types'] as $typeid => $typeVal) {
						?>
						<tr class="table_row">
							<td class="table_cell"><?php echo $typeVal; ?></td>

							<!-- Status -->
							<?php
							$statusTotal = 0;
							foreach ($arrval['status'] as $statids => $statVal) {
								?>
								<td class="table_cell">
									<?php
									$modelName = Inflector::singularize($infraname);
									$inputName = 'data[Census' . $modelName . '][' . $ctrModel . ']';
									$infraId = 0;
									$infraVal = 0;
									$infraSource = 0;
									if ($infraname === 'Buildings') { //got 3 dimension
										$infraId = isset($data[$infraname]['data'][$typeid][$statids][key($arrval['materials'])]['id']) ? $data[$infraname]['data'][$typeid][$statids][key($arrval['materials'])]['id'] : '';
										$infraVal = isset($data[$infraname]['data'][$typeid][$statids][key($arrval['materials'])]['value']) ? $data[$infraname]['data'][$typeid][$statids][key($arrval['materials'])]['value'] : '';
										$infraSource = isset($data[$infraname]['data'][$typeid][$statids][key($arrval['materials'])]['source']) ? $data[$infraname]['data'][$typeid][$statids][key($arrval['materials'])]['source'] : '';
										?>
										<input type="hidden" name="<?php echo $inputName . '[infrastructure_material_id]'; ?>" value="<?php echo key($data[$infraname]['materials']); ?>">
										<input type="hidden" name="<?php echo $inputName . '[infrastructure_' . rtrim(strtolower($infraname), "s") . '_id]'; ?>" value="<?php echo $typeid; ?>">


										<?php
									} elseif ($infraname === 'Sanitation') {

										$infraId = isset($data[$infraname]['data'][$typeid][$statids][key($arrval['materials'])]['id']) ? $data[$infraname]['data'][$typeid][$statids][key($arrval['materials'])]['id'] : '';
										$infraVal = isset($data[$infraname]['data'][$typeid][$statids][key($arrval['materials'])]['male']) ? $data[$infraname]['data'][$typeid][$statids][key($arrval['materials'])]['male'] : '';
										$infraSource = isset($data[$infraname]['data'][$typeid][$statids][key($arrval['materials'])]['source']) ? $data[$infraname]['data'][$typeid][$statids][key($arrval['materials'])]['source'] : '';
										?>	
										<input type="hidden" name="<?php echo $inputName . '[infrastructure_material_id]'; ?>" value="<?php echo key($data[$infraname]['materials']); ?>">
										<input type="hidden" name="<?php echo $inputName . '[infrastructure_' . rtrim(strtolower($infraname), "s") . '_id]'; ?>" value="<?php echo $typeid; ?>">

										<?php
									} else {
										$infraId = isset($data[$infraname]['data'][$typeid][$statids]['id']) ? $data[$infraname]['data'][$typeid][$statids]['id'] : '';
										$infraVal = isset($data[$infraname]['data'][$typeid][$statids]['value']) ? $data[$infraname]['data'][$typeid][$statids]['value'] : '';
										$infraSource = isset($data[$infraname]['data'][$typeid][$statids]['source']) ? $data[$infraname]['data'][$typeid][$statids]['source'] : '';
										?>
										<input type="hidden" name="<?php echo $inputName . '[infrastructure_' . rtrim(strtolower($infraname), "s") . '_id]'; ?>" value="<?php echo $typeid; ?>">

										<?php
									} // end if buildings
									$record_tag = "";
									foreach ($source_type as $k => $v) {
										if ($infraSource == $v) {
											$record_tag = "row_" . $k;
										}
									}


									$ctrModel++;
									$statusTotal += $infraVal;

									echo $this->Form->input('value', array(
										'type' => 'text',
										'class' => $record_tag,
										'name' => $inputName . '[value]',
										'maxlength' => 8,
										'before' => '<div class="input_wrapper">',
										'after' => '</div>',
										'onkeyup' => 'Infrastructure.computeTotal(this)',
										'value' => $infraVal
											)
									);
									?>
									<?php if ($infraId > 0) { ?>
										<input type="hidden" name="<?php echo $inputName . '[id]'; ?>" value="<?php echo $infraId; ?>">
									<?php } ?>
									<?php if ($infraname === 'Buildings') { ?>
										<input type="hidden" name="<?php echo $inputName . '[infrastructure_building_id]'; ?>" value="<?php echo $typeid; ?>">
									<?php } ?>
									<input type="hidden" name="<?php echo $inputName . '[infrastructure_status_id]'; ?>" value="<?php echo $statids; ?>">
								</td> <!-- end table_cell -->
							<?php } // end foreach(status)   ?>
							<td class="table_cell cell_total cell_number"><?php
								echo $statusTotal > 0 ? $statusTotal : '';
								$total += $statusTotal;
								?></td>
						</tr>			
					<?php } // end foreach(types)    ?>
				</tbody>

				<tfoot class="table_foot">
					<tr>
						<?php for ($i = 0; $i < $statusCount; $i++) { ?>
							<td class="table_cell"></td>
						<?php } ?>
						<td class="table_cell cell_label"><?php echo __('Total'); ?></td>
						<td class="table_cell cell_value cell_number"><?php echo $total; ?></td>
					</tr>
				</tfoot>
			</table>
		</fieldset>
	<?php } ?>
	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'infrastructure', $selectedYear), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	<?php echo $this->Form->end(); ?>
</div>
<?php $this->end(); ?>