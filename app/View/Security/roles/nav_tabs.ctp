<?php
if((isset($page) && $page == 'reorder')){
	$reorder = $this->Label->get('general.reorder');
	$options = array();
	if($isSuperUser){
		$options['System Defined Roles'] = array(
			'url' => array('controller' => 'Security', 'action' => 'rolesReorder', 'system_defined'),
			'text' => $this->Label->get('SecurityRole.systemDefined')
		);
	}
	
	$options['User Defined Roles'] = array(
			'url' => array('controller' => 'Security', 'action' => 'rolesReorder', 'user_defined', $selectedGroup),
			'text' => $this->Label->get('SecurityRole.userDefined')
		);
}else{
	$options = array();
	if($isSuperUser){
		$options['System Defined Roles'] = array(
			'url' => array('controller' => 'Security', 'action' => 'roles'),
			'text' => $this->Label->get('SecurityRole.systemDefined')
		);
	}
	$options['User Defined Roles'] = array(
			'url' => array('controller' => 'Security', 'action' => 'rolesUserDefined'),
			'text' => $this->Label->get('SecurityRole.userDefined')
	);
}

$currentPage = isset($currentTab) ? $currentTab : 'System Defined Roles';
?>
<ul class="nav nav-tabs">
	<?php foreach($options as $option => $arr): ?>
		<li role="presentation" class="<?php echo ($option == $currentPage) ? 'active' : ''; ?>"><?php echo $this->Html->link($arr['text'], $arr['url']); ?></li>
	<?php endforeach; ?>
</ul>