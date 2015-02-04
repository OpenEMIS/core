<?php
$options = array(
	'Area' => array(
		'url' => array('controller' => 'Areas', 'action' => 'Area'),
		'text' => __('Areas (Education)')
	),
	'AreaLevel' => array(
		'url' => array('controller' => 'Areas', 'action' => 'AreaLevel'),
		'text' => __('Area Levels (Education)')
	),
	'AreaAdministrative' => array(
		'url' => array('controller' => 'Areas', 'action' => 'AreaAdministrative'),
		'text' => __('Areas (Administrative)')
	),
	'AreaAdministrativeLevel' => array(
		'url' => array('controller' => 'Areas', 'action' => 'AreaAdministrativeLevel'),
		'text' => __('Area Levels (Administrative)')
	)
);

$selectedAction = isset($selectedAction) ? $selectedAction : 'Area';

?>
<ul class="nav nav-tabs">
	<?php foreach($options as $option => $arr): ?>
		<li role="presentation" class="<?php echo ($option == $selectedAction) ? 'active' : ''; ?>"><?php echo $this->Html->link($arr['text'], $arr['url']); ?></li>
	<?php endforeach; ?>
</ul>