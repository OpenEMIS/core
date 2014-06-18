<?php echo $this->element('breadcrumb'); ?>
<?php echo $this->Html->script('/Teachers/js/qualifications', false); ?>
<?php echo $this->Html->css('jquery-ui.min', 'stylesheet', array('inline' => false)); ?>
<?php echo $this->Html->script('jquery-ui.min', false); ?>

<div id="qualification" class="content_wrapper edit add" url="Teachers/ajax_find_institution/">
    <h1>
        <span><?php echo __('Qualifications'); ?></span>
        <?php 
        if ($_edit) {
            echo $this->Html->link(__('Back'), array('action' => 'qualifications'), array('class' => 'divider'));
        }
        ?>
    </h1>
    <?php echo $this->element('alert'); ?>

    <?php

    echo $this->Form->create('TeacherQualification', array(
        'url' => array('controller' => 'Teachers', 'action' => 'qualificationsAdd'),
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
            <?php echo $this->Form->input('qualification_institution', array('id' => 'search', 'onkeyup'=>'objTeacherQualifications.clearValue()','class'=>'default qualification-institution-name', 'placeholder' => __('Institution Name')));?>
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