<fieldset d="data_section_group" class="section_group">
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
								<td class="cell-number">
									<?php
									echo $this->Form->hidden("PublicExpenditure.parent.0.id", array(
										'label' => false,
										'div' => false,
										'after' => false,
										'between' => false,
										'class' => 'form-control',
										'value' => !empty($row['id']) ? $row['id'] : 0
									));
									echo $this->Form->hidden("PublicExpenditure.parent.0.area_id", array(
										'label' => false,
										'div' => false,
										'after' => false,
										'between' => false,
										'class' => 'form-control',
										'value' => $row['area_id']
									));
									echo $row['name'];
									?>
								</td>
								<td class="cell-number">
									<?php
									echo $this->Form->input("PublicExpenditure.parent.0.total_public_expenditure", array(
										'label' => false,
										'div' => false,
										'after' => false,
										'between' => false,
										'class' => 'form-control',
										'value' => $row['total_public_expenditure'],
										'onkeypress' => 'return utility.integerCheck(event)'
									));
									?>
								</td>
								<td class="cell-number">
									<?php
									echo $this->Form->input("PublicExpenditure.parent.0.total_public_expenditure_education", array(
										'label' => false,
										'div' => false,
										'after' => false,
										'between' => false,
										'class' => 'form-control',
										'value' => $row['total_public_expenditure_education'],
										'onkeypress' => 'return utility.integerCheck(event)'
									));
									?>
								</td>
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
						$recordIndex = 0;
						foreach ($data['children'] AS $row):
							?>
							<tr>
								<td class="cell-number">
									<?php
									echo $this->Form->hidden("PublicExpenditure.children.$recordIndex.id", array(
										'label' => false,
										'div' => false,
										'after' => false,
										'between' => false,
										'class' => 'form-control',
										'value' => !empty($row['id']) ? $row['id'] : 0
									));
									echo $this->Form->hidden("PublicExpenditure.children.$recordIndex.area_id", array(
										'label' => false,
										'div' => false,
										'after' => false,
										'between' => false,
										'class' => 'form-control',
										'value' => $row['area_id']
									));
									echo $row['name'];
									?>
								</td>
								<td class="cell-number">
									<?php
									echo $this->Form->input("PublicExpenditure.children.$recordIndex.total_public_expenditure", array(
										'label' => false,
										'div' => false,
										'after' => false,
										'between' => false,
										'class' => 'form-control',
										'value' => $row['total_public_expenditure'],
										'onkeypress' => 'return utility.integerCheck(event)'
									));
									?>
								</td>
								<td class="cell-number">
									<?php
									echo $this->Form->input("PublicExpenditure.children.$recordIndex.total_public_expenditure_education", array(
										'label' => false,
										'div' => false,
										'after' => false,
										'between' => false,
										'class' => 'form-control',
										'value' => $row['total_public_expenditure_education'],
										'onkeypress' => 'return utility.integerCheck(event)'
									));
									?>
								</td>
							</tr>
							<?php
							$recordIndex++;
						endforeach;
						?>
					</tbody>
				<?php endif; ?>
			</table>
		</div>
	</fieldset>
</fieldset>