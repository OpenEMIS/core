<?php
$options = array(
	'Single Grade' => array(
		'url' => array('action' => 'InstitutionSiteSection', 'singleGradeAdd', $selectedAcademicPeriod),
		'text' => $this->Label->get('InstitutionSiteSection.single_grade')
	),
	'Multi Grades' => array(
		'url' => array('action' => 'InstitutionSiteSection', 'multiGradesAdd', $selectedAcademicPeriod),
		'text' => $this->Label->get('InstitutionSiteSection.multi_grades')
	)
);

//$currentPage = isset($currentTab) ? $currentTab : 'Single Grade';

?>
<ul class="nav nav-tabs">
	<?php foreach($options as $option => $arr): ?>
		<li role="presentation" class="<?php echo ($option == $currentTab) ? 'active' : ''; ?>"><?php echo $this->Html->link($arr['text'], $arr['url']); ?></li>
	<?php endforeach; ?>
</ul>