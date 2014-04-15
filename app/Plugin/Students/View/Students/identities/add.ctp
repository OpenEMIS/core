<?php /*

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
    <?php echo $this->element('alert'); ?>
    <?php

    echo $this->Form->create('StudentIdentity', array(
        'url' => array('controller' => 'Students', 'action' => 'identitiesAdd'),
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
     <?php if($WizardMode){ ?>
    <div class="add_more_controls">
        <?php echo $this->Form->submit(__('Add More'), array('div'=>false, 'name'=>'submit','class'=>"btn_save btn_right")); ?>
    </div>
    <?php } ?>
    <div class="controls">
        <?php if(!$WizardMode){ ?>
        <input type="submit" value="<?php echo __("Save"); ?>" class="btn_save btn_right" onclick="return Config.checkValidate();"/>
        <?php echo $this->Html->link(__('Cancel'), array('action' => 'identities'), array('class' => 'btn_cancel btn_left')); ?>
        <?php }else{?>
            <?php 
                echo $this->Form->submit(__('Previous'), array('div'=>false, 'name'=>'submit','class'=>"btn_save btn_right"));

                if(!$wizardEnd){
                    echo $this->Form->submit(__('Next'), array('div'=>false, 'name'=>'submit', 'name'=>'submit','class'=>"btn_save btn_right",'onclick'=>"return Config.checkValidate();")); 
                }else{
                    echo $this->Form->submit(__('Finish'), array('div'=>false, 'name'=>'submit', 'name'=>'submit','class'=>"btn_save btn_right",'onclick'=>"return Config.checkValidate();")); 
                }
                if($mandatory!='1' && !$wizardEnd){
                    echo $this->Form->submit(__('Skip'), array('div'=>false, 'name'=>'submit','class'=>"btn_cancel btn_cancel_button btn_left"));
                } 
      } ?>
    </div>
    <?php echo $this->Form->end(); ?>
</div>
 * 
 */ ?>

<?php
echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if ($_edit && !$WizardMode) {
            echo $this->Html->link(__('Back'), array('action' => 'identities'), array('class' => 'divider'));
        }
$this->end();
$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'identitiesAdd'));
echo $this->Form->create($model, $formOptions);

echo $this->Form->input('identity_type_id', array('options'=>$identityTypeOptions));
echo $this->Form->input('number'); 
echo $this->FormUtility->datepicker('issue_date', array('id' => 'IssueDate'));
echo $this->FormUtility->datepicker('expiry_date', array('id' => 'ExpiryDate', 'date' => date('d-m-Y', time() + 86400)));
echo $this->Form->input('issue_location');
echo $this->Form->input('comments', array('type'=>'textarea'));


if(!$WizardMode){ 
    echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'identities')));
}
else{
    echo '<div class="add_more_controls">'.$this->Form->submit(__('Add More'), array('div'=>false, 'name'=>'submit','class'=>"btn_save btn_right")).'</div>';
    
    echo $this->Form->submit(__('Previous'), array('div'=>false, 'name'=>'submit','class'=>"btn_save btn_right"));

    if(!$wizardEnd){
        echo $this->Form->submit(__('Next'), array('div'=>false, 'name'=>'submit', 'name'=>'submit','class'=>"btn_save btn_right",'onclick'=>"return Config.checkValidate();")); 
    }else{
        echo $this->Form->submit(__('Finish'), array('div'=>false, 'name'=>'submit', 'name'=>'submit','class'=>"btn_save btn_right",'onclick'=>"return Config.checkValidate();")); 
    }
    if($mandatory!='1' && !$wizardEnd){
        echo $this->Form->submit(__('Skip'), array('div'=>false, 'name'=>'submit','class'=>"btn_cancel btn_cancel_button btn_left"));
    } 
}

echo $this->Form->end();
$this->end();
?>
