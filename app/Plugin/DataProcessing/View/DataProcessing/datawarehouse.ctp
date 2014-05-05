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

    $('.btn_save').click(function(e){
        // e.preventDefault();
        if($('.census-table:checked').length < 1){
            alert('Please select census tables to be exported.');
            return false;
        }
        // console.info('click');
    });
    <?php if(isset($error)){ ?>
    var alertOpt = {
        // id: 'alert-' + new Date().getTime(),
        parent: 'body',
        title: i18n.Areas.titleDismiss,
        text: '<?php echo $error; ?>',
        type: alertType.error, // alertType.ok or alertType.info or alertType.warn or alertType.error
        position: 'top',
        css: {}, // positioning of your alert, or other css property like width, eg. {top: '-10px', left: '-20px'}
        autoFadeOut: true
    };

    $.alert(alertOpt);
    <?php } ?>
});
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
			'options' => $exportOptions,
			'class'=>'form-control',
			'default' => 'Datawarehouse'
		));
		?>
	</div>
</div>

 <div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
	<thead class="table_head">
		<tr>
			<td class="table_cell cell_checkbox"></td>
			<td class="table_cell"><?php echo __('Census'); ?></td>
		</tr>
	</thead>

	<tbody class="table_body">
		<?php foreach( $olapList['census'] as $item) { $obj = $item; ?>
		<tr class="table_row">
			<td class="table_cell">
				<?php $attr = 'checked= "checked"'; //'$obj['enabled']==1 ? 'checked="checked"' : 'disabled="disabled"'; ?>
				<input type="checkbox" class="census-table" name="data[Olap][census][]" value="<?php echo $item; ?>" <?php echo $attr ?> />
			</td>
			<td class="table_cell"><?php echo __($obj); ?></td>
		</tr>
		<?php } ?>
		<?php foreach( $olapList['lookup'] as $item) { $obj = $item; ?>
			<input type="hidden" name="data[Olap][lookup][]" value="<?php echo $item; ?>" <?php echo $attr ?> />
		<?php } ?>
	</tbody>
	</table>
</div>

<div class="controls">
	<input type="submit" value="<?php echo __('Export'); ?>" class="btn_save <?php echo ($isBackupRunning)?"btn_disabled":"";?>" />
</div>

<?php echo $this->Form->end(); ?>


<?php $this->end(); ?> 