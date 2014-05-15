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
		<span><?php echo __('Edit Overview'); ?></span>
		<?php
		echo $this->Html->link(__('View'), array('action' => 'healthView'), array('class' => 'divider'));
		?>
	</h1>
	
	<?php
	echo $this->Form->create($modelName, array(
		'url' => array('controller' => 'Students', 'action' => 'healthEdit', 'plugin'=>'Students'),
		'type' => 'file',
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
	));
	?>

	<?php if(!empty($this->data[$modelName]['id'])){ echo $this->Form->input('id', array('type'=> 'hidden')); } ?>
	<div class="row">
        <div class="label"><?php echo __('Blood Type'); ?></div>
        <div class="value">
		<?php 
			echo $this->Form->input('blood_type', array(
									'options' => $bloodTypeOptions,
									'label' => false)
									); 
		?>
        </div>
    </div>
	<div class="row">
        <div class="label"><?php echo __('Doctor Name'); ?></div>
        <div class="value"><?php echo $this->Form->input('doctor_name');?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Doctor Contact'); ?></div>
        <div class="value"><?php echo $this->Form->input('doctor_contact');?></div>
    </div>
	<div class="row">
        <div class="label"><?php echo __('Medical Facility'); ?></div>
        <div class="value"><?php echo $this->Form->input('medical_facility');?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Health Insurance'); ?></div>
        <div class="value">
		<?php 
			echo $this->Form->input('health_insurance', array(
									'options' => $booleanOptions,
									'label' => false)
									); 
		?>
        </div>
    </div>
	<div class="controls view_controls">
		<input type="submit" value="<?php echo __("Save"); ?>" class="btn_save btn_right" onclick="return Config.checkValidate();"/>
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'healthView'), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>*/?>

<?php
echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.back'), array('action' => 'healthView'), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'healthEdit'));
echo $this->Form->create($model, $formOptions);
echo $this->Form->hidden('id');
echo $this->Form->input('blood_type', array('options' => $bloodTypeOptions)); 
echo $this->Form->input('doctor_name');
echo $this->Form->input('doctor_contact');
echo $this->Form->input('medical_facility');
echo $this->Form->input('health_insurance', array('options' => $yesnoOptions));
echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'healthView')));

echo $this->Form->end();

$this->end();
?>
