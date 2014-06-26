<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->script("/{$controllerName}/js/buildDataProcess", false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Label->get('DataProcessing.custom_indicators'));

$this->start('contentActions');
echo $this->Html->link(__('View'), array('action' => 'build'), array('class' => 'divider')); 
$this->end();

$this->assign('contentId', 'report-list');
$this->start('contentBody');
?>
<?php echo $this->element('alert'); ?>

<?php 
$ctr = 0;
?>

<span id="controller" class="none"><?php echo $controllerName; ?></span>
<span id="mode" class="none">add</span>

<?php echo $this->Form->create('Report', array('type' => 'file')); ?>
<div class="row form-group">
	<label for="name" class="col-md-3 control-label">Name</label>
	<div class="col-md-4"><input id="name" class="form-control" name="data[name]" type="text" maxlength="150"/></div>
</div>
<div class="row form-group">
	<label for="description" class="col-md-3 control-label">Description</label>
	<div class="col-md-4"><textarea id="description" class="form-control" name="data[description]" cols="40" rows"7"></textarea></div>
</div>
<div class="row form-group">
	<label for="file" class="col-md-3 control-label">File</label>
	<div class="col-md-4"><input type="file" class="form-control" name="data[doc_file]" value="" id="doc_file"></div>
</div>
<div class="controls view_controls">
	<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
	<?php echo $this->Html->link(__('Cancel'), array('action' => 'Build'), array('class' => 'btn_cancel btn_left')); ?>
</div>
<?php echo $this->Form->end(); ?>


<script>
var maskId ;
$(document).ready(function(){
    jQuery.browser = {};
    jQuery.browser.mozilla = /mozilla/.test(navigator.userAgent.toLowerCase()) && !/webkit/.test(navigator.userAgent.toLowerCase());
    jQuery.browser.webkit = /webkit/.test(navigator.userAgent.toLowerCase());
    jQuery.browser.opera = /opera/.test(navigator.userAgent.toLowerCase());
    jQuery.browser.msie = /msie/.test(navigator.userAgent.toLowerCase());

    CustomReport.init(<?php echo $setting['maxFilesize']; ?>);

    $('.btn_save').click(CustomReport.validate.validateSave);

    <?php if(isset($status) && count($status) > 0){ ?>
        CustomReport.displayMessage('<?php echo $status['msg'] ?>', <?php echo $status['type'] ?>);
    <?php } ?>

});
</script>

<?php $this->end(); ?> 