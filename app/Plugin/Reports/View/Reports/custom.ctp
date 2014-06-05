<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('/Reports/css/reports', 'stylesheet', array('inline' => false));
?>
<style type="text/css">
.col_action > a { margin-right: 7px;}
.col_action > img { margin-left:0; margin-right: 5px;}
</style>

<?php echo $this->element('breadcrumb'); ?>

<?php 
$ctr = 0;
if(@$enabled === true){
?>
    <div class="content_wrapper" id="report-list">
        <h1>
            <span><?php echo __('Custom Indicators'); ?></span>
            <?php
                if($_edit) {
                    echo $this->Html->link(__('Add'), array('action' => 'CustomAdd'),	array('class' => 'divider'));
                }
            ?>
        </h1>
            <span id="controller" class="none"><?php echo $controllerName; ?></span>
            <div class="table full_width">
                <div class="table_head">
                    <div class="table_cell col_name"><?php echo __('Name'); ?></div>
                    <div class="table_cell col_desc"><?php echo __('Description'); ?></div>
                    <div class="table_cell col_action" style="width:70px;"><?php echo __('Action'); ?></div>
                </div>
                <div class="table_body">
                <?php if(count($data) > 0) {
                    foreach($data as $row){ ?>

                    <div id="<?php echo 'report_'.$row['id']; ?>" class="table_row" enabled="<?php echo $row['enabled']?>" row-id="<?php echo $row['id']?>">
                        <div class="table_cell col_name">
                            <?php echo $row['name']; ?>
                        </div>
                        <div class="table_cell col_desc">
                            <?php echo $row['metadata']; ?>
                        </div>
                        <div class="table_cell col_action">
                            <?php //echo $this->Html->image('icons/edit.png', array("alt" => "Edit", "class" => "edit", "row-id" =>$row['id'])); ?>
                            <?php
                            echo $this->Html->link(
                                $this->Html->image('icons/edit.png', array("alt" => "Edit")),
                                "/{$controllerName}/CustomEdit/{$row['id']}",
                                array('escape' => false)
                            );
                            echo $this->Html->link(
                                $this->Html->image('icons/download.png', array("alt" => "Download")),
                                "/{$controllerName}/CustomDownload/{$row['id']}",
                                array('escape' => false)
                            );
                            echo $this->Html->link(
                                $this->Html->image('icons/delete.png', array("alt" => "Delete", "class" => "delete_icon", "row-id" =>$row['id'])),
                                "#",
                                array('escape' => false)
                            );
                            ?>
                            <?php //echo $this->Html->image('icons/delete.png', array("alt" => "Delete", "class" => "delete_icon", "row-id" =>$row['id']));?>
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
            <div style="width:100%;margin:15px 5px;"><?php echo __('Please contact'); ?> <a href="<?php echo $this->Html->url(array('plugin' => null,'controller'=>'Home','action'=>'support'))?>"> <?php echo __('support'); ?> </a> <?php echo __('for more information on Custom Indicators.'); ?></div>
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

    $('.edit_icon').click(function(){
        var rowId = $(this).attr('row-id');
        window.location = getRootURL() + $('#controller').text() + '/CustomEdit/'+rowId;
    });

    $('a .delete_icon').click(function(e){
        e.preventDefault();
        var rowId = $(this).attr('row-id');
        CustomReport.deleteFile(rowId);
    });

    <?php if(isset($status) && count($status) > 0){ ?>
        CustomReport.displayMessage('<?php echo $status['msg'] ?>', <?php echo $status['type'] ?>);
    <?php } ?>

    //$('input[type="file"]').change(CustomReport.validate.validFile);

    //$('input[type="text"]#name').blur(CustomReport.validate.validName);

});
</script>