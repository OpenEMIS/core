<?php

$options = array();
$options['user'] = array(
	'url' => array('controller' => 'Security', 'action' => 'SecurityGroup', 'group_type'=>'user'),
	'text' => __('User Groups')
);
$options['system'] = array(
	'url' => array('controller' => 'Security', 'action' => 'SecurityGroup', 'group_type'=>'system'),
	'text' => __('System Groups')
);

$currentPage = isset($currentTab) ? $currentTab : 'user';
?>
<ul class="nav nav-tabs">
	<?php foreach($options as $option => $arr): ?>
		<li role="presentation" class="<?php echo ($option == $currentPage) ? 'active' : ''; ?>"><?php echo $this->Html->link($arr['text'], $arr['url']); ?></li>
	<?php endforeach; ?>
</ul>