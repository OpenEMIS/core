<?php
echo $this->Html->script('app.date', false);
echo $this->Html->script('institution_site', false);
echo $this->Html->script('config', false); 
?>
<script>
	$(document).ready(function() {
		Config.applyRule();
	});
</script>
<?php echo $this->element('breadcrumb'); ?>

<div id="site" class="content_wrapper edit add">
	<h1>
		<span><?php echo __('Overview'); ?></span>
		<?php
		echo $this->Html->link(__('View'), array('action' => 'view'), array('class' => 'divider'));
		echo $this->Html->link(__('History'), array('action' => 'history'),	array('class' => 'divider')); 
		?>
	</h1>
	
	<?php
	echo $this->Form->create('InstitutionSite', array(
		'url' => array('controller' => 'InstitutionSites', 'action' => 'edit'),
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
	));
	?>
	
	<?php $obj = @$data['InstitutionSite']; ?>
		
	<fieldset class="section_break">
		<legend><?php echo __('Information'); ?></legend>
		<div class="row">
			<div class="label"><?php echo __('Site Name'); ?></div>
			<div class="value"><?php echo $this->Form->input('name', array('value' => $obj['name'])); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Site Code'); ?></div>
			<div class="value"><?php echo $this->Form->input('code', array('value' => $obj['code'],
														    'onkeyup'=>"javascript:updateHiddenField(this, 'validate_institution_site_code');")); ?>
           		<input type="hidden" name="validate_institution_site_code" id="validate_institution_site_code" value="<?php echo $obj['code']; ?>"/>
			</div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Type'); ?></div>
			<div class="value">
			<?php echo $this->Form->input('institution_site_type_id', array(
				'options' => $type_options,
				'default' => $obj['institution_site_type_id']));
			?>
			</div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Ownership'); ?></div>
			<div class="value">
			<?php echo $this->Form->input('institution_site_ownership_id', array(
				'options' => $ownership_options,
				'default' => $obj['institution_site_ownership_id']));
			?>
			</div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Status'); ?></div>
			<div class="value">
			<?php echo $this->Form->input('institution_site_status_id', array(
				'options' => $status_options,
				'default' => $obj['institution_site_status_id']));
			?>
			</div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Date Opened'); ?></div>
			<div class="value"><?php echo $this->Utility->getDatePicker($this->Form, 'date_opened', array('desc' => true,'value' => $obj['date_opened'])); ?></div>
		</div>

		<div class="row">
			<div class="label"><?php echo __('Date Closed'); ?></div>
			<div class="value"><?php echo $this->Utility->getDatePicker($this->Form, 'date_closed', array('desc' => true,'value' => $obj['date_closed'])); ?></div>
		</div>
	</fieldset>
	<fieldset class="section_break">
		<legend><?php echo __('Area'); ?></legend>   
		<?php
		$ctr = 0; //pr($areadropdowns);
		
		foreach($levels as $levelid => $levelName){
			echo '<div class="row">
					<div class="label">'."$levelName".'</div>
					<div class="value">'. $this->Form->input('area_level_'.$ctr,array('style'=>'float:left','default'=>@$arealevel[$ctr]['id'],'options'=>$areadropdowns['area_level_'.$ctr]['options'])).
							($ctr == 0 ? $this->Form->input('area_id',array('type'=>'text','style'=>'display:none','value' => $obj['area_id'])):''). 
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
			<div class="value"><?php echo $this->Form->input('address', array('value' => $obj['address'], 'onkeyup' => 'utility.charLimit(this)')); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Postal Code'); ?></div>
			<div class="value"><?php echo $this->Form->input('postal_code', array('value' => $obj['postal_code'],
														    'onkeyup'=>"javascript:updateHiddenField(this, 'validate_institution_site_postal_code');")); ?>
           		<input type="hidden" name="validate_institution_site_postal_code" id="validate_institution_site_postal_code" value="<?php echo $obj['postal_code']; ?>"/>
			</div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Locality'); ?></div>
			<div class="value">
			<?php echo $this->Form->input('institution_site_locality_id', array(
				'options' => $locality_options,
				'default' => $obj['institution_site_locality_id']));
			?>
			</div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Latitude'); ?></div>
			<div class="value"><?php echo $this->Form->input('latitude', array('value' => $obj['latitude'])); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Longitude'); ?></div>
			<div class="value"><?php echo $this->Form->input('longitude', array('value' => $obj['longitude'])); ?></div>
		</div>
	</fieldset>
	
	<fieldset class="section_break">
		<legend><?php echo __('Contact'); ?></legend>
		<div class="row">
			<div class="label"><?php echo __('Contact Person'); ?></div>
			<div class="value"><?php echo $this->Form->input('contact_person', array('value' => $obj['contact_person'])); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Telephone'); ?></div>
			<div class="value"><?php echo $this->Form->input('telephone', array('value' => $obj['telephone'],
														    'onkeyup'=>"javascript:updateHiddenField(this, 'validate_institution_site_telephone');")); ?>
           		<input type="hidden" name="validate_institution_site_telephone" id="validate_institution_site_telephone" value="<?php echo $obj['telephone']; ?>"/>
			</div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Fax'); ?></div>
			<div class="value"><?php echo $this->Form->input('fax', array('value' => $obj['fax'],
														    'onkeyup'=>"javascript:updateHiddenField(this, 'validate_institution_site_fax');")); ?>
           		<input type="hidden" name="validate_institution_site_fax" id="validate_institution_site_fax" value="<?php echo $obj['fax']; ?>"/>
			</div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Email'); ?></div>
			<div class="value"><?php echo $this->Form->input('email', array('value' => $obj['email']));?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Website'); ?></div>
			<div class="value"><?php echo $this->Form->input('website', array('value' => $obj['website'])); ?></div>
		</div>
	</fieldset>
	
	<div class="controls view_controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" onclick="return Config.checkValidate();"/>
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'view'), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>
