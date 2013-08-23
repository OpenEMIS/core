<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('/Reports/css/reports', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<style type="text/css">
.title {font-size:11px;background-color:#EFEFEF;padding: 5px;margin-bottom:5px;color:#666666;font-weight:bold;border-bottom: solid 1px #CCCCCC;}
.col_age { width: 90px; text-align: center; }
.col_name { width: 130px; }
.col_full {
    display: table-caption;
    width: 663px;
    text-align: center;
    padding: 10px 0;
    border: solid 1px #CCCCCC;
    border-top: none;
    background-color: #FFFFFF;
}
.required { color: red;}

div.more .col_full{cursor: pointer;}
div.more .col_full:hover{background-color: #5b8baf; color:#FFFFFF;}

.col_desc { width: 300px; }
.col_lastgen { width: 70px; }
.col_action { width: 100px; }
.disable, .edit {
    margin: 0 0 5px 0;
}
button#add {
    margin: 0 0 10px 0;
}





.file_input {
	position: relative;
	height: 60px;
	overflow: hidden;
}

.file_input input[type="file"] {
	position: relative;
	-moz-opacity: 0 ;
	filter: alpha(opacity: 0);
	opacity: 0;
	z-index: 2;
}

.file_input .file {
	position: absolute;
	top: 0px;
	left: 0px;
	width: 100%;
	z-index: 1;
}

.file_input .btn { margin-top: 5px; }
.file_input .clear_btn { margin-left: 5px; }
</style>

<?php 
$ctr = 0;
//if(@$enabled === true){
if(true){
?>
    <div class="content_wrapper" id="report-list">
        <span id="controller" class="none"><?php echo $controllerName; ?></span>
      <h1>
          <span><?php echo __('Custom Reports'); ?></span>
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
    $("#add_reports, #edit_report").hide();

    $(".download").click(function(){
        console.info($(this).attr('url'));
        window.location = $(this).attr('url');
    });
    $('.btn_save').click(CustomReport.validate.validateEdit);

    <?php if(isset($status) && count($status) > 0){ ?>
        CustomReport.displayMessage('<?php echo $status['msg'] ?>', <?php echo $status['type'] ?>);
    <?php } ?>

    //$('input[type="file"]').change(CustomReport.validate.validFile);

    //$('input[type="text"]#name').blur(CustomReport.validate.validName);

});
</script>