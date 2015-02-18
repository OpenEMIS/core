<?php
$options = array(
	'AcademicPeriodLevel' => array(
		'url' => array('controller' => 'AcademicPeriods', 'action' => 'AcademicPeriodLevel'),
		'text' => __('Academic Period Levels')
	),
	'AcademicPeriod' => array(
		'url' => array('controller' => 'AcademicPeriods', 'action' => 'AcademicPeriod'),
		'text' => __('Academic Periods')
	)
);

$selectedAction = isset($selectedAction) ? $selectedAction : 'AcademicPeriod';

?>
<ul class="nav nav-tabs">
	<?php foreach($options as $option => $arr): ?>
		<li role="presentation" class="<?php echo ($option == $selectedAction) ? 'active' : ''; ?>"><?php echo $this->Html->link($arr['text'], $arr['url']); ?></li>
	<?php endforeach; ?>
</ul>