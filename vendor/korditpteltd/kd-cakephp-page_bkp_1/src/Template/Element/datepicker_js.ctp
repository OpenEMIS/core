<?php
$this->Html->scriptStart(['block' => 'scriptBottom']);

$datepickerScript = "var datepicker%s = $('#%s').datepicker(%s);\n";
$initializeDatepicker = '';
$datepickerEvent = '';

$jsOptions = [
    'format' => 'dd-mm-yyyy',
    'todayBtn' => 'linked',
    'orientation' => 'auto',
    'autoclose' => true,
];

if (isset($datepicker)) {
    foreach ($datepicker as $key => $obj) {
        $jsOptions = array_merge($jsOptions, $obj['date_options']);
        $initializeDatepicker .= sprintf($datepickerScript, $key, $obj['id'], json_encode($jsOptions));
        $datepickerEvent .= sprintf("datepicker%s.datepicker('place');\n", $key);
    }
}

$script = <<<EOT
$(function () {

$initializeDatepicker

$(document).on('DOMMouseScroll mousewheel scroll', function() {
    window.clearTimeout(t);
    t = window.setTimeout(function() {
        $datepickerEvent
    });
});

});
EOT;

echo $script;

$this->Html->scriptEnd();
?>
