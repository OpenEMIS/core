<?php /*

<?php echo $this->element('breadcrumb'); ?>
<?php echo $this->Html->script('/Staff/js/qualifications', false); ?>
<?php echo $this->Html->css('jquery-ui.min', 'stylesheet', array('inline' => false)); ?>
<?php echo $this->Html->script('jquery-ui.min', false); ?>

<div id="qualification" class="content_wrapper edit add" url="Staff/ajax_find_institution/">
   <h1>
        <span><?php echo __('Qualifications'); ?></span>
        <?php 
        if ($_edit) {
            echo $this->Html->link(__('Back'), array('action' => 'qualifications'), array('class' => 'divider'));
        }
        ?>
    </h1>

    <?php

    echo $this->Form->create('StaffQualification', array(
        'url' => array('controller' => 'Staff', 'action' => 'qualificationsAdd'),
        'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default'),
        'type' => 'file'
    ));
    ?>

    <div class="row">
        <div class="label"><?php echo __('Level'); ?></div>
        <div class="value"><?php echo $this->Form->input('qualification_level_id', array('empty'=>'--Select--', 'options'=>$levels)); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Institution'); ?></div>
        <div class="value">
            <?php echo $this->Form->input('qualification_institution', array('id' => 'search', 'onkeyup'=>'objStaffQualifications.clearValue()','class'=>'default qualification-institution-name', 'placeholder' => __('Institution Name')));?>
            <?php echo $this->Form->hidden('qualification_institution_id', array('class' => 'qualification-institution-id')); ?>
        </div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Institution Country'); ?></div>
        <div class="value"><?php echo $this->Form->input('qualification_institution_country'); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Qualification Title'); ?></div>
        <div class="value"><?php echo $this->Form->input('qualification_title'); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Major/Specialisation'); ?></div>
        <div class="value"><?php echo $this->Form->input('qualification_specialisation_id', array('empty'=>'--Select--', 'options'=>$specializations)); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Graduation Year'); ?></div>
        <div class="value"><?php echo $this->Form->input('graduate_year'); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Document No'); ?></div>
        <div class="value"><?php echo $this->Form->input('document_no'); ?></div>
    </div>
     <div class="row">
        <div class="label"><?php echo __('Grade/Score'); ?></div>
        <div class="value"><?php echo $this->Form->input('gpa'); ?></div>
    </div>
   <div class="row">
        <div class="label"><?php echo __('Attachment'); ?></div>
        <div class="value file_input">
           <?php echo $this->Form->input('files', array('name'=>'files', 'type'=>'file')); ?>
        </div>
    </div>

    <div class="controls view_controls">
        <input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" onclick="js:if(jsDate.checkValidDateClosed() && Config.checkValidate()){ return true; }else{ return false; }" />
        <?php echo $this->Html->link(__('Cancel'), array('action' => 'qualifications'), array('class' => 'btn_cancel btn_left')); ?>
    </div>
    <?php echo $this->Form->end(); ?>
</div>
 * 
 * 
 */?>
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
echo $this->Form->input('QualificationInstitution.name', array('id' => 'search', 'class' => 'form-control qualification-institution-name', 'label'=>array('text'=> $this->Label->get('QualificationInstitution.name'),'class'=>'col-md-3 control-label'), 'placeholder' => __('Institution Name')));
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