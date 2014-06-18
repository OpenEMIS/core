<?php
echo $this->Html->script('Staff.training_needs', false);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if(!empty($this->data[$model]['id'])){
	$redirectAction = array('action' => 'trainingNeedView', $this->data[$model]['id']);
}
else{
	$redirectAction = array('action' => 'trainingNeed');
}
echo $this->Html->link($this->Label->get('general.back'), $redirectAction, array('class' => 'divider'));

$this->end();
$this->start('contentBody');
$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $this->action, 'plugin' => 'Staff'));
echo $this->Form->create($model, $formOptions);
echo $this->Form->hidden('id');
echo $this->Form->hidden('training_status_id');

echo $this->Form->input('training_need_type', array(
	'options' => array_map('__',$trainingNeedTypeOptions),
	'label' => array('text' => $this->Label->get('StaffTraining.need_type'), 'class' => 'col-md-3 control-label'),
	'onchange' => 'objTrainingNeeds.getTrainingNeedTypeSelection(this)'
));
echo '<div class="divCourse ' . (isset($this->request->data['StaffTrainingNeed']['training_need_type']) && $this->request->data['StaffTrainingNeed']['training_need_type']=='2' ? 'hide' : '').'">';
echo $this->Form->input('ref_course_id', array(
	'options' => $trainingCourseOptions,
	'label' => array('text' => $this->Label->get('StaffTraining.course_title'), 'class' => 'col-md-3 control-label'),
	'url' => sprintf('%s/%s', $this->params['controller'], $this->params['action']),
	'onchange' => 'objTrainingNeeds.getDetailsAfterChangeCourse(this)'
));
echo $this->Form->hidden('ref_course_title', array('class' => 'ref_course_title'));
echo $this->Form->input('ref_course_code', array('readonly' => 'readonly', 'class' => 'form-control ref_course_code', 'label' => array('text' => $this->Label->get('StaffTraining.code'), 'class' => 'col-md-3 control-label')));
echo $this->Form->input('ref_course_description', array('readonly' => 'readonly', 'class' => 'form-control ref_course_description', 'type' => 'textarea','label' => array('text' => $this->Label->get('StaffTraining.description'), 'class' => 'col-md-3 control-label')));
echo $this->Form->input('ref_course_requirement', array('readonly' => 'readonly', 'class' => 'form-control ref_course_requirement', 'label' => array('text' => $this->Label->get('StaffTraining.requirement'), 'class' => 'col-md-3 control-label')));
echo '</div>';
echo '<div class="divNeed ' . ((isset($this->request->data['StaffTrainingNeed']['training_need_type']) && $this->request->data['StaffTrainingNeed']['training_need_type']=='1') ? 'hide' : '').'">';
echo $this->Form->input('ref_need_id', array(
	'options' => $trainingNeedCategoryOptions,
	'label' => array('text' => $this->Label->get('StaffTraining.need_category'), 'class' => 'col-md-3 control-label'),
	'url' => sprintf('%s/%s', $this->params['controller'], $this->params['action']),
	'onchange' => 'objTrainingNeeds.getDetailsAfterChangeCourse(this)'
));
echo $this->Form->input('ref_need_title', array('label' => array('text' => $this->Label->get('StaffTraining.course_title'), 'class' => 'col-md-3 control-label')));
echo $this->Form->input('ref_need_code', array('label' => array('text' => $this->Label->get('StaffTraining.code'), 'class' => 'col-md-3 control-label')));
echo $this->Form->input('ref_need_description', array('type' => 'textarea', 'label' => array('text' => $this->Label->get('StaffTraining.description'), 'class' => 'col-md-3 control-label')));
echo $this->Form->input('ref_need_requirement', array('label' => array('text' => $this->Label->get('StaffTraining.requirement'), 'class' => 'col-md-3 control-label')));
echo '</div>';

echo $this->Form->input('training_priority_id', array('options' => $trainingPriorityOptions));
echo $this->Form->input('comments');

//echo $this->FormUtility->getFormButtons(array('cancelURL' => $redirectAction));
?>
<div class="controls view_controls">
<?php if (!isset($this->request->data['StaffTrainingNeed']['training_status_id']) || $this->request->data['StaffTrainingNeed']['training_status_id'] == 1) { ?>
			<input type="submit" value="<?php echo $this->Label->get('general.save'); ?>" name='save' class="btn_save btn_right" onclick="return Config.checkValidate();"/>
			<input type="submit" value="<?php echo __("Submit for Approval"); ?>" name='submitForApproval' class="btn_save btn_right" onclick="return Config.checkValidate();"/>
<?php } ?>
<?php echo $this->Html->link($this->Label->get('general.cancel'), array('action' => 'trainingNeed'), array('class' => 'btn_cancel btn_left')); ?>
</div>

<?php
echo $this->Form->end();
$this->end();
?>
