<?php
$types = [
    'success' => ['class' => 'alert-success'],
    'error' => ['class' => 'alert-danger'],
    'warning' => ['class' => 'alert-warning'],
    'info' => ['class' => 'alert-info']
];

if (isset($alert)) {
	foreach ($alert as $item) {
	    $html = <<<EOT
<div class="alert %s">
    <a class="close" aria-hidden="true" href="#" data-dismiss="alert">&times;</a>
    %s
</div>
EOT;

	    echo sprintf($html, $types[$item['type']]['class'], $item['message']);
    }
}
?>
