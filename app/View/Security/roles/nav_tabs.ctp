<?php
$options = array(
	'System Defined Roles' => array(
		'url' => array('controller' => 'Security', 'action' => 'roles'),
		'text' => $this->Label->get('SecurityRole.systemDefined')
	),
	'User Defined Roles' => array(
		'url' => array('controller' => 'Security', 'action' => 'rolesUserDefined'),
		'text' => $this->Label->get('SecurityRole.userDefined')
	)
);

$currentPage = isset($currentTab) ? $currentTab : 'System Defined Roles';

?>
<ul class="nav nav-tabs">
	<?php foreach($options as $option => $arr): ?>
		<li role="presentation" class="<?php echo ($option == $currentPage) ? 'active' : ''; ?>"><?php echo $this->Html->link($arr['text'], $arr['url']); ?></li>
	<?php endforeach; ?>
</ul>