<fieldset id="data_section_group" class="section_group">
	<legend><?php echo __('Total Public Expenditure'); ?></legend>

	<fieldset class="section_break" id="parent_section">
		<legend id="parent_level"><?php echo isset($data['parent'][0]) ? __($data['parent'][0]['area_level_name']) : ''; ?></legend>
		<div id="parentlist">
			<table class="table table-striped table-hover table-bordered">
				<thead>
					<tr>
						<th class="table_cell cell_area"><?php echo __('Area'); ?></th>
						<th class="table_cell"><?php echo __('Total Public Expenditure'); ?> <?php echo $currency; ?></th>
						<th class="table_cell"><?php echo __('Total Public Expenditure For Education'); ?> <?php echo $currency; ?></th>
					</tr>
				</thead>

				<?php
				if (!empty($data['parent'])):
					?>
					<tbody>
						<?php
						foreach ($data['parent'] AS $row):
							?>
							<tr>
								<td class="cell-number"><?php echo $row['name']; ?></td>
								<td class="cell-number"><?php echo $row['total_public_expenditure']; ?></td>
								<td class="cell-number"><?php echo $row['total_public_expenditure_education']; ?></td>
							</tr>
							<?php
						endforeach;
						?>
					</tbody>
				<?php endif; ?>
			</table>
		</div>
	</fieldset>

	<fieldset class="section_break" id="children_section">
		<legend id="children_level"><?php echo isset($data['children'][0]) ? __($data['children'][0]['area_level_name']) : ''; ?></legend>
		<div id="mainlist">
			<table class="table table-striped table-hover table-bordered">
				<thead>
					<tr>
						<th class="cell_area"><?php echo __('Area'); ?></th>
						<th class=""><?php echo __('Total Public Expenditure'); ?> <?php echo $currency; ?></th>
						<th class=""><?php echo __('Total Public Expenditure For Education'); ?> <?php echo $currency; ?></th>
					</tr>
				</thead>

				<?php
				if (!empty($data['children'])):
					?>
					<tbody>
						<?php
						foreach ($data['children'] AS $row):
							?>
							<tr>
								<td class="cell-number"><?php echo $row['name']; ?></td>
								<td class="cell-number"><?php echo $row['total_public_expenditure']; ?></td>
								<td class="cell-number"><?php echo $row['total_public_expenditure_education']; ?></td>
							</tr>
							<?php
						endforeach;
						?>
					</tbody>
				<?php endif; ?>
			</table>
		</div>
	</fieldset>
</fieldset>