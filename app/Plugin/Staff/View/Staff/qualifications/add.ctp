<?php
echo $this->Html->css('../js/plugins/fileupload/bootstrap-fileupload', array('inline' => false));
echo $this->Html->script('plugins/fileupload/bootstrap-fileupload', false);
echo $this->Html->css('jquery-ui.min', 'stylesheet', array('inline' => false));
echo $this->Html->script('jquery-ui.min', false);
echo $this->Html->script('Staff.qualifications', false);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
	if(!empty($this->data[$model]['id'])){
        $redirectAction = array('action' => 'qualificationsView', $this->data[$model]['id']);
    }
    else{
        $redirectAction = array('action' => 'qualifications');
    }
    echo $this->Html->link($this->Label->get('general.back'), $redirectAction, array('class' => 'divider'));
$this->end();
$this->start('contentBody');
$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $this->action, 'plugin'=>'Staff'));
$formOptions['id'] = 'qualification';
$formOptions['type'] = 'file';
$formOptions['searchQualificationUrl']=$this->params['controller']."/qualificationsAjaxFindInstitution/";
echo $this->Form->create($model, $formOptions);
echo $this->Form->hidden('id');
echo $this->Form->input('qualification_level_id', array('options'=>$levelOptions,'label'=>array('text'=> $this->Label->get('QualificationLevel.name'),'class'=>'col-md-3 control-label')));
echo $this->Form->input('qualification_institution_name', array('id' => 'search', 'class' => 'form-control qualification-institution-name', 'label'=>array('text'=> $this->Label->get('QualificationInstitution.name'),'class'=>'col-md-3 control-label'), 'placeholder' => __('Institution Name')));
echo $this->Form->hidden('qualification_institution_id', array('class' => 'qualification-institution-id'));
echo $this->Form->input('qualification_institution_country', array('label'=>array('text'=> $this->Label->get('StaffQualification.qualification_institution_country'),'class'=>'col-md-3 control-label')));
echo $this->Form->input('qualification_title');
echo $this->Form->input('qualification_specialisation_id', array('options'=>$specializationOptions,'label'=>array('text'=> $this->Label->get('QualificationSpecialisation.name'),'class'=>'col-md-3 control-label')));
echo $this->Form->input('graduate_year');
echo $this->Form->input('document_no');
echo $this->Form->input('gpa', array('label'=>array('text'=> $this->Label->get('StaffQualification.gpa'),'class'=>'col-md-3 control-label')));
echo $this->Form->hidden('maxFileSize', array('name'=> 'MAX_FILE_SIZE','value'=>(2*1024*1024)));
echo $this->element('templates/file_upload');
echo $this->FormUtility->getFormButtons(array('cancelURL' =>$redirectAction));
echo $this->Form->end();
$this->end();
?>
