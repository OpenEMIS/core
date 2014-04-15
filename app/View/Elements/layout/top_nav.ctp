<div class="header_nav">
	<?php
	$navigationBar = array();
	$linkHTML = '<a href="%s"><div class="header_nav_function">%s<div class="header_nav_function_hover %s"></div></div></a>';
	$divider = '<div class="header_nav_function_divi"></div>';
	foreach($_topNavigations as $title => $attr) {
		if($title !== 'Home') {
			$url = $this->webroot . $attr['controller'] . '/' . $attr['action'];
			$selected = $attr['selected'] ? 'header_nav_function_selected' : '';
			$navigationBar[] = sprintf($linkHTML, $url, __($title), $selected);
		}
	}
	echo implode($divider, $navigationBar);
	?>
</div>