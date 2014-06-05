<?php /*
<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->script('/Staff/js/awards', false);
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->css('jquery-ui.min', 'stylesheet', array('inline' => false));
echo $this->Html->script('jquery-ui.min', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="award" class="content_wrapper edit add" selectAwardUrl="Staff/ajax_find_award/" >
	<h1>
		<span><?php echo __($subheader); ?></span>
		<?php
            if (!$WizardMode) {
	            if(!empty($this->data[$modelName]['id'])){
     			 	echo $this->Html->link(__('Back'), array('action' => 'awardView',$this->data[$modelName]['id']), array('class' => 'divider'));
		       	}else{
	        	 	echo $this->Html->link(__('Back'), array('action' => 'award'), array('class' => 'divider'));
		        }
	        }
		?>
	</h1>
	
    <?php echo $this->element('alert'); ?>
	<?php
	echo $this->Form->create($modelName, array(
		'url' => array('controller' => 'Staff', 'action' => 'awardAdd', 'plugin'=>'Staff'),
		'type' => 'file',
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
	));
	?>

	<?php if(!empty($this->data[$modelName]['id'])){ echo $this->Form->input('id', array('type'=> 'hidden')); } ?>
	<div class="row">
        <div class="label"><?php echo __('Issue Date'); ?></div>
        <div class="value">
		<?php 
			echo $this->Form->input('issue_date', array('type' => 'date', 'dateFormat' => 'DMY', 'before' => '<div class="left">', 'after' => '</div>','class'=>false)); 
		?>
        </div>
    </div>
     <div class="row">
        <div class="label"><?php echo __('Name'); ?></div>
        <div class="value">
 			<?php echo $this->Form->input('award', array('id' => 'searchAward', 'class'=>'default award', 'placeholder' => __('Award Name')));?>
        </div>
    </div>
     <div class="row">
        <div class="label"><?php echo __('Issuer'); ?></div>
        <div class="value">
        	<?php echo $this->Form->input('issuer', array('id' => 'searchIssuer', 'class'=>'default issuer', 'placeholder' => __('Issuer')));?>
        </div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Comment'); ?></div>
        <div class="value"><?php echo $this->Form->input('comment', array('type'=> 'textarea'));?></div>
    </div>
	 <?php if($WizardMode){ ?>
    <div class="add_more_controls">
        <?php echo $this->Form->submit(__('Add More'), array('div'=>false, 'name'=>'submit','class'=>"btn_save btn_right")); ?>
    </div>
    <?php } ?>
	 <div class="controls">
		<?php if(!$WizardMode){ ?>
        <input type="submit" value="<?php echo __("Save"); ?>" class="btn_save btn_right" onclick="return Config.checkValidate();"/>
	        <?php if(!empty($this->data[$modelName]['id'])){?>
	        <?php echo $this->Html->link(__('Cancel'), array('action' => 'awardView',$this->data[$modelName]['id']), array('class' => 'btn_cancel btn_left')); ?>
	        <?php }else{ ?>
	         <?php echo $this->Html->link(__('Cancel'), array('action' => 'award'), array('class' => 'btn_cancel btn_left')); ?>
	        <?php } ?>
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
 */?>

<?php
echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->css('jquery-ui.min', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);
echo $this->Html->script('jquery-ui.min', false);
echo $this->Html->script('/staff/js/awards', false);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if (!$WizardMode) {
    if(!empty($this->data[$model]['id'])){
        $redirectAction = array('action' => 'awardView', $this->data[$model]['id']);
    }
    else{
        $redirectAction = array('action' => 'award');
    }
    echo $this->Html->link($this->Label->get('general.back'), $redirectAction, array('class' => 'divider'));
}
$this->end();
$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $this->action, 'plugin'=>'Staff'));
$formOptions['id'] = 'award';
$formOptions['selectAwardUrl']=$this->params['controller']."/awardAjaxFindAward/";
echo $this->Form->create($model, $formOptions);
echo $this->Form->hidden('id');
echo $this->FormUtility->datepicker('issue_date');
echo $this->Form->input('award', array('id' => 'searchAward', 'class' => array('form-control award'), 'label'=>array('text'=> $this->Label->get('general.name'),'class'=>'col-md-3 control-label')));
echo $this->Form->input('issuer', array('id' => 'searchIssuer', 'class' => array('form-control issuer')));
echo $this->Form->input('comment');

//echo $this->FormUtility->getFormButtons(array('cancelURL' => $redirectAction));
echo $this->FormUtility->getFormWizardButtons(array(
    'cancelURL' => isset($redirectAction) ? $redirectAction : NULL,
    'WizardMode' => $WizardMode,
    'WizardEnd' => isset($wizardEnd)?$wizardEnd : NULL,
    'WizardMandatory' => isset($mandatory)?$mandatory : NULL
));

echo $this->Form->end();
$this->end();
?>
