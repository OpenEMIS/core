<?php
$options = array(
	0 => array(
		'url' => array('controller' => 'InstitutionSites', 'action' => 'InstitutionSiteQualityRubric', 'index', 'status' => 0),
		'text' => __('New')
	),
	1 => array(
		'url' => array('controller' => 'InstitutionSites', 'action' => 'InstitutionSiteQualityRubric', 'index', 'status' => 1),
		'text' => __('Draft')
	),
	2 => array(
		'url' => array('controller' => 'InstitutionSites', 'action' => 'InstitutionSiteQualityRubric', 'index', 'status' => 2),
		'text' => __('Completed')
	)
);

$selectedAction = isset($selectedAction) ? $selectedAction : 0;

?>
<ul class="nav nav-tabs">
	<?php foreach($options as $option => $arr): ?>
		<li role="presentation" class="<?php echo ($option == $selectedAction) ? 'active' : ''; ?>"><?php echo $this->Html->link($arr['text'], $arr['url']); ?></li>
	<?php endforeach; ?>
</ul>
