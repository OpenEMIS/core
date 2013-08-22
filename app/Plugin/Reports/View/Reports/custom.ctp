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
</style>

<?php 
$ctr = 0;
if(@$enabled === true){
?>
    <div class="content_wrapper" id="report-list">
        <h1>
            <span><?php echo __('Custom Reports'); ?></span>
        </h1>
        <fieldset class="section_group">
            <button id="add" class="btn">+ Add</button>
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

            <?php echo $this->Form->create('EditReport', array('type' => 'file')); ?>
            <div id="edit_report" style="border:1px solid #CCCCCC;margin-bottom:5px;">
                <div class="title">Edit Report</div>
                <input id="reportMode" name="data[mode]" type="hidden" value="edit"/>
                <input id="reportId" name="data[id]" type="hidden" value=""/>
                <div style="padding:0 5px;">
                    <div style="width: 15%;display:inline-block;"><label for="name">Name:<span class="required">*</span> </label></div>
                    <input id="name" name="data[name]" type="text" style="width:40%;" maxlength="40"/><br/>
                </div>
                <div style="padding:0 5px;">
                    <div style="width: 15%;display:inline-block;"><label for="description" >Description: </label></div>
                    <textarea id="description" name="data[description]" style="width:40%;" cols="40" rows"7"></textarea><br/>
                </div>
                <div style="padding:0 5px;">
                    <div style="width: 15%;display:inline-block;"><label for="file">Upload: </label></div>
                    <input type="file" name="data[doc_file]" value="" id="doc_file">
                    <!--input type="file"/-->
                </div>
                <div style="margin: 5px 0px; border-top:1px solid #CCCCCC;">
                    <button id="update" class="btn" style="float:right;">Update</button>
                    <button class="btn cancel" style="float:right;">Cancel</button>
                    <div style="clear:both;"></div>
                </div>
            </div>
            <?php echo $this->Form->end(); ?>
            <legend><?php echo __('Custom'); ?></legend>
            <div class="table">
                <div class="table_head">
                    <div class="table_cell col_name"><?php echo __('Name'); ?></div>
                    <div class="table_cell col_desc"><?php echo __('Description'); ?></div>
                    <div class="table_cell col_action"></div>
                </div>
                <div class="table_body">
                <?php if(count($data) > 0) {
                    foreach($data as $row){ ?>

                    <div id="<?php echo 'report_'.$row['id']; ?>" class="table_row" enabled="<?php echo $row['enabled']?>">
                        <div class="table_cell col_name"><?php echo $row['name']; ?></div>
                        <div class="table_cell col_desc">
                            <?php echo $row['metadata']; ?>
                        </div>
                    <div class="table_cell col_action">
                        <!--div class="btn disable">Disable</div-->
                        <div class="btn edit">Edit</div>
                        <!--div class="btn download" url="<?php echo $this->Html->url("Reports/CustomDownload/{$row['filename']}", true); ?>">Download</div-->
                        <div class="btn download" url="<?php echo $this->Html->url("/Reports/CustomDownload/{$row['id']}", true); ?>">Download</div>
                    </div>
                    </div>
                <?php } ?>
                    <!--div class="table_row more">
                        <div class="col_full">More</div>
                    </div-->
                <?php }else{ ?>
                    <div class="table_row">
                        <div class="col_full">No Custom Report</div>
                    </div>

                <?php } ?>
                </div>
            </div>
            <div style="width:100%;margin:15px 5px;"><?php echo __('Please contact'); ?> <a href="<?php echo $this->Html->url(array('plugin' => null,'controller'=>'Home','action'=>'support'))?>"> <?php echo __('support'); ?> </a> <?php echo __('for more information on Custom Reports.'); ?></div>
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
    $("#add_reports, #edit_report").hide();

    $(".download").click(function(){
        console.info($(this).attr('url'));
        window.location = $(this).attr('url');
    });

    <?php if(isset($status) && count($status) > 0){ ?>
        CustomReport.displayMessage('<?php echo $status['msg'] ?>', <?php echo $status['type'] ?>);
    <?php } ?>

    //$('input[type="file"]').change(CustomReport.validate.validFile);

    //$('input[type="text"]#name').blur(CustomReport.validate.validName);

});
</script>