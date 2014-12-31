<?php
$options = array(
	'Categories' => array('controller' => 'InfrastructureCategories', 'action' => 'index', 'plugin' => false),
	'Types' => array('controller' => 'InfrastructureTypes', 'action' => 'categories', 'plugin' => false),
	'Custom Fields' => '#'
);

$currentPage = isset($currentTab) ? $currentTab : 'Categories';

?>
<ul class="nav nav-tabs">
	<?php foreach($options as $option => $link): ?>
		<li role="presentation" class="<?php echo ($option == $currentPage) ? 'active' : ''; ?>"><?php echo $this->Html->link($option, $link); ?></li>
	<?php endforeach; ?>
</ul>