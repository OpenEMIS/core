<?php echo $this->element('breadcrumb'); ?>
<?php echo $this->Html->script('app.date', false); ?>

<div id="nationality" class="content_wrapper edit add">
   <h1>
        <span><?php echo __('Nationalities'); ?></span>
        <?php 
        if ($_edit && !$WizardMode) {
            echo $this->Html->link(__('Back'), array('action' => 'nationalities'), array('class' => 'divider'));
        }
        ?>
    </h1>

    <?php echo $this->element('alert'); ?>
    <?php

    echo $this->Form->create('TeacherNationality', array(
        'url' => array('controller' => 'Teachers', 'action' => 'nationalitiesAdd'),
        'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
    ));
    ?>

    <div class="row">
        <div class="label"><?php echo __('Country'); ?></div>
        <div class="value"><?php echo $this->Form->input('country_id', array('empty'=>__('--Select--'), 'options'=>$countryOptions, 'default'=>$defaultCountryId)); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Comments'); ?></div>
        <div class="value">
            <?php echo $this->Form->input('comments', array('type'=>'textarea')); ?>
        </div>
    </div>
      <?php if($WizardMode){ ?>
    <div class="view_controls">
        <?php echo $this->Form->submit(__('Add More'), array('div'=>false, 'name'=>'submit','class'=>"btn_save btn_cancel_button btn_right")); ?>
    </div>
    <?php } ?>
    <div class="controls">
         <?php if(!$WizardMode){ ?>
        <input type="submit" value="<?php echo __("Save"); ?>" class="btn_save btn_right" onclick="return Config.checkValidate();"/>
        <?php echo $this->Html->link(__('Cancel'), array('action' => 'nationalities'), array('class' => 'btn_cancel btn_left')); ?>
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