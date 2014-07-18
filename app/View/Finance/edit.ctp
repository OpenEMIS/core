<?php
// echo $this->Html->css('table.old', 'stylesheet', array('inline' => false));
echo $this->Html->css('fieldset', 'stylesheet', array('inline' => false));
echo $this->Html->css('finance', 'stylesheet', array('inline' => false));

echo $this->Html->script('app.area', false);
echo $this->Html->script('finance', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Total Public Expenditure'));
$this->start('contentActions');
echo $this->Html->link(__('View'), array('action' => 'index', $selectedYear, $areaId), array('id' => 'viewLink', 'class' => 'divider withLatestAreaId'));
$this->end();
$this->assign('contentId', 'finance');
$this->assign('contentClass', 'edit');

$currentYear = intval(date('Y'));
$selectedYear = (isset($selectedYear)) ? $selectedYear : $currentYear;
$currency = "({$this->Session->read('configItem.currency')})";

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions();
$labelOptions = $formOptions['inputDefaults']['label'];
echo $this->Form->create('Finance', $formOptions);
?>

<div class="row total_public_expenditure year">
	<label class="col-md-3 control-label"><?php echo __('Year'); ?></label>
	<div class="col-md-4">
		<?php
		echo $this->Form->input('year', array(
			'label' => false,
			'div' => false,
			'between' => false,
			'after' => false,
			'id' => 'financeYear',
			'options' => $yearList,
			'class' => 'form-control',
			'default' => $selectedYear,
			'onchange' => 'jsForm.change(this)',
			'url' => 'Finance/' . $this->action
		));
		?>
	</div>
</div>

<div class="row total_public_expenditure">
	<label class="col-md-3 control-label"><?php echo __('GNP'); ?> <?php echo $currency; ?></label>
	<div class="col-md-4">
		<div class="input_wrapper">
			<input type="text" id="gnp" name="data[gnp]" value="" maxlength="30" autocomplete="false" onkeypress="return utility.integerCheck(event)" onkeyup="Finance.checkEdited()" />
		</div>
	</div>
</div>

<fieldset id="area_section_group" class="section_group">
    <legend id="area"><?php echo __('Area'); ?></legend>
	<?php echo $this->FormUtility->areapicker('area_id', array('value' => $areaId)); ?>
</fieldset>

<?php echo $this->Form->end(); ?>
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
						$recordIndex = 0;
						foreach ($data['parent'] AS $row):
							?>
							<tr>
								<td class="cell-number">
									<?php 
									echo $this->Form->hidden('id', array(
										'label' => false,
										'div' => false,
										'after' => false,
										'between' => false,
										'class' => 'form-control',
										'id' => 'PopulationId',
										'name' => 'data[Population][' . $recordIndex . '][id]',
										'value' => $row['id']
									));
									echo $row['name'];
									
									?>
								</td>
								<td class="cell-number">
									<?php echo $row['total_public_expenditure']; ?>
								</td>
								<td class="cell-number">
									<?php echo $row['total_public_expenditure_education']; ?>
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
						foreach ($data['children'] AS $row):
							?>
							<tr>
								<td class="cell-number">
									<?php echo $row['name']; ?>
								</td>
								<td class="cell-number">
									<?php echo $row['total_public_expenditure']; ?></td>
								<td class="cell-number">
									<?php echo $row['total_public_expenditure_education']; ?>
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
</fieldset>

<?php echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'index', $selectedYear, $areaId))); ?>
</fieldset>

<script type="text/javascript">
	var currentAreaId = <?php echo intval($areaId); ?>;
	$(document).ready(function() {
		Finance.fetchGNP();
	});
</script>


<?php $this->end(); ?>