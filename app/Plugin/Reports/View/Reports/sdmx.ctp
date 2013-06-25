<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));
echo $this->Html->css('/Reports/css/reports', 'stylesheet', array('inline' => false));
echo $this->Html->script('app', false);
// echo $this->Html->script('/Reports/js/report', false);
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
    float: left;padding-right: 30px;
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
				<span><?php echo __(ucwords("SDMX Report")); ?></span>
			</h1>
<form id="sdmx-download" method="post" action="<?php echo $this->Html->url(array( "controller" => "Reports", "action" => "downloadSDMX"));?>" >
    <div class="date-selector">
        <span><strong><?php echo __("Indicators"); ?>: </strong></span>
        <select name="data[Sdmx][indicator]">
        <php? //pr($indicators); ?>
            <option value="" >-- Select one --</option>
            <?php foreach($indicators as $indicator) { ?>
            <option value="<?php echo $indicator['Indicator_Nid']; ?>" <?php echo ($indicator['Indicator_Nid'] == $selectedIndicator)? "selected":""; ?> ><?php echo $indicator['Indicator_Name']; ?></option>
            <?php } ?>
        </select>
    </div>
    <div class="olap-main">
        <div class="clear"></div>
        <div id="sdmx-areas" class="allow-fields">
            <h3><?php echo __('Areas'); ?></h3>
            <select name="data[Sdmx][areas][]" multiple>
            <?php foreach($areas as $key=>$tmpAreas){ ?>
            <optgroup label="<?php echo __(Inflector::humanize(Inflector::underscore($key))); ?>">
            <?php foreach($tmpAreas as $area){ ?>
                <option value="<?php echo $area['Area_NId']; ?>"><?php echo trim($area['Area_Name']); ?></option>
             <?php }
             }
             ?>
            </optgroup>
            </select>
        </div>

        <div id="sdmx-timeperiods" class="selected-fields">
            <h3><?php echo __('Timeperiods'); ?></h3>
            <select name="data[Sdmx][timeperiods][]" multiple>
            <?php foreach($timeperiods as $timeperiod){ ?>
                <option value="<?php echo $timeperiod['TimePeriod_NId']; ?>"><?php echo trim($timeperiod['TimePeriod']); ?></option>
             <?php } ?>
            </select>
        </div>
    </div>
    <div class="clear" ></div>
    <div class="controls">
        <button id="generate" type="button" class="btn  btn_disabled" disabled>Generate</button>
    </div>
</form>



<script>
$(document).ready(function(){

    $('.date-selector select').change(function(){
       window.location = "<?php echo $this->Html->url(array(
                                      "controller" => "Reports",
                                      "action" => "Sdmx"
                                  ));?>/"+ $(this).find("option:selected").val();
    });

    $('#sdmx-areas, #sdmx-timeperiods').change(function(){
        $('#generate').removeClass('btn_disabled')
        $('#generate').removeProp("disabled");
    });

    $('#generate').click(function(){
        maxAreaSelected = 5;
        areasCount = $('#sdmx-areas select option:selected').length
        timeperiodsCount = $('#sdmx-timeperiods select option:selected').length;
        if(areasCount < 1){
            alert('<?php echo __("Please select an Area."); ?>');
            return false;
        }else if(timeperiodsCount < 1){
            alert('<?php echo __("Please select a Timeperiod."); ?>');
            return false;
        }else if(areasCount > maxAreaSelected){
            alert('<?php echo __("You have selected maximum of Areas allowed."); ?>');
            return false;
        }else{
            $('#sdmx-download').submit();
        }
    });
});
</script>
