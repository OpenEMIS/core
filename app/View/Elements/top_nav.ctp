<div class="header_nav">
	<?php
	$linkHTML = '<a href="%s"><div class="header_nav_function">%s<div class="header_nav_function_hover %s"></div></div></a>';
	$divider = '<div class="header_nav_function_divi"></div>';
	
	$navigations = array();
	foreach($_navigations as $key => $attr) {
		if($attr['display']) $navigations[$key] = $attr;
	}
	$navigationBar = array();
	foreach($navigations as $title => $attr) {
		if($attr['display'] && ucfirst($attr['controller']) !== 'Home') {
			$url = $this->webroot . $attr['controller'] . '/';
			$selected = $attr['selected'] ? 'header_nav_function_selected' : '';
			if($_accessControl->check($attr['controller'], 'index')) {
				$navigationBar[] = sprintf($linkHTML, $url, __($title), $selected);
			}
		}
	}
	echo implode($divider, $navigationBar);
	?>
</div>