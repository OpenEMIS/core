<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('/Staff/css/staff', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="staff" class="content_wrapper">	
	<h1>
		<span><?php echo __('Staff Information'); ?></span>
		<?php 
		$obj = $data['Staff'];
		if($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'staffEdit', $obj['id']), array('class' => 'divider'));
		}
		echo $this->Html->link(__('Academic'), array('action' => 'staffCustFieldYrView', $obj['id']), array('class' => 'divider'));
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
			<div class="label"><?php echo __('Last Name'); ?></div>
			<div class="value"><?php echo $obj['last_name']; ?></div>
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
	
	<fieldset class="section_break">
		<legend><?php echo __('Employment'); ?></legend>
		<div class="table full_width" style="margin-top: 10px;">
			<div class="table_head">
				<div class="table_cell" style="width: 280px;"><?php echo __('Position'); ?></div>
				<div class="table_cell"><?php echo __('From'); ?></div>
				<div class="table_cell"><?php echo __('To'); ?></div>
				<div class="table_cell"><?php echo __('Salary'); ?></div>
			</div>
			
			<div class="table_body">
				<?php foreach($positions as $obj) { ?>
				<div class="table_row">
					<div class="table_cell"><?php echo $obj['StaffCategory']['name']; ?></div>
					<div class="table_cell center"><?php echo $this->Utility->formatDate($obj['InstitutionSiteStaff']['start_date']); ?></div>
					<div class="table_cell center">
						<?php
						$endDate = $obj['InstitutionSiteStaff']['end_date'];
						echo is_null($endDate) ? __('Current') : $this->Utility->formatDate($endDate);
						?>
					</div>
					<div class="table_cell cell_number"><?php echo $obj['InstitutionSiteStaff']['salary']; ?></div>
				</div>
				<?php } ?>
			</div>
		</div>
	</fieldset>
	
	<?php echo $this->Form->end(); ?>
</div>
