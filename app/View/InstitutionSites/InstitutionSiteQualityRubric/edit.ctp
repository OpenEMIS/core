<?php
//echo $this->Html->script('/Quality/js/rubric', false);
echo $this->Html->script('institution_site_quality_rubric', false);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);
$this->start('contentActions');
	echo $this->Html->link($this->Label->get('general.back'), $backUrl, array('class' => 'divider'));
$this->end();

$this->start('contentBody');
echo $this->element($tabsElement, array(), array());
	$formOptions = $this->FormUtility->getFormOptions($editUrl);
	echo $this->Form->create($model, $formOptions);
	if(isset($this->request->data['InstitutionSiteQualityRubric']['id'])) {
		echo $this->Form->hidden("id");
	}
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
?>

<?php if ($selectedAction == 0 || $selectedAction == 1) : ?>
	<div class="form-group">
		<div class="col-md-offset-4">
			<?php
				echo $this->Form->submit(__('Save As Draft'), array('name' => 'submit', 'class' => 'btn_save btn_right', 'div' => false));
				echo $this->Form->submit($this->Label->get('general.submit'), array('name' => 'postFinal', 'class' => 'btn_save btn_center', 'div' => false));
				echo $this->Html->link($this->Label->get('general.cancel'), $backUrl, array('class' => 'btn_cancel btn_left'));
			?>
		</div>
	</div>
<?php endif ?>

<?php
	echo $this->Form->end();
$this->end();
?>
