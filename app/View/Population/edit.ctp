<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('population', 'stylesheet', array('inline' => false));
echo $this->Html->script('app.area', false);
echo $this->Html->script('population', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Population'));

$this->start('contentActions');
echo $this->Html->link(__('View'), array('controller' => 'Population', 'action' => 'index', $selectedYear, $areaId), array('id' => 'viewLink', 'class' => 'divider withLatestAreaId'));
$this->end();

$this->assign('contentId', 'population');
$this->assign('contentClass', 'edit');
$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions();
$labelOptions = $formOptions['inputDefaults']['label'];
echo $this->Form->create('Population', $formOptions);

echo $this->element('../Population/controls');
?>
<input type="hidden" name="data[previousAction]" value="edit"/>
<fieldset id="area_section_group" class="section_group">
    <legend id="area"><?php echo __('Area'); ?></legend>
	<?php echo $this->FormUtility->areapicker('area_id', array('value' => $areaId)); ?>
</fieldset>
<?php 
echo $this->Form->end();

$formOptions = $this->FormUtility->getFormOptions(array('action' => 'edit', $selectedYear, $areaId));
$labelOptions = $formOptions['inputDefaults']['label'];
echo $this->Form->create('Population', $formOptions);
?>

<fieldset id="data_section_group" class="section_group edit">
	<legend><?php echo __('Population'); ?></legend>
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th class="cell_source"><?php echo __('Source'); ?></th>
				<th><?php echo __('Age'); ?></th>
				<th><?php echo __('Male'); ?></th>
				<th><?php echo __('Female'); ?></th>
				<th><?php echo __('Total'); ?></th>
				<th class="cell_delete">&nbsp;</th>
			</tr>
		</thead>
		<?php 
		$allTotal = 0;
		if (!empty($data)):
			?>
			<tbody><?php 
				$recordIndex = 0;
				foreach ($data AS $row):
					?>
					<tr class="<?php echo $row['data_source'] == 0 ? '' : 'row_estimate'; ?>" record-id="<?php echo $row['id']; ?>">
						<td>
							<?php 
							echo $this->Form->input('id', array(
								'label' => false,
								'div' => false,
								'after' => false,
								'between' => false,
								'class' => 'form-control',
								'name' => 'data[Population][' . $recordIndex . '][id]',
								'value' => $row['id']
							));
							
							echo $this->Form->input('source', array(
								'label' => false,
								'div' => false,
								'after' => false,
								'between' => false,
								'class' => 'form-control',
								'name' => 'data[Population][' . $recordIndex . '][source]',
								'value' => $row['source']
							));
							?>
						</td>
						<td>
							<?php
							echo $this->Form->input('age', array(
								'label' => false,
								'div' => false,
								'after' => false,
								'between' => false,
								'class' => 'form-control',
								'name' => 'data[Population][' . $recordIndex . '][age]',
								'value' => $row['age']
							));
							?>
						</td>
						<td>
							<?php
							echo $this->Form->input('male', array(
								'label' => false,
								'div' => false,
								'after' => false,
								'between' => false,
								'class' => 'form-control',
								'name' => 'data[Population][' . $recordIndex . '][male]',
								'value' => $row['male']
							));
							?>
						</td>
						<td>
							<?php
							echo $this->Form->input('female', array(
								'label' => false,
								'div' => false,
								'after' => false,
								'between' => false,
								'class' => 'form-control',
								'name' => 'data[Population][' . $recordIndex . '][female]',
								'value' => $row['female']
							));
							?>
						</td>
						<td class="cell_total"><?php echo $row['male'] + $row['female']; ?></td>
						<td><span class="icon_delete" title="'+i18n.General.textDelete+'" onclick="population.removeRow(this)"></span></td>
					</tr>
					<?php 
					$recordIndex ++;
				endforeach;
				?>
			</tbody>
			<?php
		endif;
		?>
		<tfoot>
			<tr>
				<td colspan="4" class="cell-number"><?php echo __('Total'); ?></td>
				<td class="cell_value cell_number"><?php echo $allTotal; ?></td>
				<td></td>
			</tr>
		</tfoot>
	</table>
	<div class="row"><a class="void icon_plus link_add"><?php echo __('Add') . ' ' . __('Age'); ?></a></div>
		<?php echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'index', $selectedYear, $areaId))); ?>
</fieldset>

<script type="text/javascript">
	var currentAreaId = <?php echo intval($areaId); ?>;
</script>
<?php echo $this->Form->end(); ?>
<?php $this->end(); ?>