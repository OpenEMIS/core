<div class="header_nav">
	<?php
	$icons = array(
		'Institutions' => 'kd-Institutions',
		'Students' => 'kd-Students',
		'Staff' => 'kd-Staff',
		'Reports' => 'kd-Reports',
		'Administration' => 'fa fa-cogs'
	);
	$navigationBar = array();
	$linkHTML = '
		<a href="%s">
			<div class="header_nav_function">
				<i class="kd %s"></i><br>
				<div class="title">%s</div>
				<div class="header_nav_function_hover %s"></div>
			</div>
		</a>';
	$divider = '<div class="header_nav_function_divi"></div>';
	foreach($_topNavigations as $title => $attr) {
		if($title !== 'Home' && isset($attr['controller'])) {
			$url = $this->webroot . $attr['controller'] . '/' . $attr['action'];
			$selected = $attr['selected'] ? 'header_nav_function_selected' : '';
			$navigationBar[] = sprintf($linkHTML, $url, $icons[$title], __($title), $selected);
		}
	}
	echo implode($divider, $navigationBar);
	?>
</div>

<?php echo $this->element('layout/product_list') ?>
