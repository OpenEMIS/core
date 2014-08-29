<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);
echo $this->Html->script('infrastructure', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Infrastructure'));

$this->start('contentActions');
if ($_edit && $isEditable) {
	echo $this->Html->link(__('Edit'), array('action' => 'infrastructureEdit', $selectedYear), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
echo $this->element('census/year_options');
//pr($data);
?>

<input type="hidden" id="is_edit" value="false">
<div id="infrastructure" class="">

	<?php
	foreach ($data as $infraname => $arrval) {
		$total = 0;
		?>
		<fieldset class="section_group" id="<?php echo $infraname; ?>">
			<legend><?php
				echo ($infraname == 'Sanitation' ? __('Sanitation') : __($infraname));
				$infranameSing = __(Inflector::singularize($infraname));
				?></legend>

			<?php if (count(@$data[$infraname]['materials']) > 0) { ?>
				<?php if ($infraname === 'Buildings' || $infraname === 'Sanitation') { ?>
					<select name="data[Census<?php echo $infraname; ?>][material]" id="<?php echo $infraname; ?>category" class="form-control topSelectControl">
						<?php foreach ($arrval['materials'] as $key => $value) { ?>
							<option value="<?php echo $key; ?>"><?php echo $value; ?></option>
						<?php } ?>
					</select>
					<?php
				}
			}
			?>
			<?php if ($infraname === 'Sanitation') { 
				echo $this->Form->input('gender_id', array(
					'id' => 'SanitationGender',
					'name' => 'data[CensusCensusSanitation][gender]',
					'label' => false,
					'div' => false,
					'class' => 'form-control topSelectControl',
					'options' => $genderOptions,
					'autocomplete' => 'off'
				));
			} ?>

			<table class="table table-striped table-hover table-bordered">
				<thead>
					<tr>
						<th class="cell_category"><?php echo __('Category'); ?></th>
						<?php
						$statusCount = 0;
						foreach ($arrval['status'] as $statVal) {
							$statusCount++;
							?>
							<th style="white-space:normal"><?php echo $statVal; ?></th>
						<?php } ?>
						<th><?php echo __('Total'); ?></th>
					</tr>
				</thead>

				<tbody id="<?php echo $infraname; ?>_section">
					<?php foreach ($arrval['types'] as $typeid => $typeVal) { ?>
						<tr>
							<td><?php echo $typeVal; ?></td>

							<!-- Status -->
							<?php
							$statusTotal = 0;
							foreach ($arrval['status'] as $statids => $statVal) {
								?>

								<?php
								if ($infraname === 'Buildings') {
									//pr($data[$infraname]['data'][$typeid][$statids]);
									$val = (isset($data[$infraname]['data'][$typeid][$statids][key($arrval['materials'])]['value']) ? $data[$infraname]['data'][$typeid][$statids][key($arrval['materials'])]['value'] : '');
									$source = (isset($data[$infraname]['data'][$typeid][$statids][key($arrval['materials'])]['source']) ? $data[$infraname]['data'][$typeid][$statids][key($arrval['materials'])]['source'] : '');
								} elseif ($infraname === 'Sanitation') {
									//echo $data[$infraname]['data'][$typeid][$statids][key($arrval['materials'])]['id'];
									$val = (isset($data[$infraname]['data'][$typeid][$statids][key($arrval['materials'])]['male']) ? $data[$infraname]['data'][$typeid][$statids][key($arrval['materials'])]['male'] : '');
									$source = (isset($data[$infraname]['data'][$typeid][$statids][key($arrval['materials'])]['source']) ? $data[$infraname]['data'][$typeid][$statids][key($arrval['materials'])]['source'] : '');
								} else {
									$val = (isset($data[$infraname]['data'][$typeid][$statids]['value']) ? $data[$infraname]['data'][$typeid][$statids]['value'] : '');
									$source = (isset($data[$infraname]['data'][$typeid][$statids]['source']) ? $data[$infraname]['data'][$typeid][$statids]['source'] : '');
								}
								$statusTotal += $val;
								$record_tag = "";
								foreach ($source_type as $k => $v) {
									if ($source == $v) {
										$record_tag = "row_" . $k;
									}
								}
								?>

								<td class="cell_number <?php echo $record_tag; ?>">
									<?php echo $val; ?>
								</td>
							<?php } // end foreach(status)  ?>
							<!-- Status -->

							<td class="cell_number"><?php
								echo $statusTotal > 0 ? $statusTotal : '';
								$total += $statusTotal;
								?></td>
						</tr>
					<?php } ?>
				</tbody>

				<tfoot class="table_foot">
					<tr>
						<td style="text-align: right" colspan="<?php echo $statusCount+1; ?>"><?php echo __('Total'); ?></td>
						<td class="table_cell cell_value cell-number"><?php echo $total; ?></td>
					</tr>
				</tfoot>
			</table>
		</fieldset>

	<?php } ?>
</div>
<?php $this->end(); ?>