<?php 
echo $this->Html->css('jquery-ui.min', 'stylesheet', array('inline' => false));
echo $this->Html->script('jquery-ui.min', array('inline' => false));
echo $this->Html->script('app.autocomplete', array('inline' => false));

$inputDefaults = $this->Form->inputDefaults();
$inputOptions = array(
	'class' => 'form-control autocomplete', 
	'url' => $url
);

if (isset($labelOptions)) {
	$inputOptions['label'] = $labelOptions;
}

if (isset($placeholder)) {
	$inputOptions['placeholder'] = $placeholder;
}

$inputOptions['length'] = 3;

$loadingImg = $this->Html->image('icons/loader.gif', array());
$noDataMsg = $this->Label->get('Autocomplete.no_result');
$loadingHtml = '<div class="loadingWrapper"><span class="img">' . $loadingImg . '</span><span class="msg">' . $noDataMsg . '</span> ';
if(isset($linkWhenNoRecords)){
	$loadingHtml .= $linkWhenNoRecords;
}
$loadingHtml .= '</div>';

$after = $inputDefaults['after'] . $loadingHtml;
$inputOptions['after'] = $after;

echo $this->Form->input('search', $inputOptions);
?>
