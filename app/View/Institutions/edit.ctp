<?php
echo $this->Html->css('institution', 'stylesheet', array('inline' => false));
echo $this->Html->script('app.date', false);
echo $this->Html->script('institution', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="institution" class="content_wrapper edit add">
	<h1>
		<span><?php echo __('Overview'); ?></span>
		<?php
		echo $this->Html->link(__('View'), array('action' => 'view'), array('class' => 'divider'));
		echo $this->Html->link(__('History'), array('action' => 'history'), array('class' => 'divider')); 
		?>
	</h1>
	
	<?php
	echo $this->Form->create('Institution', array(
		'url' => array('controller' => 'Institutions', 'action' => 'edit'),
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
	));
	?>
	
	<?php $obj = @$data['Institution']; ?>
	
	<fieldset class="section_break">
		<legend><?php echo __('Information'); ?></legend>
		<div class="row">
			<div class="label"><?php echo __('Institution Name'); ?></div>
			<div class="value"><?php echo $this->Form->input('name', array('value' => $obj['name'])); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Institution Code'); ?></div>
			<div class="value"><?php echo $this->Form->input('code', array('value' => $obj['code'])); ?></div>
		</div>
        <div class="row">
			<div class="label"><?php echo __('Sector'); ?></div>
			<div class="value">
			<?php echo $this->Form->input('institution_sector_id', array(
				'options' => $sector_options,
				'default' => $obj['institution_sector_id']));
			?>
			</div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Provider'); ?></div>
			<div class="value">
			<?php echo $this->Form->input('institution_provider_id', array(
				'options' => $provider_options,
				'default' => $obj['institution_provider_id']));
			?>
			</div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Status'); ?></div>
			<div class="value">
			<?php echo $this->Form->input('institution_status_id', array(
				'options' => $status_options,
				'default' => $obj['institution_status_id']));
			?>
			</div>
		</div>

		<div class="row">
			<div class="label"><?php echo __('Date Opened'); ?></div>
			<div class="value">
				<?php echo $this->Utility->getDatePicker($this->Form, 'date_opened', array('desc' => true,'value' => $obj['date_opened'])); ?>
			</div>
		</div>

		<div class="row">
			<div class="label"><?php echo __('Date Closed'); ?></div>
			<div class="value">
				<?php echo $this->Utility->getDatePicker($this->Form, 'date_closed', array('desc' => true,'value' => $obj['date_closed'])); ?>
			</div>
		</div>
	</fieldset>
	
	<fieldset class="section_break">
		<legend><?php echo __('Location'); ?></legend>
		<div class="row">
			<div class="label"><?php echo __('Address'); ?></div>
			<div class="value"><?php echo $this->Form->input('address', array('value' => $obj['address'], 'onkeyup' => 'utility.charLimit(this)')); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Postal Code'); ?></div>
			<div class="value"><?php echo $this->Form->input('postal_code', array('value' => $obj['postal_code'])); ?></div>
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
			<div class="value"><?php echo $this->Form->input('telephone', array('value' => $obj['telephone'])); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Fax'); ?></div>
			<div class="value"><?php echo $this->Form->input('fax', array('value' => $obj['fax'])); ?></div>
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
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'view'), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>