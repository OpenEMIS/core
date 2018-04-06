<?php
$this->Html->script('angular/map/map.ctrl', ['block' => true]); 

$this->extend('OpenEmis./Layout/Panel');

$this->start('panelBody');
?>

	<kdx-map id="map-group-cluster" [config]="MapController.mapConfig" [position]="MapController.mapPosition" [data]="MapController.mapData"></kdx-map>

<?php
	unset($centerLat);
	unset($centerLng);
	unset($defaultZoom);
	unset($institutionTypes);
	unset($iconColors);
	unset($colorCount);
	unset($key);
	unset($type);
	$jsonOptions = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
	$json = json_encode($institutionByType, $jsonOptions);
	echo '<script>';
	echo '	var institutionsData = ';print_r($json);
	echo '</script>';

$this->end();
?>

