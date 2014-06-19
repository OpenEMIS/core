<?php /*

<?php
echo $this->Html->css('/Students/css/students', 'stylesheet', array('inline' => false));
echo $this->Html->script('app.date', false);
echo $this->Html->script('/Students/js/students', false);
echo $this->Html->script('config', false);
//$obj = @$data['Student'];
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="student" class="content_wrapper edit add">
	<h1>
		<span><?php echo __($subheader); ?></span>
		<?php
		if(!empty($this->data[$modelName]['id'])){
			echo $this->Html->link(__('View'), array('action' => 'healthImmunizationView', $this->data[$modelName]['id']), array('class' => 'divider'));
		}
		else{
			echo $this->Html->link(__('List'), array('action' => 'healthImmunization'), array('class' => 'divider'));
		}
		?>
	</h1>
	
	<?php
	echo $this->Form->create($modelName, array(
		'url' => array('controller' => 'Students', 'action' =>  $this->action, 'plugin'=>'Students'),
		'type' => 'file',
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
	));
	?>

	<?php if(!empty($this->data[$modelName]['id'])){ echo $this->Form->input('id', array('type'=> 'hidden')); } ?>
	<div class="row">
        <div class="label"><?php echo __('Date'); ?></div>
        <div class="value">
		<?php 
			echo $this->Form->input('date', array('type' => 'date', 'dateFormat' => 'DMY', 'before' => '<div class="left">', 'after' => '</div>','class'=>false)); 
		?>
        </div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Immunization'); ?></div>
        <div class="value">
		<?php 
			echo $this->Form->input('health_immunization_id', array(
									'options' => $healthImmunizationsOptions,
									'label' => false)
									); 
		?>
        </div>
    </div>
	<div class="row">
        <div class="label"><?php echo __('Dosage'); ?></div>
        <div class="value"><?php echo $this->Form->input('dosage'); ?> </div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Comment'); ?></div>
        <div class="value"><?php echo $this->Form->input('comment', array('type'=> 'textarea'));?></div>
    </div>
	
	<div class="controls view_controls">
		<input type="submit" value="<?php echo __("Save"); ?>" class="btn_save btn_right" onclick="return Config.checkValidate();"/>
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'healthImmunization'), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>
 */ ?>
 
<?php
echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');

if(!empty($this->data[$model]['id'])){
	$redirectAction = array('action' => 'healthImmunizationView', $this->data[$model]['id']);
	$setDate = array('data-date' => $this->data[$model]['date']);
}
else{
	$redirectAction = array('action' => 'healthImmunization');
	$setDate = null;
}
echo $this->Html->link($this->Label->get('general.back'), $redirectAction, array('class' => 'divider'));

$this->end();
$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $this->action, 'plugin'=>'Students'));
echo $this->Form->create($model, $formOptions);
echo $this->Form->hidden('id');
echo $this->FormUtility->datepicker('date', $setDate);
echo $this->Form->input('health_immunization_id', array('options' => $healthImmunizationsOptions, 'label'=>array('text'=> $this->Label->get('HealthImmunization.name'),'class'=>'col-md-3 control-label'))); 
echo $this->Form->input('dosage');
echo $this->Form->input('comment');

echo $this->FormUtility->getFormButtons(array('cancelURL' => $redirectAction));
echo $this->Form->end();
$this->end();
?>
