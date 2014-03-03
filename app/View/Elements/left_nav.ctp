<div class="left_nav accordion" id="accordion">
	<?php
	$index = 0;
	$group = '
	<div class="accordion-group">
		<div class="accordion-heading">
		  <a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion" href="#%s">
			<p>%s</p>
		  </a>
		</div>
		<div id="%s" class="accordion-body collapse %%s">
		  <div class="accordion-inner">%%s</div>
		</div>
	</div>';
	
	$item = '
	<a href="%s">
		<div class="left_nav_li %s">
			<div class="left_nav_li_icon">%s</div>
			<div class="left_nav_li_content">%s</div>
		</div>
	</a>';
	
	$html = '';
	$groupHtml = '';
	$itemHtml = '';
	$in = false;
	foreach($_leftNavigations as $module => $obj) {
		if(!isset($obj['display'])) continue;
		if(is_string($module)) {
			$id = 'id_' . $index++;
			$groupHtml = sprintf($group, $id, __($module), $id);
		}
		$itemHtml = '';
		foreach($obj as $link) {
			if(is_array($link)) {
				if(!$link['display']) continue;
				$controller = $link['controller'];
				$params = (isset($link['params']) ? '/'.$link['params'] : '') . (strlen($_params)>0 ? '/'.$_params : '');
				$url = sprintf('%s%s/%s', $this->webroot, $controller , $link['action']) . $params;
				$selected = $link['selected'] ? 'left_nav_li_selected' : '';
				if(!$in && $link['selected']) {
					$in = true;
				}
				$tmpurl = $url;
				$parsedURL = Router::parse($tmpurl);
				if($this->webroot != "/"){
					$parsedURL['controller'] = $parsedURL['action'];
					$parsedURL['action'] = ((count($parsedURL['pass']) > 0)?$parsedURL['pass'][0]:'index');
				}
				$icon = $this->Html->image('nav_icons/'.$parsedURL['controller'].'/'.$parsedURL['action'].".png");
				$itemHtml .= sprintf($item, $url, $selected, $icon, __($link['title']));
			}
		}
		if(strlen($groupHtml) > 0) {
			$html .= sprintf($groupHtml, ($in ? 'in' : ''), $itemHtml);
			$in = false;
		} else {
			$html .= $itemHtml;
		}
	}
	echo $html;
	?>
</div>