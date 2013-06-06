<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="indicators" class="content_wrapper">
	<?php
	echo $this->Form->create('DataProcessing', array(
		'inputDefaults' => array('label' => false, 'div' => false),
		'url' => array('plugin' => 'DataProcessing', 'controller' => 'DataProcessing', 'action' => 'exports'),
		'onsubmit' => 'return jsForm.isSubmitDisabled(this)'
	));
	?>
	<h1>
		<span><?php echo __("Export"); ?></span>
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

<script type="text/javascript" charset="utf-8">
$(document).ready(function(){
    var url = '<?php echo $this->Html->url($url); ?>';
    $('#DataProcessingExportFormat').change(function(e){
        e.preventDefault();
        window.location.href = url+'/'+$(this).find('option:selected').val();
    });
});
</script>