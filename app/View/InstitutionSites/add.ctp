<?php
echo $this->Html->css('institution', 'stylesheet', array('inline' => false));
echo $this->Html->script('app.date', false);
echo $this->Html->script('institution', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="site" class="content_wrapper edit add">
	<h1><?php echo __('Add New Institution Site'); ?></h1>

	<?php
	echo $this->Form->create('InstitutionSite', array(
		'url' => array('controller' => 'InstitutionSites', 'action' => 'add'),
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default')
	));
	echo $this->Form->hidden('institution_id',array('value'=>$institutionId));
	?>

	<fieldset class="section_break">
		<legend><?php echo __('General'); ?></legend>
		<div class="row">
			<div class="label"><?php echo __('Site Name'); ?></div>
			<div class="value"><?php echo $this->Form->input('name'); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Site Code'); ?></div>
			<div class="value"><?php echo $this->Form->input('code'); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Type'); ?></div>
			<div class="value"><?php echo $this->Form->input('institution_site_type_id', array('options'=>$type_options)); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Ownership'); ?></div>
			<div class="value"><?php echo $this->Form->input('institution_site_ownership_id', array('options'=>$ownership_options)); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Status'); ?></div>
			<div class="value"><?php echo $this->Form->input('institution_site_status_id', array('options'=>$status_options)); ?></div>
		</div>

		<div class="row">
			<div class="label"><?php echo __('Date Opened'); ?></div>
			<div class="value"><?php echo $this->Utility->getDatePicker($this->Form, 'date_opened'); ?></div>
		</div>

		<div class="row">
			<div class="label"><?php echo __('Date Closed'); ?></div>
			<div class="value"><?php echo $this->Utility->getDatePicker($this->Form, 'date_closed'); ?></div>
		</div>

	</fieldset>
	<fieldset class="section_break">
		<legend><?php echo __('Area'); ?></legend>
		<?php
		$ctr = 0;
		foreach($levels as $levelid => $levelName){
			echo '<div class="row">
					<div class="label">'.__("$levelName").'</div>
					<div class="value">'. $this->Form->input('area_level_'.$ctr,array('class' => 'form-error default', 'default'=>@$arealevel[$ctr]['id'],'options'=>(isset($areadropdowns['area_level_'.$ctr]['options'])?$areadropdowns['area_level_'.$ctr]['options']:($ctr == 0?$highestLevel:array('0'=>'--'.__('Select').'--'))))).
							($ctr == 0 ? $this->Form->input('area_id',array('type'=>'text','style'=>'display:none')):''). 
					'</div>
				</div>';
			$ctr++;
		}
		?>
	</fieldset>
	
	<fieldset class="section_break">
		<legend><?php echo __('Location'); ?></legend>
		<div class="row">
			<div class="label"><?php echo __('Address'); ?></div>
			<div class="value"><?php echo $this->Form->input('address', array('onkeyup' => 'utility.charLimit(this)')); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Postal Code'); ?></div>
			<div class="value"><?php echo $this->Form->input('postal_code'); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Locality'); ?></div>
			<div class="value"><?php echo $this->Form->input('institution_site_locality_id', array('options'=>$locality_options)); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Latitude'); ?></div>
			<div class="value"><?php echo $this->Form->input('latitude'); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Longitude'); ?></div>
			<div class="value"><?php echo $this->Form->input('longitude'); ?></div>
		</div>
	</fieldset>
	
	<fieldset class="section_break">
		<legend><?php echo __('Contact'); ?></legend>
		<div class="row">
			<div class="label"><?php echo __('Contact Person'); ?></div>
			<div class="value"><?php echo $this->Form->input('contact_person'); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Telephone'); ?></div>
			<div class="value"><?php echo $this->Form->input('telephone'); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Fax'); ?></div>
			<div class="value"><?php echo $this->Form->input('fax'); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Email'); ?></div>
			<div class="value"><?php echo $this->Form->input('email'); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Website'); ?></div>
			<div class="value"><?php echo $this->Form->input('website'); ?></div>
		</div>
	</fieldset>
    
	<div class="controls view_controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" onClick="return objInstitution.addSite();" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'index'), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	<?php echo $this->Form->end(); ?>
</div>