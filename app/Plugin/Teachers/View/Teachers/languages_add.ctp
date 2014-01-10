<?php echo $this->element('breadcrumb'); ?>
<?php echo $this->Html->script('app.date', false); ?>

<div id="language" class="content_wrapper edit add">
   <h1>
        <span><?php echo __('Languages'); ?></span>
        <?php 
        if ($_edit) {
            echo $this->Html->link(__('Back'), array('action' => 'languages'), array('class' => 'divider'));
        }
        ?>
    </h1>

    <?php

    echo $this->Form->create('TeacherLanguage', array(
        'url' => array('controller' => 'Teachers', 'action' => 'languagesAdd'),
        'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
    ));
    ?>
    <div class="row">
        <div class="label"><?php echo __('Evaluation Date'); ?></div>
        <div class="value"><?php echo $this->Utility->getDatePicker($this->Form, 'evaluation_date',array('desc' => true)); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Language'); ?></div>
        <div class="value"><?php echo $this->Form->input('language_id', array('empty'=>__('--Select--'),'options'=>$languageOptions)); ?></div>
    </div>
      <div class="row">
        <div class="label"><?php echo __('Listening'); ?></div>
        <div class="value">
            <?php echo $this->Form->input('listening', array('empty'=>'--Select--', 'options'=>$gradeOptions)); ?>
        </div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Speaking'); ?></div>
        <div class="value">
            <?php echo $this->Form->input('speaking', array('empty'=>'--Select--', 'options'=>$gradeOptions)); ?>
        </div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Reading'); ?></div>
        <div class="value">
            <?php echo $this->Form->input('reading', array('empty'=>'--Select--', 'options'=>$gradeOptions)); ?>
        </div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Writing'); ?></div>
        <div class="value">
            <?php echo $this->Form->input('writing', array('empty'=>'--Select--', 'options'=>$gradeOptions)); ?>
        </div>
    </div>
    <div class="controls view_controls">
        <input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
        <?php echo $this->Html->link(__('Cancel'), array('action' => 'languages'), array('class' => 'btn_cancel btn_left')); ?>
    </div>
    <?php echo $this->Form->end(); ?>
</div>