<?php
$this->Html->scriptStart(['block' => 'scriptBottom']);

$timepickerScript = "var timepicker%s = $('#%s').timepicker(%s);\n";
$initializeTimepicker = '';
$timepickerEvent = '';

$jsOptions = [
    'format' => 'h:i A',
    'todayBtn' => 'linked',
    'orientation' => [
        'x' => 'auto',
        'y' => 'auto'
    ],
    'autoclose' => true,
];

if (isset($timepicker)) {
    foreach ($timepicker as $key => $obj) {
        $initializeTimepicker .= sprintf($timepickerScript, $key, $obj['id'], json_encode($jsOptions));
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
