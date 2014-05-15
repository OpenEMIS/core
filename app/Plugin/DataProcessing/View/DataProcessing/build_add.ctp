<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<?php 
$ctr = 0;
?>
    <div class="content_wrapper" id="report-list">
        <span id="controller" class="none"><?php echo $controllerName; ?></span>
        <span id="mode" class="none">add</span>
        <h1>
            <span><?php echo __('Custom Indicators'); ?></span>
            <?php echo $this->Html->link(__('View'), array('action' => 'build'), array('class' => 'divider')); ?>
        </h1>
        <?php echo $this->Form->create('Report', array('type' => 'file')); ?>
		<div class="row">
			<div class="label"><label for="name">Name</label></div>
			<div class="value"><input id="name" class="default" name="data[name]" type="text" maxlength="150"/></div>
		</div>
		<div class="row">
			<div class="label"><label for="description" >Description</label></div>
			<div class="value"><textarea id="description" class="default" name="data[description]" cols="40" rows"7"></textarea></div>
		</div>
		<div class="row">
			<div class="label"><label for="file" >File</label></div>
			<div class="value"><input type="file" class="default" name="data[doc_file]" value="" id="doc_file"></div>
		</div>
        <div class="controls view_controls">
			<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
			<?php echo $this->Html->link(__('Cancel'), array('action' => 'Build'), array('class' => 'btn_cancel btn_left')); ?>
		</div>
        <?php echo $this->Form->end(); ?>
    </div>

<?php echo $this->Html->script("/{$controllerName}/js/buildDataProcess", false); ?>
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