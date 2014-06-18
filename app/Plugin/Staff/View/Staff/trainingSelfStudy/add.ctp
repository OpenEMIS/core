<?php
echo $this->Html->css('../js/plugins/fileupload/bootstrap-fileupload', array('inline' => false));
echo $this->Html->script('plugins/fileupload/bootstrap-fileupload', false);
echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);
echo $this->Html->script('/Staff/js/training_self_studies', false);
echo $this->Html->css('jquery-ui.min', 'stylesheet', array('inline' => false));
echo $this->Html->script('jquery-ui.min', false);
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');

$startDate = array('id' => 'startDate');
$endDate = array('id' => 'endDate');
if(!empty($this->data[$model]['id'])){
	$redirectAction = array('action' => 'trainingSelfStudyView', $this->data[$model]['id']);
	$startDate['data-date'] = $this->data[$model]['start_date'];
    $endDate['data-date'] = $this->data[$model]['end_date'];
}
else{
	$redirectAction = array('action' => 'trainingSelfStudy');
    $endDate['data-date'] =  date('d-m-Y', time() + 86400);
}
$readonly = array();
if($this->request->data['StaffTrainingSelfStudy']['resultEditable']=='2'){
	$readonly['readonly'] = 'readonly';
}
echo $this->Html->link($this->Label->get('general.back'), $redirectAction, array('class' => 'divider'));
$this->end();
$this->start('contentBody');
$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $this->action, 'plugin'=>'Staff'));
$formOptions['type'] = 'file';
$formOptions['deleteUrl']=$this->params['controller']."/trainingSelfStudyAttachmentsDelete/";
echo $this->Form->create($model, $formOptions);
 echo $this->Form->input('resultEditable', array('type'=> 'hidden', 'default'=>$this->request->data['StaffTrainingSelfStudy']['resultEditable']));
echo $this->Form->hidden('id');
echo $this->Form->hidden('training_status_id');
echo $this->Form->input('training_achievement_type_id', array('options'=>$trainingAchievementTypeOptions, 'label' => array('text' => $this->Label->get('StaffTrainingSelfStudy.achievement_type'), 'class' => 'col-md-3 control-label'), $readonly));
echo $this->Form->input('title', array($readonly));

if($this->request->data['StaffTrainingSelfStudy']['resultEditable']!='2'){
	echo $this->FormUtility->datepicker('start_date', $startDate);
	echo $this->FormUtility->datepicker('end_date', $endDate);
}else{
	echo $this->Form->input('start_date', array('type'=>'text', $readonly));
	echo $this->Form->input('end_date', array('type'=>'text', $readonly));
}
echo $this->Form->input('description', array('type'=>'textarea', $readonly));
echo $this->Form->input('objective', array('type'=>'textarea', $readonly,
	'label' => array('text' => $this->Label->get('StaffTraining.objective'), 'class' => 'col-md-3 control-label'))); 
echo $this->Form->input('location', array($readonly));
echo $this->Form->input('training_provider', array('id'=>'searchTrainingProvider','class'=>'form-control training-provider', 'url'=>'Staff/ajax_find_training_provider/', 'placeholder' => __('Training Provider'), $readonly));
echo $this->Form->input('hours', array($readonly));
echo $this->Form->input('credit_hours', array('options'=>$trainingCreditHourOptions, $readonly));
if($this->request->data['StaffTrainingSelfStudy']['resultEditable']=='2'){
	echo $this->Form->hidden('StaffTrainingSelfStudyResult.id');
	echo $this->Form->hidden('StaffTrainingSelfStudyResult.staff_training_self_study_id');
	echo $this->Form->input('StaffTrainingSelfStudyResult.result');
	echo $this->Form->input('StaffTrainingSelfStudyResult.pass', array('options' => $passfailOptions));
}
if($this->request->data['StaffTrainingSelfStudy']['resultEditable']!='2'){
	$multiple = array('multipleURL' => $this->params['controller']."/trainingSelfStudyAjaxAddField/");
	echo $this->Form->hidden('maxFileSize', array('name'=> 'MAX_FILE_SIZE','value'=>(2*1024*1024)));
	echo $this->element('templates/file_upload', compact('multiple'));

	$tableHeaders = array(__('File(s)'), '&nbsp;');
	$tableData = array();
	foreach ($attachments as $obj) {
		$row = array();
		$row[] = array($obj['StaffTrainingSelfStudyAttachment']['file_name'], array('file-id' =>$obj['StaffTrainingSelfStudyAttachment']['id']));
		$row[] = '<span class="icon_delete" title="'. $this->Label->get('general.delete').'" onClick="jsForm.deleteFile('.$obj['StaffTrainingSelfStudyAttachment']['id'].')"></span>';
		$tableData[] = $row;
	}
}
echo $this->element('templates/file_list', compact('tableHeaders', 'tableData'));

?>
<div class="controls view_controls">
		<?php if (!isset($this->request->data['StaffTrainingSelfStudy']['training_status_id']) || $this->request->data['StaffTrainingSelfStudy']['training_status_id'] == 1 || $this->request->data['StaffTrainingSelfStudy']['resultEditable']=='2') { ?>
			<input type="submit" value="<?php echo __("Save"); ?>" name='save' class="btn_save btn_right" onclick="js:if(objTrainingSelfStudies.errorFlag() && Config.checkValidate()){ return true; }else{ return false; }"/>
			<input type="submit" value="<?php echo __("Submit for Approval"); ?>" name='submitForApproval' class="btn_save btn_right" onclick="js:if(objTrainingSelfStudies.errorFlag() && Config.checkValidate()){ return true; }else{ return false; }"/>
		<?php } ?>
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'trainingSelfStudy'), array('class' => 'btn_cancel btn_left')); ?>
</div>
<?php
echo $this->Form->end();
$this->end();
?>