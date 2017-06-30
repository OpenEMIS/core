<?php
$href = $this->Page->getUrl($url, true);

$_linkOptions = [
    'class' => 'btn btn-xs btn-default',
    'title' => '',
    'data-toggle' => 'tooltip',
    'data-placement' => 'bottom',
    'escape' => false
];

if (isset($linkOptions)) {
    $_linkOptions = array_merge($_linkOptions, $linkOptions);
}
$_linkOptions['data-original-title'] = $_linkOptions['title'];

echo $this->Html->link('<i class="' . $iconClass . '"></i>', $href, $_linkOptions);
?>
