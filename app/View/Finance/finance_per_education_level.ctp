<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('finance', 'stylesheet', array('inline' => false));

echo $this->Html->script('app.area', false);
echo $this->Html->script('financePerEducation', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Total Public Expenditure Per Education Level'));
$this->start('contentActions');
if ($_edit) {
	echo $this->Html->link(__('Edit'), array('action' => 'financePerEducationLevelEdit', $selectedYear, $areaId), array('id' => 'edit', 'class' => 'divider withLatestAreaId'));
}
$this->end();
$this->assign('contentId', 'financePerEducation');

$currentYear = intval(date('Y'));
$selectedYear = (isset($selectedYear)) ? $selectedYear : $currentYear;
$currency = "({$this->Session->read('configItem.currency')})";

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions();
$labelOptions = $formOptions['inputDefaults']['label'];
echo $this->Form->create('Finance', $formOptions);
?>

<div class="row per_education_level">
	<label class="col-md-3 control-label"><?php echo __('View'); ?></label>
	<div type="select" name="view" value="1" class="col-md-4">
		<select name="data[view]" id="view" class="form-control">
			<option value="Total Public Expenditure"><?php echo __('Total Public Expenditure'); ?></option>
			<option value="Total Public Expenditure Per Education Level"><?php echo __('Total Public Expenditure Per Education Level'); ?></option>
		</select>
	</div>
</div>

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

<fieldset id="area_section_group" class="section_group">
	<legend id="area"><?php echo __('Area'); ?></legend>
	<?php echo $this->FormUtility->areapicker('area_id', array('value' => $areaId)); ?>
</fieldset>

<?php echo $this->Form->end(); ?>
<fieldset id="data_section_group" class="section_group">
	<legend><?php echo __('Total Public Expenditure Per Education Level'); ?></legend>


	<?php foreach ($eduLevels as $eduLevel): ?>
		<fieldset class="section_break">
			<legend><?php echo $eduLevel['name']; ?></legend>

			<div class="mainlist" id="edu_level_<?php echo $eduLevel['id']; ?>" name="edu_level_<?php echo $eduLevel['id']; ?>">
				<table class="table table-striped table-hover table-bordered">
					<thead>
						<tr>
							<th class="cell_arealevel"><?php echo __('Area Level'); ?></th>
							<th class=""><?php echo __('Area'); ?></th>
							<th class=""><?php echo __('Amount'); ?> <?php echo $currency; ?></th>
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
	<?php endforeach; ?>

</fieldset>

<script type="text/javascript">
	var currentAreaId = <?php echo intval($areaId); ?>;
</script>
<?php $this->end(); ?>