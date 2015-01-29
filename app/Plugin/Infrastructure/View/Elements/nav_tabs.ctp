<?php
$options = array(
	'Levels' => array(
		'url' => array('controller' => 'InfrastructureLevels', 'action' => 'index', 'plugin' => false),
		'text' => $this->Label->get('general.levels')
	),
	'Types' => array(
		'url' => array('controller' => 'InfrastructureTypes', 'action' => 'index', 'plugin' => false),
		'text' => $this->Label->get('general.types')
	),
	'Custom Fields' => array(
		'url' => array('controller' => 'InfrastructureCustomFields', 'action' => 'index', 'plugin' => false),
		'text' => $this->Label->get('general.custom_fields')
	)
);

$currentPage = isset($currentTab) ? $currentTab : 'Levels';

?>
<ul class="nav nav-tabs">
	<?php foreach($options as $option => $arr): ?>
		<li role="presentation" class="<?php echo ($option == $currentPage) ? 'active' : ''; ?>"><?php echo $this->Html->link($arr['text'], $arr['url']); ?></li>
	<?php endforeach; ?>
</ul>