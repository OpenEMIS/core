<?php
$this->Html->scriptStart(['block' => 'scriptBottom']);

$timepickerScript = "var timepicker%s = $('#%s').timepicker(%s);\n";
$initializeTimepicker = '';
$timepickerEvent = '';

if (isset($timepicker)) {
    foreach ($timepicker as $key => $obj) {
        $initializeTimepicker .= sprintf($timepickerScript, $key, $obj['id'], json_encode($obj['time_options']));
        $timepickerEvent .= sprintf("timepicker%s.timepicker('place');\n", $key);
    }
}

$script = <<<EOT
$(function () {

$initializeTimepicker

$(document).on('DOMMouseScroll mousewheel scroll', function() {
    window.clearTimeout(t);
    t = window.setTimeout(function() {
        $timepickerEvent
    });
});

});
EOT;

echo $script;

$this->Html->scriptEnd();
?>
