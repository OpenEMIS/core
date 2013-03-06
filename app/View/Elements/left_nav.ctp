<div class="left_nav">
	<?php
	foreach($_navigations as $module => $obj) {
		if(!$obj['display']) continue;
		
		foreach($obj['links'] as $links) {
			if(!$links['_display']) continue;
			
			foreach($links as $title => $linkList) {
				if($title === '_display') continue;
				if(is_string($title) && $linkList['_display']) {
					echo '<p>' . __($title) . '</p>';
				}
				foreach($linkList as $key => $link) {
					if(!$link['display'] || $key === '_controller' || $key === '_display') continue;
					$title = $link['title'];
					$controller = isset($link['controller']) ? $link['controller'] : $linkList['_controller'];
					$params = (isset($link['params']) ? '/'.$link['params'] : '') . (strlen($_params)>0 ? '/'.$_params : '');
					$url = sprintf('%s%s/%s', $this->webroot, $controller , $link['action']) . $params;
					$selected = $link['selected'] ? 'left_nav_li_selected' : '';
					$tmpurl = $url;
					$parsedURL = Router::parse($tmpurl);
					if($this->webroot != "/"){
						$parsedURL['controller'] = $parsedURL['action'];
						$parsedURL['action'] = ((count($parsedURL['pass']) > 0)?$parsedURL['pass'][0]:'index');
					}
					//$img = str_replace(' ','_',$img);
					echo '<a href="' . $url . '"><div class="left_nav_li ' . $selected . '">';
					echo '<div class="left_nav_li_icon">' . $this->Html->image('nav_icons/'.$parsedURL['controller'].'/'.$parsedURL['action'].".png") . '</div>';
					echo '<div class="left_nav_li_content">' . __($title) . '</div>';
					echo '</div></a>';
				}
			}
			break;
		}
	}
	?>
</div>