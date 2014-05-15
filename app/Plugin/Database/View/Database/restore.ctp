<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
?>
<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Label->get('Database.restore'));
$this->start('contentActions');
$this->end();

$this->assign('contentId', 'indicators');
$this->start('contentBody');
?>
<?php echo $this->element('alert'); ?>

<?php if(count($data) > 0 ) { ?>
<?php
echo $this->Form->create('DataProcessing', array(
	'id' => 'submitForm',
	'inputDefaults' => array('label' => false, 'div' => false),	
	'url' => array('plugin' => 'DataProcessing', 'controller' => 'DataProcessing', 'action' => 'indicatorExport'),
	'onsubmit' => 'return jsForm.isSubmitDisabled(this)'
));
?>

<p><?php echo __('The system will %s the current data and restore to your previous data based on the restore point you selected.', "<b>" . __('OVERWRITE ALL') . "</b>"); ?></p> 
<p><?php echo __('Please note that the system will not restore the following tables:'); ?></p>
<ul>
	<li><?php echo __('Security Users, Roles, and Functions'); ?></li>
	<li><?php echo __('System Configuration Values'); ?></li>
	<li><?php echo __('Security'); ?></li>
</ul>
<p><?php echo __('Below are the list of available backup dates, please choose a restore point.'); ?></p>
<div class="table-responsive">
<table class="table table-striped table-hover table-bordered">
	<thead class="table_head">
		<tr>
			<td class="table_cell cell_checkbox"></td>
			<td class="table_cell"><?php echo __('Files'); ?></td>
		</tr>
	</thead>
	<tbody class="table_body">
		<?php $ctr = 0; foreach($data as $item) {  ?>
		<tr class="table_row">
			<td class="table_cell"><input type="radio" name="backupfile[]" value="<?php echo $ctr;?>" <?php echo ($isBackupRunning)?"disabled":"";?> /></td>
			<td class="table_cell"><?php echo $item; ?></td>
		</tr>
		<?php $ctr++; } ?>
	</tbody>
</table>
</div>
<div class="controls">
	<input type="submit" value="<?php echo __('Restore'); ?>" class="btn_save <?php echo ($isBackupRunning)?"btn_disabled":"";?>"  />
</div>

<?php echo $this->Form->end(); ?>
<?php } else { ?>
	<p><?php echo __('No backup files found.'); ?></p>
	
<?php }
?>
<?php $this->end(); ?>  