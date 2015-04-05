<?php
echo $this->Html->script('/Quality/js/rubric', false);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);
$this->start('contentActions');
	echo $this->Html->link($this->Label->get('general.back'), $backUrl, array('class' => 'divider'));
$this->end();

$this->start('contentBody');
	$formOptions = $this->FormUtility->getFormOptions($editUrl);
	echo $this->Form->create($model, $formOptions);
	echo $this->Form->hidden("status");
	echo $this->Form->hidden("comment");
	echo $this->Form->hidden("rubric_template_id");
	echo $this->Form->hidden("academic_period_id");
	echo $this->Form->hidden("education_programme_id");
	echo $this->Form->hidden("education_grade_id");
	echo $this->Form->hidden("institution_site_section_id");
	echo $this->Form->hidden("institution_site_class_id");
	echo $this->Form->hidden("staff_id");
	echo $this->Form->hidden("institution_site_id");
	echo $this->element('../InstitutionSites/InstitutionSiteQualityRubric/preview');
	echo $this->Form->button('reload', array('id' => 'reload', 'type' => 'submit', 'name' => 'submit', 'value' => 'reload', 'class' => 'hidden'));
	echo $this->FormUtility->getFormButtons(array('cancelURL' => $backUrl));
	echo $this->Form->end();
?>

<?php
$this->end();
?>
