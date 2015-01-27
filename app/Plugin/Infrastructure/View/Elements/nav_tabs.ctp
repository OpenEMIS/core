<?php
$options = array(
	'Levels' => array('controller' => 'InfrastructureLevels', 'action' => 'index', 'plugin' => false),
	'Types' => array('controller' => 'InfrastructureTypes', 'action' => 'index', 'plugin' => false),
	'Custom Fields' => array('controller' => 'InfrastructureCustomFields', 'action' => 'index', 'plugin' => false)
);

$currentPage = isset($currentTab) ? $currentTab : 'Levels';

?>
<ul class="nav nav-tabs">
	<?php foreach($options as $option => $link): ?>
		<li role="presentation" class="<?php echo ($option == $currentPage) ? 'active' : ''; ?>"><?php echo $this->Html->link($option, $link); ?></li>
	<?php endforeach; ?>
</ul>