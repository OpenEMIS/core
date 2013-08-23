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
        <span id="mode" class="none">add</span>
        <h1>
            <span><?php echo __('Custom Reports'); ?></span>
            <?php echo $this->Html->link(__('View'), array('action' => 'custom'), array('class' => 'divider')); ?>
        </h1>
        <?php echo $this->Form->create('Report', array('type' => 'file')); ?>
        <!--fieldset class="section_break"-->
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
        <!--/fieldset-->
        <div class="controls view_controls">
        		<input type="submit" value="Save" class="btn_save btn_right">
        		<a href="/demo_eugene/Reports/Custom" class="btn_cancel btn_left">Cancel</a>	</div>
        <?php echo $this->Form->end(); ?>

        <fieldset class="section_group none">
            <?php echo $this->Form->create('Report', array('type' => 'file')); ?>
            <div id="add_reports" style="border:1px solid #CCCCCC;margin-bottom:5px;">
                <div class="title">Add New Report</div>
                <input id="reportMode" name="data[mode]" type="hidden" value="add"/>
                <div style="padding:0 5px;">
                    <div style="width: 15%;display:inline-block;"><label for="name">Name:<span class="required">*</span></label></div>
                    <input id="name" name="data[name]" type="text" style="width:40%;" maxlength="40"/><br/>
                </div>
                <div style="padding:0 5px;">
                    <div style="width: 15%;display:inline-block;"><label for="description" >Description: </label></div>
                    <textarea id="description" name="data[description]" style="width:40%;" cols="40" rows"7"></textarea><br/>
                </div>
                <div style="padding:0 5px;">
                    <div style="width: 15%;display:inline-block;"><label for="file">Upload:<span class="required">*</span> </label></div>
                    <input type="file" name="data[doc_file]" value="" id="doc_file">
                    <br/>
                    <!--input type="file"/-->
                </div>

                <p style="color:#666666;padding:0 0 0 5px;"><em><span class="required">*</span> required.</em></p>
                <div style="margin: 5px 0px; border-top:1px solid #CCCCCC;">
                    <!--submit id="save" class="btn" style="float:right;">Save</submit-->
                    <button id="save" class="btn" style="float:right;">Save</button>
                    <button class="btn cancel" style="float:right;">Cancel</button>
                    <div style=" clear:both;"></div>
                </div>
            </div>
            <?php echo $this->Form->end(); ?>
            <legend><?php echo __('Custom'); ?></legend>
            </fieldset>
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

    $('.btn_save').click(CustomReport.validate.validateSave);

    <?php if(isset($status) && count($status) > 0){ ?>
        CustomReport.displayMessage('<?php echo $status['msg'] ?>', <?php echo $status['type'] ?>);
    <?php } ?>

});
</script>