<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));

echo $this->Html->script('app.date', false);
echo $this->Html->script('institution_site_student_behaviour', false);

echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Add Behaviour'));

$this->start('contentBody');
?>

<div id="studentBehaviourAdd" class="content_wrapper add">

	<?php
	$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'studentsBehaviourAdd'));
	$labelOptions = $formOptions['inputDefaults']['label'];
	echo $this->Form->create('InstitutionSiteStudentBehaviour', $formOptions);

//	echo $this->Form->create('InstitutionSiteStudentBehaviour', array(
//		'url' => array('controller' => 'InstitutionSites', 'action' => 'studentsBehaviourAdd'),
//		'inputDefaults' => array('label' => false, 'div' => false)
//	));
	?>

	<div class="form-group edit">
        <label class="col-md-3 control-label"><?php echo __('Institution Site'); ?></label>
        <div class="col-md-4 text">
			<?php echo $institutionSiteOptions[$institutionSiteId]; ?>
        </div>
    </div>

	<?php
	$labelOptions['text'] = $this->Label->get('general.category');
	echo $this->Form->input('student_behaviour_category_id', array('options' => $categoryOptions, 'label' => $labelOptions));


	echo $this->FormUtility->datepicker('date_of_behaviour', array('id' => 'date_of_behaviour'));

	echo $this->Form->input('title');

	echo $this->Form->input('description', array(
		'onkeyup' => 'utility.charLimit(this)',
		'type' => 'textarea'
	));

	echo $this->Form->input('action', array(
		'onkeyup' => 'utility.charLimit(this)',
		'type' => 'textarea'
	));
	?>

	<div class="controls">
		<input type="hidden" name="data[InstitutionSiteStudentBehaviour][student_id]" id="student_id" value="<?php echo $studentId; ?>" />
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" onclick=""  />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'studentsBehaviour', $studentId), array('class' => 'btn_cancel btn_left')); ?>
	</div>

	<?php echo $this->Form->end(); ?>
</div>
<?php $this->end(); ?>