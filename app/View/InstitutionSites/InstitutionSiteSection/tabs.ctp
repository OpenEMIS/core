<?php
$options = array(
	'Single Grade' => array('action' => 'InstitutionSiteSection', 'singleGradeAdd', $selectedAcademicPeriod),
	'Multiple Grades' => array('action' => 'InstitutionSiteSection', 'multiGradesAdd', $selectedAcademicPeriod)
);

//$currentPage = isset($currentTab) ? $currentTab : 'Single Grade';

?>
<ul class="nav nav-tabs">
	<?php foreach($options as $option => $link): ?>
		<li role="presentation" class="<?php echo ($option == $currentTab) ? 'active' : ''; ?>"><?php echo $this->Html->link($option, $link); ?></li>
	<?php endforeach; ?>
</ul>