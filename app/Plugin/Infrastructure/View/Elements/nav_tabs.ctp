<?php
$options = array(
	'Categories' => array('action' => 'index'),
	'Types' => '#',
	'Custom Fields' => '#'
);

$currentPage = isset($currentPage) ? $currentPage : 'Categories';

?>
<ul class="nav nav-tabs">
	<?php foreach($options as $option => $link): ?>
		<li role="presentation" class="<?php echo ($option == $currentPage) ? 'active' : ''; ?>"><?php echo $this->Html->link($option, $link); ?></li>
	<?php endforeach; ?>
</ul>