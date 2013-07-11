<?php 
echo $this->Html->css('jquery_ui', 'stylesheet', array('inline' => false));
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));
echo $this->Html->css('/Reports/css/reports', 'stylesheet', array('inline' => false));
echo $this->Html->script('jquery.ui', false);
echo $this->Html->script('app', false);
echo $this->Html->script('/Reports/js/report', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<style type="text/css">
.col_age { width: 90px; text-align: center; }
.col_name { width: 130px; }
.col_desc { width: 300px; }
.col_lastgen { width: 70px; }

.btn-add-single,
.btn-remove-single,
.btn-add-all,
.btn-remove-all{
    width: 50px;
    display: block;
    margin: 26px 15px
}

.icon_up, .icon_down{
    clear: both;
    width: 30px;
    height: 30px;
    margin-left: 5px;
}

.icon_up {
    background: url('../img/icons/up_30.png') top left no-repeat;
    margin-top:50px;
}

.icon_down {
    background: url('../img/icons/down_30.png') top left no-repeat;
    margin-top: 60px;
}

select optgroup {
    /*background-color: #EEEEEE;*/
}

.allow-fields, .button-group, .selected-fields, .button-group-order {
    float: left;
}

.allow-fields select, .selected-fields select{
    width: 230px;
    height: 200px;
}

.allow-fields h3, .selected-fields h3, .date-selector {
    color: #666666;
}
.allow-fields h3, .selected-fields h3 {
    margin: 0;
    padding: 0 0 0 5px;
}
.date-selector {
    margin-bottom: 10px;
}

.controls { margin-top: 10px; }

.clear { clear:both; }
.olap-main {
    margin: 0 auto;
    height:200px;
    width:580px;
}
</style>

			<h1>
				<span><?php echo __(ucwords("OLAP Report")); ?></span>
			</h1>
<div class="date-selector">
    <span><strong><?php echo __("School Year"); ?>: </strong></span>
    <select>
        <?php foreach($school_years as $year) { ?>
        <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
        <?php } ?>
    </select>
</div>
<div class="olap-main">
    <div class="clear"></div>
    <div class="allow-fields">
        <h3><?php echo __('Available Variables'); ?></h3>
        <select multiple>
        <?php foreach($data as $key => $value){ ?>
            <optgroup label="<?php echo __(Inflector::humanize(Inflector::underscore($key))); ?>">
            <?php foreach($value as $innerKey => $innerValue){ ?>
                <option value="<?php echo $innerKey; ?>" data-model="<?php echo $key; ?>"><?php echo __($innerValue); ?></option>
            <?php } ?>
            </optgroup>
         <?php } ?>
        </select>
    </div>

    <div class="button-group">
        <button type="button" class="btn btn-add-single" >></button>
        <button type="button" class="btn btn-remove-single" ><</button>
        <button type="button" class="btn btn-add-all" >>></button>
        <button type="button" class="btn btn-remove-all" ><<</button>
    </div>

    <div class="selected-fields">
        <h3><?php echo __('Selected Variables'); ?></h3>
        <select multiple></select>
    </div>
    <div class="button-group-order">
        <div class="icon_up" onClick="olapReport.orderOptionsUp();">&nbsp;</div>
        <div class="icon_down" onClick="olapReport.orderOptionsDown();">&nbsp;</div>
    </div>
</div>
<div class="clear" ></div>
<div class="controls">
    <button id="generate" type="button" class="btn">Generate</button>
</div>



<script>
$(document).ready(function(){
	olapReport.ProgressTpl = '<div id="prog_wrapper_{id}" >Complete <div id="prog_count_{id}" style="display:inline">0%</div> <img id="prog_img_{id}" style="vertical-align:middle" src="http://dev.openemis.org/demo/img/icons/loader.gif" ></div>'
	olapReport.init();

        setTimeout(function() {
                $('#alertError').fadeOut(2000);
        }, 3000);
   // $("#progressbar").progressbar({ value: 37 });

});
</script>
