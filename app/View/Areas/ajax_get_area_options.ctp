<?php 
if(!empty($data)) {
	echo '<option value="0">' . $this->Label->get('Area.select') . '</option>';
	$html = '<option value="%s" level="%s">%s</option>';
	foreach($data as $obj) {
		echo sprintf($html, $obj[$model]['id'], $obj[$levelModel]['name'], $obj[$model]['name']);
	}
}
?>
