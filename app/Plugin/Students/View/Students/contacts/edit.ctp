<?php /*

<?php echo $this->element('breadcrumb'); ?>
<?php echo $this->Html->script('app.date', false); ?>

<div id="contact" class="content_wrapper edit add">
     <h1>
        <span><?php echo __('Contacts'); ?></span>
        <?php 
        if ($_edit && !$WizardMode) {
            echo $this->Html->link(__('Back'), array('action' => 'contactsView', $id), array('class' => 'divider'));
        }
        ?>
    </h1>
	<?php
	echo $this->Form->create('StudentContact', array(
		'url' => array('controller' => 'Students', 'action' => 'contactsEdit', $id, $selectedContactOptions),
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
	));
	?>

    
    <?php $obj = @$this->request->data['StudentContact']; ?>
	<?php echo $this->Form->input('StudentContact.id');?>

    <div class="row">
        <div class="label"><?php echo __('Type'); ?></div>
        <div class="value">
            <?php
                echo $this->Form->input('contact_option_id', array(
                    'options' => $contactOptions,
                    'default' => $selectedContactOptions,
                    'url' => sprintf('/%s/%s/%s', $this->params['controller'], $this->params['action'], $id),
                    'onchange' => 'jsForm.change(this)'
                ));
            ?>
        </div>
    </div>
    
    <div class="row select_row">
        <div class="label"><?php echo __('Description'); ?></div>
        <div class="value">
            <?php
                echo $this->Form->input('contact_type_id', array(
                    'options' => $contactTypeOptions,
                ));
            ?>
        </div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Value'); ?></div>
        <div class="value">
            <?php echo $this->Form->input('value'); ?>
        </div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Preferred'); ?></div>
        <div class="value"><?php echo $this->Form->input('preferred', array('options'=>array('1'=>'Yes', '0'=>'No'))); ?></div>
    </div>

	 <div class="controls">
		  <?php if(!$WizardMode){ ?>
        <input type="submit" value="<?php echo __("Save"); ?>" class="btn_save btn_right" onclick="return Config.checkValidate();"/>
        <?php echo $this->Html->link(__('Cancel'), array('action' => 'contactsView',$id), array('class' => 'btn_cancel btn_left')); ?>
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
    echo $this->Html->link($this->Label->get('general.back'), array('action' => 'contactsView', $id), array('class' => 'divider'));
}
$this->end();
$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'contactsEdit', $id, $contactOptionId));
echo $this->Form->create($model, $formOptions);
echo $this->Form->input('id');

echo $this->Form->input('contact_option_id', array(
    'options' => $contactOptions,
    'default' => $contactOptionId,
    'url' => sprintf('%s/%s/%s', $this->params['controller'], $this->params['action'], $id),
    'onchange' => 'jsForm.change(this)',
    'label' => array('text'=> $this->Label->get('general.type'), 'class'=>'col-md-3 control-label')
));
echo $this->Form->input('contact_type_id', array(
    'options' => $contactTypeOptions,
    'label' => array('text'=> $this->Label->get('general.description'), 'class'=>'col-md-3 control-label')
));
echo $this->Form->input('value');
echo $this->Form->input('preferred', array('options' => $yesnoOptions));

echo $this->FormUtility->getFormWizardButtons(array(
    'cancelURL' => array('action' => 'contactsView', $id),
    'WizardMode' => $WizardMode,
    'WizardEnd' => isset($wizardEnd)?$wizardEnd : NULL,
    'WizardMandatory' => isset($mandatory)?$mandatory : NULL
));
echo $this->Form->end();

$this->end();
?>