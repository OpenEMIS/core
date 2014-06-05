<?php 
if(!empty($programmeOptions)) {
	foreach($programmeOptions as $id => $name) {
		echo sprintf('<option value="%s">%s</option>', $id, $name);
	}
} else {
	echo sprintf('<option value="">-- %s --</option>', __('No Programmes'));
}
?>