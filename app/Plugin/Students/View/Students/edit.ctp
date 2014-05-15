<?php
echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);
echo $this->Html->css('../js/plugins/fileupload/bootstrap-fileupload', array('inline' => false));
echo $this->Html->script('plugins/fileupload/bootstrap-fileupload', false);
echo $this->Html->css('/Students/css/students', 'stylesheet', array('inline' => false));
echo $this->Html->script('app.date', false);
echo $this->Html->script('/Students/js/students', false);
echo $this->Html->script('config', false);

$this->extend('/Elements/layout/container');

$this->assign('contentId', 'student');
$this->assign('contentHeader', __('Overview'));
$this->assign('contentClass', 'edit add');
$this->start('contentActions');
if(!$WizardMode){
	echo $this->Html->link(__('View'), array('action' => 'view'), array('class' => 'divider'));
	echo $this->Html->link(__('History'), array('action' => 'history'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');

//$obj = @$data['Student'];
/*echo $this->Form->create('Student', array(
	'url' => array('controller' => 'Students', 'action' => 'edit'),
	'type' => 'file',
	'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
));*/
$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'edit'));
$labelOptions = $formOptions['inputDefaults']['label'];
$formOptions['id'] = 'student';
$formOptions['type'] = 'file';
echo $this->Form->create('Student', $formOptions);
?>

<fieldset class="section_break">
	<legend><?php echo __('Information'); ?></legend>
	<?php 
		
			$openEmisIdLabel = $labelOptions;
			$openEmisIdLabel['text'] =$this->Label->get('general.openemisId');
			
			if($autoid==''){
				echo $this->Form->input('identification_no', array(
					'label' => $openEmisIdLabel,
					'onkeyup'=>"javascript:updateHiddenField(this, 'validate_student_identification');"));
				$tempIdNo = isset($this->data['Student']['identification_no'])?$this->data['Student']['identification_no'] : '';
				echo $this->Form->hidden(null, array('id'=>'validate_student_identification', 'name' => 'validate_student_identification', 'value'=>$tempIdNo));
			}else{
				if($this->Session->check('StudentId')){ 
					echo $this->Form->input('identification_no', array('label' => $openEmisIdLabel));
				}
				else{
					echo $autoid;
					echo $this->Form->hidden('identification_no');
				}
			}
			
			echo $this->Form->input('first_name');
			echo $this->Form->input('middle_name');
			echo $this->Form->input('last_name');
			echo $this->Form->input('preferred_name');
			echo $this->Form->input('gender', array('options' => $gender));
			$tempDob = isset($this->data['Student']['date_of_birth'])?array('data-date' => $this->data['Student']['date_of_birth']) : array();
			
			echo $this->FormUtility->datepicker('date_of_birth', $tempDob);
			
			//echo $this->Form->hidden('maxFileSize', array('name'=> 'MAX_FILE_SIZE','value'=>(2*1024*1024)));
			//echo $this->element('templates/file_upload');
	?>
	
	
	
	
	
	<!--
	<?php /*if($this->Session->check('StudentId')){ ?>
	<div class="row">
		<div class="col-md-2"><?php echo __('OpenEMIS ID'); ?></div>
		<?php if($autoid==''){ ?>
		<div class="col-md-6"><?php echo $this->Form->input('identification_no', array('value' => $obj['identification_no'],
														'onkeyup'=>"javascript:updateHiddenField(this, 'validate_student_identification');")); ?>
			<input type="hidden" name="validate_student_identification" id="validate_student_identification" value="<?php echo $obj['identification_no']; ?>"/>
		</div>
		<?php }else{ ?>
		<div class="col-md-6"><?php echo $this->Form->input('identification_no', array('value' => $obj['identification_no'])); ?>
		</div>
		<?php } ?>
	</div>
	<?php }else{ ?>
	 <div class="row">
		<div class="col-md-2"><?php echo __('OpenEMIS ID'); ?>
		<?php if($autoid!=''){ ?>
		<?php echo $this->Form->input('identification_no', array('hidden'=>true,  'default'=>$autoid, 'error' => false)); ?>
		<?php } ?>
		</div>
		<div class="col-md-6">
		<?php if($autoid!=''){ ?>
			 <?php echo $autoid; ?>
		<?php }else{ ?>
			<?php echo $this->Form->input('identification_no', array('onkeyup'=>"javascript:updateHiddenField(this, 'validate_student_identification');")) ?>
			<input type="hidden" name="validate_student_identification" id="validate_student_identification"/>
		<?php } ?>
		</div>
	</div>
	<?php } 
	<div class="row">
		<div class="col-md-2"><?php echo  __('First Name'); ?></div>
		<div class="col-md-6"><?php echo $this->Form->input('first_name', array('value' => $obj['first_name'])); ?></div>
	</div>
			<div class="row">
		<div class="col-md-2"><?php echo  __('Middle Name'); ?></div>
		<div class="col-md-6"><?php echo $this->Form->input('middle_name', array('value' => $obj['middle_name'])); ?></div>
	</div>
	<div class="row">
		<div class="col-md-2"><?php echo  __('Last Name'); ?></div>
		<div class="col-md-6"><?php echo $this->Form->input('last_name', array('value' => $obj['last_name'])); ?></div>
	</div>
			<div class="row">
		<div class="col-md-2"><?php echo  __('Preferred Name'); ?></div>
		<div class="col-md-6"><?php echo $this->Form->input('preferred_name', array('value' => $obj['preferred_name'])); ?></div>
	</div>
	<div class="row">
		<div class="col-md-2"><?php echo  __('Gender'); ?></div>
		<div class="col-md-6">
		<?php echo $this->Form->input('gender', array(
			'options' => $gender,
			'default' => $obj['gender']));
		?>
		</div>
	</div>
	<div class="row">
		<div class="col-md-2"><?php echo  __('Date of Birth'); ?></div>
		<div class="col-md-6">
			<?php echo $this->Utility->getDatePicker($this->Form, 'date_of_birth', array('desc' => true,'value' => $obj['date_of_birth'], 'emptySelect' => true)); ?>
		</div>
	</div>
	<div class="row">
		<div class="col-md-2"><?php echo __('Profile Image'); ?> </div>
		<div class="col-md-6">
			<?php echo $this->Form->input('photo_content', array('type' => 'file', 'class' => 'form-error'));?>
			<?php echo $this->Form->hidden('reset_image', array('value'=>'0')); ?>
			<span id="resetDefault" class="icon_delete"></span>
			<?php echo isset($imageUploadError) ? '<div class="error-message">'.$imageUploadError.'</div>' : ''; ?>
			<br/>
			<div id="image_upload_info">
				<em>
					<?php echo sprintf(__("Max Resolution: %s pixels"), '400 x 514'); ?>
					<br/>
					<?php echo __("Max File Size:"). ' 200 KB'; ?>
					<br/>
					<?php echo __("Format Supported:"). " .jpg, .jpeg, .png, .gif"; ?>
				</em>
			</div>
		</div>
	</div>
	*/?> -->
		<?php echo $this->Form->input('photo_content', array('type' => 'file', 'class' => 'form-error')); ?>

		<div class="form-group">
			<div class="col-md-3"> </div>
			<div class="col-md-6">
				<?php echo $this->Form->hidden('reset_image', array('value' => '0')); ?>
				<span id="resetDefault" class="icon_delete"></span>
				<?php echo isset($imageUploadError) ? '<div class="error-message">' . $imageUploadError . '</div>' : ''; ?><br/>
				<div id="image_upload_info">
					<em>
						<?php echo sprintf(__("Max Resolution: %s pixels"), '400 x 514'); ?>
						<br/>
						<?php echo __("Max File Size:") . ' 200 KB'; ?>
						<br/>
						<?php echo __("Format Supported:") . " .jpg, .jpeg, .png, .gif"; ?>
					</em>
				</div>
			</div>
		</div>
</fieldset>

<fieldset class="section_break">
	<legend><?php echo __('Address'); ?></legend>
	
	<?php 
		echo $this->Form->input('address', array('onkeyup' => 'utility.charLimit(this)'));
		echo $this->Form->input('postal_code', array('onkeyup'=>"javascript:updateHiddenField(this, 'validate_student_postal_code');"));
		$tempPostCode = isset($this->data['Student']['postal_code'])?$this->data['Student']['postal_code'] : '';
		echo $this->Form->hidden(null, array('id'=>'validate_student_postal_code', 'name' => 'validate_student_postal_code', 'value' => $tempPostCode));
	?>
	
	<?php /*<div class="row">
		<div class="col-md-2"><?php echo  __('Address'); ?></div>
		<div class="col-md-6"><?php echo $this->Form->input('address', array('value' => $obj['address'], 'onkeyup' => 'utility.charLimit(this)')); ?></div>
	</div>
	<div class="row">
		<div class="col-md-2"><?php echo  __('Postal Code'); ?></div>
		<div class="col-md-6"><?php echo $this->Form->input('postal_code', array('value' => $obj['postal_code'],
														'onkeyup'=>"javascript:updateHiddenField(this, 'validate_student_postal_code');")); ?>
			<input type="hidden" name="validate_student_postal_code" id="validate_student_postal_code" value="<?php echo $obj['postal_code']; ?>"/>
		</div>
	</div>*/ ?>
</fieldset>

<fieldset class="section_break area">
	<legend id="area"><?php echo __('Address Area'); ?></legend>
		<?php echo @$this->Utility->getAreaPicker($this->Form, 'address_area_id',$this->data['Student']['address_area_id'], array()); ?>

</fieldset>

<fieldset class="section_break area">
	<legend id="area"><?php echo __('Birth Place Area'); ?></legend>
		<?php echo @$this->Utility->getAreaPicker($this->Form, 'birthplace_area_id',$this->data['Student']['birthplace_area_id'], array()); ?>

</fieldset>

<?php 
echo $this->FormUtility->getFormWizardButtons(array(
    'cancelURL' => array('action' => 'view'),
    'WizardMode' => $WizardMode,
    'WizardEnd' => isset($wizardEnd)?$wizardEnd : NULL,
    'WizardMandatory' => isset($mandatory)?$mandatory : NULL,
	'addMoreBtn' => false
));

/*
 <div class="controls">
	<?php if(!$WizardMode){ ?>
	<input type="submit" value="<?php echo __("Save"); ?>" class="btn_save btn_right" onclick="return Config.checkValidate();"/>
	<?php echo $this->Html->link(__('Cancel'), array('action' => 'view'), array('class' => 'btn_cancel btn_left')); ?>
	<?php }else{?>
		<?php if(!$this->Session->check('StudentId')){ 
		   echo $this->Form->submit(__('Cancel'), array('div'=>false, 'name'=>'submit','class'=>"btn_cancel btn_cancel_button btn_right"));
		 }
		if(!$wizardEnd){
			echo $this->Form->submit(__('Next'), array('div'=>false, 'name'=>'submit', 'name'=>'submit','class'=>"btn_save btn_left",'onclick'=>"return Config.checkValidate();")); 
		}else{
			echo $this->Form->submit(__('Finish'), array('div'=>false, 'name'=>'submit', 'name'=>'submit','class'=>"btn_save btn_left",'onclick'=>"return Config.checkValidate();")); 
		}
	}?>
</div> */ ?>


<?php echo $this->Form->end(); ?>

<?php $this->end(); ?>
