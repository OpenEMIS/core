<?php /*

<?php echo $this->element('breadcrumb'); ?>
<?php echo $this->Html->script('app.date', false); ?>

<div id="nationality" class="content_wrapper edit add">
     <h1>
        <span><?php echo __('Nationalities'); ?></span>
        <?php 
        if ($_edit && !$WizardMode) {
            echo $this->Html->link(__('Back'), array('action' => 'nationalitiesView', $id), array('class' => 'divider'));
        }
        ?>
    </h1>
	<?php
	echo $this->Form->create('StudentNationality', array(
		'url' => array('controller' => 'Students', 'action' => 'nationalitiesEdit'),
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
	));
	?>
    <?php $obj = @$this->request->data['StudentNationality']; ?>
	<?php echo $this->Form->input('StudentNationality.id');?>
	 <div class="row">
        <div class="label"><?php echo __('Country'); ?></div>
        <div class="value"><?php echo $this->Form->input('country_id', array('empty'=>__('--Select--'),'options'=>$countryOptions, 'default'=> $obj['country_id'])); ?></div>
    </div>
     <div class="row">
        <div class="label"><?php echo __('Comments'); ?></div>
        <div class="value"><?php echo $this->Form->input('comments', array('type'=>'textarea')); ?></div>
    </div>
     <div class="controls">
		 <?php if(!$WizardMode){ ?>
        <input type="submit" value="<?php echo __("Save"); ?>" class="btn_save btn_right" onclick="return Config.checkValidate();"/>
        <?php echo $this->Html->link(__('Cancel'), array('action' => 'nationalitiesView',$id), array('class' => 'btn_cancel btn_left')); ?>
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

*/ ?>

<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if ($_edit && !$WizardMode) {
            echo $this->Html->link(__('Back'), array('action' => 'nationalitiesView', $id), array('class' => 'divider'));
        }
$this->end();
$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'nationalitiesEdit'));
echo $this->Form->create($model, $formOptions);
echo $this->Form->hidden('id');
echo $this->Form->input('country_id', array('options'=>$countryOptions));
echo $this->Form->input('comments', array('type'=>'textarea'));


if(!$WizardMode){ 
    echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'nationalitiesView', $id)));
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