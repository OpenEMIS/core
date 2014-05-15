<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Label->get('DataProcessing.export'));
$this->start('contentActions');
$this->end();

$this->assign('contentId', 'indicators');
$this->start('contentBody');
?>
<?php echo $this->element('alert'); ?>
<script type="text/javascript" charset="utf-8">
$(document).ready(function(){
    var url = '<?php echo $this->Html->url($url); ?>';
    $('#DataProcessingExportFormat').change(function(e){
        e.preventDefault();
        window.location.href = url+'/'+$(this).find('option:selected').val();
    });
});

	function toggleSelect(obj) {
		var table = $(obj).closest('.table');
		table.find('.table_body input[type="checkbox"]').each(function() {
				if(obj.checked) {
					if( $(this).attr('disabled') == undefined){
						$(this).attr('checked','checked');
					}
				} else {
					$(this).removeAttr('checked');
				}
		});
	}

</script>
<?php
echo $this->Form->create('DataProcessing', array(
	'inputDefaults' => array('label' => false, 'div' => false),
	'url' => array('plugin' => 'DataProcessing', 'controller' => 'DataProcessing', 'action' => 'export'),
	'onsubmit' => 'return jsForm.isSubmitDisabled(this)'
));
?>
<div class="row form-group input">
	<label class="col-md-3 control-label"><?php echo __('Format'); ?></label>
	<div class="col-md-4">
		<?php
		echo $this->Form->input('export_format', array(
			'class'=>'form-control',
			'options' => $exportOptions
		));
		?>
	</div>
</div>
<?php foreach($list as $key => $groupItems) { ?>


<fieldset class="section_group">
    <legend><?php echo ucfirst($key); ?></legend>
    <div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
        <thead class="table_head">
        	<tr>
	            <td class="table_cell cell_checkbox"><input type="checkbox" value="1" onchange="toggleSelect(this)"></td>
	            <td class="table_cell"><?php echo __('Indicator'); ?></td>
	        </tr>
        </thead>

        <tbody class="table_body">
		<?php foreach($groupItems as $item) {
		    $obj = $item['BatchIndicator']; ?>

			<tr class="table_row">
				<td class="table_cell">
					<input type="checkbox" name="data[BatchIndicator][<?php echo $obj['id']; ?>]" <?php echo ($obj['enabled']==1)?'': 'disabled="disabled"'?>  value="<?php echo $obj['id']; ?>" />
				</td>
				<td class="table_cell"><?php echo __($obj['name']); ?></td>
			</tr>
		<?php  } ?>
		</tbody>
	</table>
	</div>
</fieldset>
<?php } ?>
</div>

<div class="controls">
	<input type="submit" value="<?php echo __('Export'); ?>" class="btn_save <?php echo ($isBackupRunning)?"btn_disabled":"";?>" />
</div>

<?php echo $this->Form->end(); ?>

<?php $this->end(); ?> 