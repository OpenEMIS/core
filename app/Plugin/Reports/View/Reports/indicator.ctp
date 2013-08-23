<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));
echo $this->Html->css('/Reports/css/reports', 'stylesheet', array('inline' => false));
echo $this->Html->script('app', false);
// echo $this->Html->script('/Reports/js/report', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="report-list" class="content_wrapper">
			<h1>
				<span><?php echo __(ucwords("Indicator Reports")); ?></span>
			</h1>
<form id="sdmx-download" method="post" action="<?php echo $this->Html->url(array( "controller" => "Reports", "action" => "downloadIndicator"));?>" >
    <div id="indicator_selector" class="row">
        <div class="label"><?php echo __("Indicator"); ?></div>
        <div class="value">
            <select name="data[Sdmx][indicator]" class="default">
            <php? //pr($indicators); ?>
                <option value="" >-- Select one --</option>
                <?php foreach($indicators as $indicator) { ?>
                <option value="<?php echo $indicator['Indicator_Nid']; ?>" <?php echo ($indicator['Indicator_Nid'] == $selectedIndicator)? "selected":""; ?> ><?php echo $indicator['Indicator_Name']; ?></option>
                <?php } ?>
            </select>
        </div>

    </div>
    <div id="format_selector" class="row">
        <div class="label"><?php echo __("Format"); ?></div>
        <div class="value">
        <select name="data[Sdmx][format]" class="default">
            <?php
            $optionElementTemplate = '<option value="{{ value }}" {{ selected }}>{{ text }}</option>';
            foreach($formats as $format){
                $optionElement = str_ireplace('{{ value }}', $format['value'], $optionElementTemplate);
                $optionElement = str_ireplace('{{ text }}', strtoupper($format['value']), $optionElement);
                $optionElement = str_ireplace('{{ selected }}', ($format['selected'])?'selected':'', $optionElement);
                echo $optionElement;
            }
            ?>
        </select>
        </div>
    </div>
    <div id="sdmx-areas" class="row">
        <div class="label"><?php echo __("Areas"); ?></div>
        <div class="value">
            <div class="custom_box">
                <?php foreach($areas as $key=>$tmpAreas){ ?>
                    <div class="custom_label"><?php echo __(Inflector::humanize(Inflector::underscore($key))); ?></div>

                    <div class="custom_value">
                    <ul>
                    <?php foreach($tmpAreas as $area){
                        $cssId = str_replace(' ', '_', trim($area['Area_Name'])) . '_' . $area['Area_NId'];
                    ?>
                        <li>
                            <input id="<?php echo $cssId?>" class='areas_input' type="checkbox" name="data[Sdmx][areas][]" value="<?php echo $area['Area_NId']; ?>" <?php echo (!in_array($area['Area_NId'], $selectedAreas))?:'checked'; ?>/> <label for="<?php echo $cssId?>"><?php echo trim($area['Area_Name']); ?></label>
                        </li>
                    <?php } ?>
                    </ul>
                    </div>
                 <?php } ?>
            </div>
        </div>
    </div>
    <div id="sdmx-timeperiods" class="row">
        <div class="label"><?php echo __("Time Periods"); ?></div>
        <div class="value">
            <div class="custom_box">
                <div class="custom_value">
                <ul>
                <?php foreach($timeperiods as $timeperiod){
                    $cssId = str_replace(' ', '_', trim($timeperiod['TimePeriod'])). '_' .$timeperiod['TimePeriod_NId'];
                ?>
                    <li>
                        <input id="<?php echo $cssId?>" class="timeperiods_input" type="checkbox" name="data[Sdmx][timeperiods][]" value="<?php echo $timeperiod['TimePeriod_NId']; ?>" <?php echo (!in_array($timeperiod['TimePeriod_NId'], $selectedTimeperiods))?:'checked'; ?>/> <label for="<?php echo $cssId?>"><?php echo trim($timeperiod['TimePeriod']); ?></label>
                    </li>
                <?php } ?>
                </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="clear" ></div>
    <div class="controls view_controls">
        <button id="generate" type="button" class="btn  btn_disabled" disabled>Export</button>
    </div>
</form>
</div>



<script>
$(document).ready(function(){


    var alertOpt = {
        // id: 'alert-' + new Date().getTime(),
        parent: 'body',
        title: i18n.General.textDismiss,
        text: '<div style=\"text-align:center;\">' + 'test' +'</div>',
        type: alertType.warn, // alertType.info or alertType.warn or alertType.error
        position: 'top',
        css: {}, // positioning of your alert, or other css property like width, eg. {top: '-10px', left: '-20px'}
        autoFadeOut: true
    };

    <?php if(isset($alert)){ ?>
        alertOpt.type = alertType.error;
        alertOpt.text = '<?php echo nl2br(__($alert)); ?>';
        $.alert(alertOpt);
    <?php } ?>

    $('#indicator_selector select').change(function(){
       window.location = "<?php echo $this->Html->url(array(
                                      "controller" => "Reports",
                                      "action" => "Indicator"
                                  ));?>/"+ $(this).find("option:selected").val();
    });

    isCheckboxChecked();

    $('.areas_input, .timeperiods_input').change(isCheckboxChecked);

    $('#generate').click(function(){
        maxAreaSelected = 5;

        areasCount = $('.areas_input[type="checkbox"]:checked').length;
        timeperiodsCount = $('.timeperiods_input[type="checkbox"]:checked').length;
        if(areasCount < 1){
            // alert('<?php echo __("Please select an Area."); ?>');
            alertOpt.text = '<?php echo __("Please select an Area."); ?>';
            $.alert(alertOpt);
            return false;
        }else if(timeperiodsCount < 1){
            alert('<?php echo __("Please select a Timeperiod."); ?>');
            alertOpt.text = '<?php echo __("Please select a Timeperiod."); ?>';
            $.alert(alertOpt);
            return false;
        }else if(areasCount > maxAreaSelected){
            //alert('<?php echo __("You have selected maximum of Areas allowed."); ?>');
            alertOpt.text = '<?php echo __("You have selected maximum of Areas allowed."); ?>';
            $.alert(alertOpt);
            return false;
        }else{
            $('#sdmx-download').submit();
        }
    });

    function isCheckboxChecked(){
        var totalAreas = $('.areas_input[type="checkbox"]:checked').length;
        var totalTimeperiod = $('.timeperiods_input[type="checkbox"]:checked').length;
        if(totalAreas > 0 && totalTimeperiod > 0){
            $('#generate').removeClass('btn_disabled');
            $('#generate').removeProp("disabled");
        }else if(totalAreas == 0 || totalTimeperiod == 0){
            $('#generate').addClass('btn_disabled');
            $('#generate').prop("disabled", "disabled");
        }
    }
});
</script>
