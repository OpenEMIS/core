<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('population', 'stylesheet', array('inline' => false));
echo $this->Html->script('app.area', false);
echo $this->Html->script('population', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Population'));

$this->start('contentActions');
echo $this->Html->link(__('View'), array('controller' => 'Population', 'action' => 'index'), array('id' => 'viewLink', 'class' => 'divider'));
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

<?php echo $this->Form->end(); ?>
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
		
		<tfoot>
			<tr>
				<td colspan="4" class="cell-number"><?php echo __('Total'); ?></td>
				<td class="cell_value cell_number">0</td>
				<td></td>
			</tr>
		</tfoot>
	</table>
	<div class="row"><a class="void icon_plus link_add"><?php echo __('Add') . ' ' . __('Age'); ?></a></div>
	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right btn_disabled" onClick="population.save()" />
		<input type="button" value="<?php echo __('Cancel'); ?>" class="btn_cancel btn_left" />
	</div>
</fieldset>

<script type="text/javascript">

			$(document).ready(function() {
				var currentAreaId = <?php echo intval($areaId); ?>;
			});

</script>
<?php $this->end(); ?>