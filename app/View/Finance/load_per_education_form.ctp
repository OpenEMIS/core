<fieldset id="data_section_group" class="section_group">
	<legend><?php echo __('Total Public Expenditure Per Education Level'); ?></legend>
	<?php
	if (!empty($data)):
		$globalIndex = 0;
		foreach ($data AS $perEduLevel):
			?>
			<fieldset class="section_break">
				<legend><?php echo $perEduLevel['education_level_name']; ?></legend>
				<table class="table table-striped table-hover table-bordered">
					<thead>
						<tr>
							<th class="cell_arealevel"><?php echo __('Area Level'); ?></th>
							<th class=""><?php echo __('Area'); ?></th>
							<th class=""><?php echo __('Amount'); ?> <?php echo $currency; ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						$rowIndex = 0;
						foreach ($perEduLevel['areas'] AS $row):
							?>
							<tr>
								<?php
								if ($rowIndex == 0) {
									?>
									<td class=""><?php echo $row['area_level_name']; ?></td>
									<?php
								} else if ($rowIndex == 1) {
									if (count($perEduLevel['areas']) > 2) {
										?>
										<td rowspan="<?php echo count($perEduLevel['areas']) - 1; ?>" class=""><?php echo $row['area_level_name']; ?></td>
										<?php
									} else {
										?>
										<td class=""><?php echo $row['area_level_name']; ?></td>
										<?php
									}
								}
								?>
								<td class=""><?php
									echo $this->Form->hidden("PublicExpenditureEducationLevel.$globalIndex.id", array(
										'label' => false,
										'div' => false,
										'after' => false,
										'between' => false,
										'class' => 'form-control',
										'value' => !empty($row['id']) ? $row['id'] : 0
									));
									echo $this->Form->hidden("PublicExpenditureEducationLevel.$globalIndex.area_id", array(
										'label' => false,
										'div' => false,
										'after' => false,
										'between' => false,
										'class' => 'form-control',
										'value' => $row['area_id']
									));
									echo $row['name'];
									?></td>
								<td class="cell-number">
									<?php
									echo $this->Form->input("PublicExpenditureEducationLevel.$globalIndex.value", array(
										'label' => false,
										'div' => false,
										'after' => false,
										'between' => false,
										'class' => 'form-control',
										'value' => $row['value'],
										'onkeypress' => 'return utility.integerCheck(event)'
									));
									?>
								</td>
							</tr>
							<?php
							$rowIndex++;
							$globalIndex++;
						endforeach;
						?>
					</tbody>
				</table>
			</fieldset>
			<?php
		endforeach;
	endif;
	?>
</fieldset>