<?php 
if(!empty($data)) {
	if($model == 'AreaAdministrative' && $parentId != $worldId) {
		echo '<option value='.$parentId.'>' . $this->Label->get('Area.select') . '</option>';
	}
	$html = '<option value="%s" level="%s">%s</option>';
	foreach($data as $obj) {
		echo sprintf($html, $obj[$model]['id'], $obj[$levelModel]['name'], $obj[$model]['name']);
	}
}
?>
