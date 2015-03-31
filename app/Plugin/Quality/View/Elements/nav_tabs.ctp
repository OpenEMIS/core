<?php
$options = array(
	'RubricTemplate' => array(
		'url' => array('controller' => 'QualityRubrics', 'action' => 'RubricTemplate'),
		'text' => __('Templates')
	),
	'RubricSection' => array(
		'url' => array('controller' => 'QualityRubrics', 'action' => 'RubricSection'),
		'text' => __('Sections')
	),
	'RubricCriteria' => array(
		'url' => array('controller' => 'QualityRubrics', 'action' => 'RubricCriteria'),
		'text' => __('Criterias')
	),
	'RubricTemplateOption' => array(
		'url' => array('controller' => 'QualityRubrics', 'action' => 'RubricTemplateOption'),
		'text' => __('Options')
	)
);

$selectedAction = isset($selectedAction) ? $selectedAction : 'RubricTemplate';

?>
<ul class="nav nav-tabs">
	<?php foreach($options as $option => $arr): ?>
		<li role="presentation" class="<?php echo ($option == $selectedAction) ? 'active' : ''; ?>"><?php echo $this->Html->link($arr['text'], $arr['url']); ?></li>
	<?php endforeach; ?>
</ul>
