<?php echo $this->element('breadcrumb'); ?>
<?php echo $this->Html->script('app.date', false); ?>

<div id="identity" class="content_wrapper edit add">
   <h1>
        <span><?php echo __('Identities'); ?></span>
        <?php 
        if ($_edit && !$WizardMode) {
            echo $this->Html->link(__('Back'), array('action' => 'identities'), array('class' => 'divider'));
        }
        ?>
    </h1>

    <?php

    echo $this->Form->create('TeacherIdentity', array(
        'url' => array('controller' => 'Teachers', 'action' => 'identitiesAdd'),
        'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
    ));
    ?>
    <div class="row">
        <div class="label"><?php echo __('Type'); ?></div>
        <div class="value"><?php echo $this->Form->input('identity_type_id', array('empty'=>__('--Select--'),'options'=>$identityTypeOptions)); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Number'); ?></div>
        <div class="value"><?php echo $this->Form->input('number'); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Issue Date'); ?></div>
       <div class="value"><?php echo $this->Utility->getDatePicker($this->Form, 'issue_date',array('desc' => true)); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Expiry Date'); ?></div>
       <div class="value"><?php echo $this->Utility->getDatePicker($this->Form, 'expiry_date',array('desc' => true,'yearAdjust'=>5)); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Issue Location'); ?></div>
        <div class="value"><?php echo $this->Form->input('issue_location'); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Comments'); ?></div>
        <div class="value">
            <?php echo $this->Form->input('comments', array('type'=>'textarea')); ?>
        </div>
    </div>
     <div class="controls">
        <?php if(!$WizardMode){ ?>
        <input type="submit" value="<?php echo __("Save"); ?>" class="btn_save btn_right" onclick="return Config.checkValidate();"/>
        <?php echo $this->Html->link(__('Cancel'), array('action' => 'identities'), array('class' => 'btn_cancel btn_left')); ?>
        <?php }else{?>
            <?php 
                echo $this->Form->submit(__('Previous'), array('div'=>false, 'name'=>'submit','class'=>"btn_save btn_cancel_button btn_right"));
                if($mandatory!='1'){
                echo $this->Form->submit(__('Skip'), array('div'=>false, 'name'=>'submit','class'=>"btn_cancel btn_cancel_button btn_right"));
                } 
            echo $this->Form->submit(__('Next'), array('div'=>false, 'name'=>'submit', 'name'=>'submit','class'=>"btn_save btn_left",'onclick'=>"return Config.checkValidate();")); 
      } ?>
    </div>
    <?php echo $this->Form->end(); ?>
</div>