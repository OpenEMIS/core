<div class="left_nav panel-group" id="accordion">
	<?php
	$index = 0;
	$group = '
	<div class="panel panel-default">
		<div class="panel-heading">
			<h4 class="panel-title">
				<a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion" href="#%s">
				<p>%s</p>
			</h4>
		  </a>
		</div>
		<div id="%s" class="panel-collapse collapse %%s">
		  <div class="panel-body">%%s</div>
		</div>
	</div>';
	
	$item = '
	<a href="%s" %s>
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
				
				// hide the add existing student/staff if institution site id is not set
				if (($link['controller'] == 'Students' && $link['action'] == 'InstitutionSiteStudent/add')
				|| ($link['controller'] == 'Staff' && $link['action'] == 'InstitutionSiteStaff/add')) {
					if (!$this->Session->check('InstitutionSite.id')) {
						continue;
					}
				}
				$controller = $link['controller'];
				$params = (isset($link['params']) ? '/'.$link['params'] : '') . (strlen($_params)>0 ? '/'.$_params : '');
				$url = sprintf('%s%s/%s', $this->webroot, $controller , $link['action']) . $params;
				$selected = $link['selected'] ? 'left_nav_li_selected' : '';
				if(!$in && $link['selected']) {
					$in = true;
				}
				$filename = str_replace('/', '.', $link['action']);
				$icon = $this->Html->image(sprintf('nav_icons/%s/%s.png', $controller, $filename));
				$wizard = $link['wizard'] ? 'wizard="true"' : '';
				$itemHtml .= sprintf($item, $url, $wizard, $selected, $icon, __($link['title']));
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
<?php 
$wizardRun = false;
if($this->Session->check('WizardMode') && $this->Session->read('WizardMode')==true){
	$wizardRun = $this->Session->read('WizardMode');
}
?>

<script type="text/javascript">
   var wizardRun = "<?php echo $wizardRun; ?>";
</script>
