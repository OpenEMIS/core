<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Label->get('DataProcessing.process'));
$this->start('contentActions');
if($_execute) { ?>
	<a class="void divider" href="javascript: void(0);" onclick="killallprocess();"><?php echo __('Abort All'); ?></a>
	<a class="void divider" href="javascript: void(0);" onclick="clearallprocess();"><?php echo __('Clear All'); ?></a>
<?php }
$this->end(); ?>
<?php
$this->assign('contentId', 'indicators');
$this->start('contentBody');
?>
<div id="indicators" class="content_wrapper">
	<?php
	echo $this->Form->create('DataProcessing', array(
		'inputDefaults' => array('label' => false, 'div' => false),
		'url' => array('plugin' => 'DataProcessing', 'controller' => 'DataProcessing', 'action' => 'indicators'),
		'onsubmit' => 'return jsForm.isSubmitDisabled(this)'
	));
	?>
	<h1>
		<span><?php echo __('Export Indicators'); ?></span>
	</h1>
	<?php echo $this->element('alert'); ?>
	<div class="row input" style="margin-left: 5px;">
		<div class="label" style="width: 60px;"><?php echo __('Export To'); ?></div>
		<div class="value">
			<?php
			echo $this->Form->input('export_format', array(
				'options' => $exportOptions
			));
			?>
		</div>
	</div>
	
	<div class="table full_width" style="margin: 20px 0 0 3px;">
		<div class="table_head">
			<div class="table_cell cell_checkbox"><!--input type="checkbox" onchange="jsForm.toggleSelect(this);" checked="checked" /--></div>
			<div class="table_cell"><?php echo __('Indicator'); ?></div>
		</div>
		
		<div class="table_body">
			<?php foreach($list as $item) { $obj = $item['BatchIndicator']; ?>
			<div class="table_row">
				<div class="table_cell">
					<?php $attr = $obj['enabled']==1 ? 'checked="checked"' : 'disabled="disabled"'; ?>
					<input type="checkbox" name="data[BatchIndicator][<?php echo $obj['id']; ?>]" <?php echo $attr ?> disabled="disabled" />
				</div>
				<div class="table_cell"><?php echo __($obj['name']); ?></div>
			</div>
			<?php } ?>
		</div>
	</div>
	
	<div class="controls">
		<input type="submit" value="<?php echo __('Export'); ?>" class="btn_save <?php echo ($isBackupRunning)?"btn_disabled":"";?>" />
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>
<?php $this->end(); ?> 