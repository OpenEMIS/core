<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('fieldset', 'stylesheet', array('inline' => false));
echo $this->Html->css('finance', 'stylesheet', array('inline' => false));

echo $this->Html->script('app.area', false);
echo $this->Html->script('financePerEducation', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Total Public Expenditure Per Education Level'));

$this->start('contentActions');
echo $this->Html->link(__('View'), array('action' => 'financePerEducationLevel', $selectedYear, $selectedAreaId, $selectedEduLevel), array('id' => 'viewLink', '	class' => 'divider withLatestAreaId'));
$this->end();

$this->assign('contentId', 'financePerEducation');
$this->assign('contentClass', 'edit');

$currentYear = intval(date('Y'));
$selectedYear = (isset($selectedYear)) ? $selectedYear : $currentYear;
$currency = "({$this->Session->read('configItem.currency')})";

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('action' => 'financePerEducationLevelEdit', $selectedYear, $selectedAreaId, $selectedEduLevel));
$labelOptions = $formOptions['inputDefaults']['label'];
echo $this->Form->create('Finance', $formOptions);
?>
<div class="row">
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
			'default' => $selectedYear
		));
		?>
	</div>
</div>
<div class="row">
	<label class="col-md-3 control-label"><?php echo __('Education Levels'); ?></label>
	<div class="col-md-4">
		<?php
		echo $this->Form->input('education_level', array(
			'label' => false,
			'div' => false,
			'between' => false,
			'after' => false,
			'id' => 'educationLevel',
			'options' => $educationLevels,
			'class' => 'form-control',
			'default' => $selectedEduLevel
		));
		?>
	</div>
</div>
<fieldset id="area_section_group" class="section_group">
	<legend id="area"><?php echo __('Area'); ?></legend>
	<?php echo $this->FormUtility->areapicker('area_id', array('value' => $selectedAreaId)); ?>
</fieldset>

<div class="replaceHolder">
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
</div>

<?php echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'financePerEducationLevel', $selectedYear, $selectedAreaId, $selectedEduLevel))); ?>
<?php echo $this->Form->end(); ?>

<script type="text/javascript">

	var currentAreaId = <?php echo intval($selectedAreaId); ?>;

</script>
<?php $this->end(); ?>