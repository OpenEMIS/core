<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('/Reports/css/reports', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<?php 
$ctr = 0;
if(@$enabled === true){
?>
    <div class="content_wrapper" id="report-list">
        <span id="controller" class="none"><?php echo $controllerName; ?></span>
      <h1>
          <span><?php echo __('Custom Indicators'); ?></span>
          <?php echo $this->Html->link(__('View'), array('action' => 'custom'), array('class' => 'divider')); ?>
      </h1>
      <?php echo $this->Form->create('Report', array('type' => 'file')); ?>

        <input id="mode" name="data[mode]" type="hidden" value="add"/>
        <input name="data[id]" value="<?php echo $data['id'] ?>" id="reportId" type="hidden" />
      <!--fieldset class="section_break"-->
          <div class="row">
              <div class="label"><label for="name">Name</label></div>
              <div class="value"><input id="name" class="default" name="data[name]" value="<?php echo $data['name']?>" type="text" maxlength="150"/></div>
          </div>
          <div class="row">
              <div class="label"><label for="description" >Description</label></div>
              <div class="value"><textarea id="description" class="default" name="data[description]" cols="40" rows"7"><?php echo $data['description']?></textarea></div>
          </div>
          <div class="row">
              <div class="label"><label for="file" >File</label></div>
              <div class="value"><input type="file" class="default" name="data[doc_file]" value="" id="doc_file"></div>
          </div>
      <!--/fieldset-->
      <div class="controls view_controls">
            <input type="submit" value="Save" class="btn_save btn_right">
            <a href="/demo_eugene/Reports/Custom" class="btn_cancel btn_left">Cancel</a>	</div>
      <?php echo $this->Form->end(); ?>
    </div>
<?php
}else{ 
    echo __('Report Feature disabled');

 } ?>

<?php echo $this->Html->script('/Reports/js/customReport', false); ?>
<script>
var maskId ;
$(document).ready(function(){
    jQuery.browser = {};
    jQuery.browser.mozilla = /mozilla/.test(navigator.userAgent.toLowerCase()) && !/webkit/.test(navigator.userAgent.toLowerCase());
    jQuery.browser.webkit = /webkit/.test(navigator.userAgent.toLowerCase());
    jQuery.browser.opera = /opera/.test(navigator.userAgent.toLowerCase());
    jQuery.browser.msie = /msie/.test(navigator.userAgent.toLowerCase());

    CustomReport.init(<?php echo $setting['maxFilesize']; ?>);
    $('.btn_save').click(CustomReport.validate.validateEdit);

    <?php if(isset($status) && count($status) > 0){ ?>
        CustomReport.displayMessage('<?php echo $status['msg'] ?>', <?php echo $status['type'] ?>);
    <?php } ?>

});
</script>