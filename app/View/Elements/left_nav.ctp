<div class="left_nav">
	<?php
	$html = '
	<a href="%s">
		<div class="left_nav_li %s">
			<div class="left_nav_li_icon">%s</div>
			<div class="left_nav_li_content">%s</div>
		</div>
	</a>';
	
	foreach($_leftNavigations as $module => $obj) {
		if(!isset($obj['display'])) continue;
		if(is_string($module)) {
			echo sprintf('<p>%s</p>', __($module));
		}
		foreach($obj as $link) {
			if(is_array($link)) {
				if(!$link['display']) continue;
				$controller = $link['controller'];
				$params = (isset($link['params']) ? '/'.$link['params'] : '') . (strlen($_params)>0 ? '/'.$_params : '');
				$url = sprintf('%s%s/%s', $this->webroot, $controller , $link['action']) . $params;
				$selected = $link['selected'] ? 'left_nav_li_selected' : '';
				$tmpurl = $url;
				$parsedURL = Router::parse($tmpurl);
				if($this->webroot != "/"){
					$parsedURL['controller'] = $parsedURL['action'];
					$parsedURL['action'] = ((count($parsedURL['pass']) > 0)?$parsedURL['pass'][0]:'index');
				}
				$icon = $this->Html->image('nav_icons/'.$parsedURL['controller'].'/'.$parsedURL['action'].".png");
				echo sprintf($html, $url, $selected, $icon, __($link['title']));
			}
		}
	}
	?>
</div>