<?php
echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);
echo $this->Html->css('../js/plugins/fileupload/bootstrap-fileupload', array('inline' => false));
echo $this->Html->script('plugins/fileupload/bootstrap-fileupload', false);
echo $this->Html->script('holder', false);
echo $this->Html->script('app.date', false);
echo $this->Html->script('app.area', false);
echo $this->Html->script('config', false);

$this->extend('/Elements/layout/container');

$this->assign('contentId', 'student');
$this->assign('contentHeader', __('Overview'));
$this->start('contentActions');
if(!$WizardMode){
	echo $this->Html->link(__('View'), array('action' => 'view'), array('class' => 'divider'));
	echo $this->Html->link(__('History'), array('action' => 'history'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');

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
		$openEmisIdLabel['text'] = $this->Label->get('general.openemisId');
		
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
		echo $this->Form->input('gender', array('options' => $genderOptions));
		$tempDob = isset($this->data['Student']['date_of_birth']) ? array('data-date' => $this->data['Student']['date_of_birth']) : array();
		echo $this->FormUtility->datepicker('date_of_birth', $tempDob);
		
		$imgOptions = array();
		$imgOptions['field'] = 'photo_content';
		$imgOptions['width'] = '90';
		$imgOptions['height'] = '115';
		$imgOptions['label'] = __('Profile Image');
		if(isset($this->data['Student']['photo_name']) && isset($this->data['Student']['photo_content'])) {
			$imgOptions['src'] = $this->Image->getBase64($this->data['Student']['photo_name'], $this->data['Student']['photo_content']);
		}
		echo $this->element('templates/file_upload_preview', $imgOptions);
	?>
		<!--div class="form-group">
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
		</div-->
</fieldset>

<fieldset class="section_break">
	<legend><?php echo __('Address'); ?></legend>
	
	<?php 
		echo $this->Form->input('address', array('onkeyup' => 'utility.charLimit(this)'));
		echo $this->Form->input('postal_code', array('onkeyup'=>"javascript:updateHiddenField(this, 'validate_student_postal_code');"));
		$tempPostCode = isset($this->data['Student']['postal_code'])?$this->data['Student']['postal_code'] : '';
		echo $this->Form->hidden(null, array('id'=>'validate_student_postal_code', 'name' => 'validate_student_postal_code', 'value' => $tempPostCode));
	?>
</fieldset>

<fieldset class="section_break">
	<legend id="area"><?php echo __('Address Area'); ?></legend>
	<?php echo $this->FormUtility->areapicker('address_area_id', array('model' => 'Area', 'value' => $addressAreaId)); ?>
</fieldset>

<fieldset class="section_break">
	<legend id="area"><?php echo __('Birth Place Area'); ?></legend>
	<?php echo $this->FormUtility->areapicker('birthplace_area_id', array('model' => 'Area', 'value' => $birthplaceAreaId)); ?>
</fieldset>

<div class="controls">
	<?php if(!$WizardMode){ ?>
	<input type="submit" value="<?php echo __("Save"); ?>" class="btn_save btn_right" onclick="return Config.checkValidate();"/>
	<?php echo $this->Html->link(__('Cancel'), array('action' => 'view'), array('class' => 'btn_cancel btn_left')); ?>
	<?php }else{?>
		<?php if(!$this->Session->check('StudentId')){ 
		   echo $this->Form->submit(__('Cancel'), array('div'=>false, 'name'=>'submit', 'class'=>"btn_cancel btn_cancel_button btn_right"));
		 }
		if(!$wizardEnd){
			echo $this->Form->submit(__('Next'), array('div'=>false, 'name'=>'submit', 'name'=>'submit','class'=>"btn_save btn_left",'onclick'=>"return Config.checkValidate();")); 
		}else{
			echo $this->Form->submit(__('Finish'), array('div'=>false, 'name'=>'submit', 'name'=>'submit','class'=>"btn_save btn_left",'onclick'=>"return Config.checkValidate();")); 
		}
	}?>
</div>

<?php echo $this->Form->end(); ?>
<?php $this->end(); ?>
