<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->css('/Staff/css/staff', 'stylesheet', array('inline' => false));
echo $this->Html->script('app.date', false);
echo $this->Html->script('institution_site', false);
echo $this->Html->script('institution_site_staff', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="staffEdit" class="content_wrapper edit">
	<h1>
		<span><?php echo __('Staff Information'); ?></span>
		<?php 
		$obj = $data['Staff'];
		if($_edit) {
			echo $this->Html->link(__('View'), array('action' => 'staffView', $obj['id']), array('class' => 'divider'));
		}
		?>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<fieldset class="section_break" id="general">
		<legend><?php echo __('General'); ?></legend>
		<?php
		    $path = (isset($obj['photo_content']) && !empty($obj['photo_content']) && !stristr($obj['photo_content'], 'null'))? "/Staff/fetchImage/{$obj['id']}":"/Staff/img/default_staff_profile.jpg";
		    echo $this->Html->image($path, array('class' => 'profile_image', 'alt' => '90x115'));
		?>
		<div class="row">
			<div class="label"><?php echo __('Identification No.'); ?></div>
			<div class="value">
				<?php
				if($_view_details) {
					echo $this->Html->link($obj['identification_no'], array('controller' => 'Staff', 'action' => 'viewStaff', $obj['id']), array('class' => 'link_back'));
				} else {
					echo $obj['identification_no'];
				}
				?>
			</div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('First Name'); ?></div>
			<div class="value"><?php echo $obj['first_name']; ?></div>
		</div>
                <div class="row">
			<div class="label"><?php echo __('Middle Name'); ?></div>
			<div class="value"><?php echo $obj['middle_name']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Last Name'); ?></div>
			<div class="value"><?php echo $obj['last_name']; ?></div>
		</div>
                <div class="row">
			<div class="label"><?php echo __('Preferred Name'); ?></div>
			<div class="value"><?php echo $obj['preferred_name']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Gender'); ?></div>
			<div class="value"><?php echo $this->Utility->formatGender($obj['gender']); ?></div>
		</div>

		<div class="row">
			<div class="label"><?php echo __('Date of Birth'); ?></div>
			<div class="value"><?php echo $this->Utility->formatDate($obj['date_of_birth']); ?></div>
		</div>
	</fieldset>

	<?php
	echo $this->Form->create('InstitutionSiteStaff', array(
		'inputDefaults' => array('label' => false, 'div' => false, 'autocomplete' => 'off'),
		'url' => array('controller' => 'InstitutionSites', 'action' => 'staffEdit', $obj['id'])
	));
	$fieldName = 'data[InstitutionSiteStaff][%s][%s]';
	?>
	<fieldset class="section_break" id="employment">
		<legend><?php echo __('Location'); ?></legend>
		<div class="table full_width" style="margin-top: 10px;">
			<div class="table_head">
				<div class="table_cell" style="width: 150px;"><?php echo __('Position'); ?></div>
				<div class="table_cell" style="width: 280px;"><?php echo __('Details'); ?></div>
				<div class="table_cell"><?php echo __('FTE'); ?></div>
				<div class="table_cell"><?php echo __('Status'); ?></div>
				<div class="table_cell cell_icon_action"></div>
			</div>
			
			<div class="table_body">
				<?php foreach($positions as $i => $pos) { ?>
				<div class="table_row" row-id="<?php echo $i; ?>">
					<?php
					echo $this->Form->hidden($i.'.id', array('class' => 'key', 'value' => $pos['InstitutionSiteStaff']['id']));
					?>
					<div class="table_cell">
						<div class="table_cell_row"><?php echo $pos['StaffCategory']['name']; ?></div>
						<div class="table_cell_row"><?php echo $pos['InstitutionSiteStaff']['position_no']; ?></div>
					</div>
					<div class="table_cell">
						<div class="table_cell_row">
							<div class="label"><?php echo __('From'); ?></div>
							<?php 
							echo $this->Utility->getDatePicker($this->Form, $i . 'start_date', 
								array(
									'name' => sprintf($fieldName, $i, 'start_date'),
									'value' => $pos['InstitutionSiteStaff']['start_date'],
									'endDateValidation' => $i . 'end_date'
								));
							?>
						</div>
						<div class="table_cell_row">
							<div class="label"><?php echo __('To'); ?></div>
							<?php 
							echo $this->Utility->getDatePicker($this->Form, $i . 'end_date', 
								array(
									'name' => sprintf($fieldName, $i, 'end_date'),
									'emptySelect' => true,
									'value' => $pos['InstitutionSiteStaff']['end_date'],
									'endDateValidation' => $i . 'end_date',
									'yearAdjust' => 1
								));
							?>
						</div>
					</div>
					<div class="table_cell">
						<?php
						echo $this->Form->input($i . '.FTE', array(
							'type' => 'text',
							'label' => false,
							'div' => false,
							'class' => 'default',
							'style' => 'width: 90%;',
							'maxlength' => 3,
							'name' => sprintf($fieldName, $i, 'FTE'),
							'value' => $pos['InstitutionSiteStaff']['FTE'],
							'onkeypress' => 'return utility.floatCheck(event)'
						));
						?>
					</div>
					<div class="table_cell">
						<?php
						echo $this->Form->input($i . '.staff_status_id', array(
							'label' => false,
							'div' => false,
							'style' => 'width: 100%',
							'options' => $statusOptions,
							'value' => $pos['InstitutionSiteStaff']['staff_status_id']
						));
						?>
					</div>
					<div class="table_cell">
						<div class="table_cell_row"><span class="icon_delete" onclick="InstitutionSiteStaff.deletePosition(this);"></span></div>
					</div>
				</div>
				<?php } ?>
			</div>
		</div>
		<?php if($_add) { ?>
		<div class="row" style="margin-left: 0;">
			<a class="void icon_plus" url="InstitutionSites/staffAddPosition/"><?php echo __('Add').' '.__('Position'); ?></a>
		</div>
		<?php } ?>
	</fieldset>
	
	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'staffView', $obj['id']), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>
