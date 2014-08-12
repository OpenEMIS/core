
<?php 
if(!empty($gradeOptions)) {
	foreach($gradeOptions as $id => $name) {
		echo sprintf('<option value="%s">%s</option>', $id, $name);
	}
} else {
	echo sprintf('<option value="">-- %s --</option>', __('No Grades'));
}
?>