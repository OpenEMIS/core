<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>



<?php 
echo $this->Form->create('Database', array(
			'url' => array(
				'controller' => 'database', 
				'action' => 'backup'
			),
			'inputDefaults' => array('label' => false, 'div' =>false)
		));
?>


<div id="indicators" class="content_wrapper">
	<?php if(count($data) > 0 ) { ?>
	<?php
	echo $this->Form->create('DataProcessing', array(
		'id' => 'submitForm',
		'inputDefaults' => array('label' => false, 'div' => false),	
		'url' => array('plugin' => 'DataProcessing', 'controller' => 'DataProcessing', 'action' => 'indicatorExport'),
		'onsubmit' => 'return jsForm.isSubmitDisabled(this)'
	));
	?>
	<h1>
		<span><?php echo __('Restore'); ?></span>
		
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<p><?php echo __('The system will %s the current data and restore to your previous data based on the restore point you selected.', "<b>" . __('OVERWRITE ALL') . "</b>"); ?></p> 
	<p><?php echo __('Please note that the system will not restore the following tables:'); ?></p>
	<ul>
		<li><?php echo __('Security Users, Roles, and Functions'); ?></li>
		<li><?php echo __('System Configuration Values'); ?></li>
		<li><?php echo __('Security'); ?></li>
	</ul>
	<p><?php echo __('Below are the list of available backup dates, please choose a restore point.'); ?></p>
	<div class="table full_width" style="margin: 20px 0 0 3px;">
		<div class="table_head">
			<div class="table_cell cell_checkbox"></div>
			<div class="table_cell"><?php echo __('Files'); ?></div>
		</div>
		
		<div class="table_body">
			
			<?php $ctr = 0; foreach($data as $item) {  ?>
			<div class="table_row">
				<div class="table_cell"><input type="radio" name="backupfile[]" value="<?php echo $ctr;?>" <?php echo ($isBackupRunning)?"disabled":"";?> /></div>
				<div class="table_cell"><?php echo $item; ?></div>
			</div>
			<?php $ctr++; } ?>
		</div>
	</div>
	
	<div class="controls">
		<input type="submit" value="<?php echo __('Restore'); ?>" class="btn_save <?php echo ($isBackupRunning)?"btn_disabled":"";?>"  />
	</div>
	
	<?php echo $this->Form->end(); ?>
	<?php } else { ?>
		<h1>
			<span><?php echo __('Restore'); ?></span>

		</h1>
		<p><?php echo __('No backup files found.'); ?></p>
		
	<?php }
?>
	
</div>