<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));

echo $this->Html->script('app.date', false);

echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Edit Behaviour Details'));

$data = $studentBehaviourObj[0]['StudentBehaviour'];

$this->start('contentActions');
echo $this->Html->link(__('View'), array('action' => 'studentsBehaviourView', $data['id']), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
?>

<div id="studentBehaviourEdit" class="content_wrapper">

	<?php
	$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'studentsBehaviourEdit'));
	$labelOptions = $formOptions['inputDefaults']['label'];
	echo $this->Form->create('InstitutionSiteStudentBehaviour', $formOptions);

//	echo $this->Form->create('InstitutionSiteStudentBehaviour', array(
//		'url' => array('controller' => 'InstitutionSites', 'action' => 'studentsBehaviourEdit'),
//		'inputDefaults' => array('label' => false, 'div' => false)
//	));
	echo $this->Form->hidden('id', array('value' => $data['id']));
	echo $this->Form->hidden('student_id', array('value' => $data['student_id']));
	?>
	<div class="form-group edit">
        <label class="col-md-3 control-label"><?php echo __('Institution Site'); ?></label>
        <div class="col-md-4 text">
			<?php echo $institutionSiteOptions[$institutionSiteId]; ?>
        </div>
    </div>

	<?php
	$labelOptions['text'] = $this->Label->get('general.category');
	echo $this->Form->input('student_behaviour_category_id', array('options' => $categoryOptions, 'label' => $labelOptions, 'value' => $data['student_behaviour_category_id']));


	echo $this->FormUtility->datepicker('date_of_behaviour', array('id' => 'date_of_behaviour', 'data-date' => $data['date_of_behaviour']));

	echo $this->Form->input('title', array('value' => $data['title']));

	echo $this->Form->input('description', array(
		'onkeyup' => 'utility.charLimit(this)',
		'type' => 'textarea',
		'value' => $data['description']
	));

	echo $this->Form->input('action', array(
		'onkeyup' => 'utility.charLimit(this)',
		'type' => 'textarea',
		'value' => $data['action']
	));
	?>

    <div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'studentsBehaviourView', $data['id']), array('class' => 'btn_cancel btn_left')); ?>
	</div>

	<?php echo $this->Form->end(); ?>
</div>
<?php $this->end(); ?>