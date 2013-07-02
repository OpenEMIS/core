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

.controls { margin-top: 10px; }

.clear { clear:both; }

.custom_div{
    margin-bottom: 10px;
}

.custom_div .custom_div_label{
    width:130px;
    margin-right: 10px;
    margin-left: 20px;
    float:left;
}

.custom_div#indicator_selector .custom_div_content select{
    /*min-width:360px;*/
    overflow-wrap:initial;
}

.custom_box {
    overflow: scroll;
    overflow-x: hidden;
    margin: 0 15px 15px 10px;
    border: 1px solid #CCCCCC;
    min-height: 50px;
    max-height: 150px;
    min-width: 100px;
    max-width: 250px;
    /*max-width: 360px;*/
}

.custom_box .custom_label {
    font-size: 11px;
    font-weight: bold;
    padding: 5px 10px;
    border-bottom: 1px solid #CCCCCC;
    border-top: 1px solid #CCCCCC;
    background-color: #EFEFEF;
    color: #666666;
    overflow: hidden;
}

.custom_box .custom_label:first-child {
    border-top: none;
}

.custom_box .custom_value {
    overflow: hidden;
    padding: 5px 10px;
    font-size: 11px;
    color: #666666;
}
.custom_box .custom_value ul{
    list-style-type: none;
    padding:0px;
    margin: 0px;
}
</style>
<div id="report-list" class="content_wrapper">
			<h1>
				<span><?php echo __(ucwords("Indicator Reports")); ?></span>
			</h1>
<form id="sdmx-download" method="post" action="<?php echo $this->Html->url(array( "controller" => "Reports", "action" => "downloadIndicator"));?>" >
    <div id="indicator_selector" class="custom_div">
        <div class="custom_div_label"><?php echo __("Indicator"); ?></div>
        <div class="custom_div_content">
            <select name="data[Sdmx][indicator]" class="default">
            <php? //pr($indicators); ?>
                <option value="" >-- Select one --</option>
                <?php foreach($indicators as $indicator) { ?>
                <option value="<?php echo $indicator['Indicator_Nid']; ?>" <?php echo ($indicator['Indicator_Nid'] == $selectedIndicator)? "selected":""; ?> ><?php echo $indicator['Indicator_Name']; ?></option>
                <?php } ?>
            </select>
        </div>

    </div>
    <div id="format_selector" class="custom_div">
        <div class="custom_div_label"><?php echo __("Format"); ?></div>
        <div class="custom_div_content">
        <select name="data[Sdmx][export]" class="default">
            <option value="csv">CSV</option>
            <option value="sdmx">SDMX</option>
        </select>
        </div>
    </div>
    <div id="sdmx-areas" class="custom_div">
        <div class="custom_div_label"><?php echo __("Areas"); ?></div>
        <div class="custom_div_content">
            <div class="custom_box">
                <?php foreach($areas as $key=>$tmpAreas){ ?>
                    <div class="custom_label"><?php echo __(Inflector::humanize(Inflector::underscore($key))); ?></div>

                    <div class="custom_value">
                    <ul>
                    <?php foreach($tmpAreas as $area){
                        $cssId = str_replace(' ', '_', trim($area['Area_Name'])) . '_' . $area['Area_NId'];
                    ?>
                        <li>
                            <input id="<?php echo $cssId?>" class='areas_input' type="checkbox" name="data[Sdmx][areas][]" value="<?php echo $area['Area_NId']; ?>"/ > <label for="<?php echo $cssId?>"><?php echo trim($area['Area_Name']); ?></label>
                        </li>
                    <?php } ?>
                    </ul>
                    </div>
                 <?php } ?>
            </div>
        </div>
    </div>
    <div id="sdmx-timeperiods" class="custom_div">
        <div class="custom_div_label"><?php echo __("Time Periods"); ?></div>
        <div class="custom_div_content">
            <div class="custom_box">
                <div class="custom_value">
                <ul>
                <?php foreach($timeperiods as $timeperiod){
                    $cssId = str_replace(' ', '_', trim($timeperiod['TimePeriod'])). '_' .$timeperiod['TimePeriod_NId'];
                ?>
                    <li>
                        <input id="<?php echo $cssId?>" class="timeperiods_input" type="checkbox" name="data[Sdmx][timeperiods][]" value="<?php echo $timeperiod['TimePeriod_NId']; ?>"/> <label for="<?php echo $cssId?>"><?php echo trim($timeperiod['TimePeriod']); ?></label>
                    </li>
                <?php } ?>
                </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="clear" ></div>
    <div class="controls">
        <button id="generate" type="button" class="btn  btn_disabled" disabled>Export</button>
    </div>
</form>
</div>



<script>
$(document).ready(function(){

    $('#indicator_selector select').change(function(){
       window.location = "<?php echo $this->Html->url(array(
                                      "controller" => "Reports",
                                      "action" => "Indicator"
                                  ));?>/"+ $(this).find("option:selected").val();
    });

    $('.areas_input, .timeperiods_input').change(function(){
        var totalAreas = $('.areas_input[type="checkbox"]:checked').length;
        var totalTimeperiod = $('.timeperiods_input[type="checkbox"]:checked').length;
        if(totalAreas > 0 && totalTimeperiod > 0){
            $('#generate').removeClass('btn_disabled');
            $('#generate').removeProp("disabled");
        }else if(totalAreas == 0 || totalTimeperiod == 0){
            $('#generate').addClass('btn_disabled');
            $('#generate').prop("disabled", "disabled");
        }
    });

    $('#generate').click(function(){
        maxAreaSelected = 5;

        areasCount = $('.areas_input[type="checkbox"]:checked').length;
        timeperiodsCount = $('.timeperiods_input[type="checkbox"]:checked').length;
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
