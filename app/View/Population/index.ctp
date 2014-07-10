<?php
// echo $this->Html->css('table.old', 'stylesheet', array('inline' => false));
echo $this->Html->css('population', 'stylesheet', array('inline' => false));
echo $this->Html->script('app.area', false);
echo $this->Html->script('population', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Population'));
$this->start('contentActions');
if ($_edit) {
	echo $this->Html->link(__('Edit'), array('action' => 'edit', $selectedYear, $areaId), array('id' => 'edit', 'class' => 'divider withLatestAreaId'));
}
$this->end();
$this->assign('contentId', 'population');
$this->start('contentBody');


$formOptions = $this->FormUtility->getFormOptions();
$labelOptions = $formOptions['inputDefaults']['label'];
echo $this->Form->create('Population', $formOptions);

echo $this->element('../Population/controls');
?>
<fieldset id="area_section_group" class="section_group">
    <legend id="area"><?php echo __('Area'); ?></legend>
	<?php echo $this->FormUtility->areapicker('area_id', array('value' => $areaId)); ?>
</fieldset>

<?php echo $this->Form->end(); ?>

<fieldset id="data_section_group" class="section_group">
	<legend><?php echo __('Population'); ?></legend>
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<!--div class="table_cell">Area Level</div-->
				<tr>
					<th class="cell_source"><?php echo __('Source'); ?></span></th>
					<th><?php echo __('Age'); ?></th>
					<th><?php echo __('Male'); ?></th>
					<th><?php echo __('Female'); ?></th>
					<th><?php echo __('Total'); ?></th>
				</tr>

			</thead>
			<?php 
			$allTotal = 0;
			if (!empty($data)): ?>
				<tbody>
					<?php
					
					foreach ($data AS $row):
						$allTotal += $row['male'];
						$allTotal += $row['female'];
						?>
						<tr class="<?php echo $row['data_source'] == 0 ? '' : 'row_estimate'; ?>">
							<td><?php echo $row['source']; ?></td>
							<td class="cell-number"><?php echo $row['age']; ?></td>
							<td class="cell-number"><?php echo $row['male']; ?></td>
							<td class="cell-number"><?php echo $row['female']; ?></td>
							<td class="cell-number cell_total"><?php echo $row['male'] + $row['female']; ?></td>
						</tr>
						<?php
					endforeach;
					?>
				</tbody>
			<?php endif; ?>

			<tfoot>
				<tr>
					<td class="cell-number" colspan="4"><?php echo __('Total'); ?></td>
					<td class="cell_value cell_number"><?php echo $allTotal; ?></td>
				</tr>
			</tfoot>
		</table>
</fieldset>

<script type="text/javascript">
	var currentAreaId = <?php echo intval($areaId); ?>;
</script>
<?php $this->end(); ?>
