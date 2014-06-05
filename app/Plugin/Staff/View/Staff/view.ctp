<?php 
echo $this->Html->css('/Staff/css/staff', 'stylesheet', array('inline' => false));
echo $this->Html->script('/Staff/js/staff', false);
 
$this->extend('/Elements/layout/container');
$this->assign('contentId', 'student');
$this->assign('contentHeader', __('Overview'));
$this->start('contentActions');
		if ($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'edit'), array('class' => 'divider'));
		}
		if($_delete) {
			echo $this->Html->link(__('Delete'), array('action' => 'delete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
		}
		echo $this->Html->link(__('History'), array('action' => 'history'), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
$obj = $data['Staff'];
?>
	
	<fieldset class="section_break" id="general">
		<legend><?php echo __('Information'); ?></legend>
		<div class='profile_image'>
		<?php
		    $path = (isset($obj['photo_content']) && !empty($obj['photo_content']) && !stristr($obj['photo_content'], 'null'))? "/Staff/fetchImage/{$obj['id']}":"/Staff/img/default_staff_profile.jpg";
		    echo $this->Html->image($path, array('alt' => '90x115'));
		?>
		</div>
		<div class='profile_text'>
		<?php // echo $this->Html->image("/Staff/img/default_staff_profile.jpg", array('class' => 'profile_image', 'alt' => '90x115')); ?>
		<div class="row">
			<div class="col-md-2"><?php echo __('OpenEMIS ID'); ?></div>
			<div class="col-md-6"><?php echo $obj['identification_no']; ?></div>
		</div>
		<div class="row">
			<div class="col-md-2"><?php echo __('First Name'); ?></div>
			<div class="col-md-6"><?php echo $obj['first_name']; ?></div>
		</div>
                <div class="row">
			<div class="col-md-2"><?php echo __('Middle Name'); ?></div>
			<div class="col-md-6"><?php echo $obj['middle_name']; ?></div>
		</div>
		<div class="row">
			<div class="col-md-2"><?php echo __('Last Name'); ?></div>
			<div class="col-md-6"><?php echo $obj['last_name']; ?></div>
		</div>
                <div class="row">
			<div class="col-md-2"><?php echo __('Preferred Name'); ?></div>
			<div class="col-md-6"><?php echo $obj['preferred_name']; ?></div>
		</div>
		<div class="row">
			<div class="col-md-2"><?php echo __('Gender'); ?></div>
			<div class="col-md-6"><?php echo $this->Utility->formatGender($obj['gender']); ?></div>
		</div>

		<div class="row">
			<div class="col-md-2"><?php echo __('Date of Birth'); ?></div>
			<div class="col-md-6"><?php echo $this->Utility->formatDate($obj['date_of_birth']); ?></div>
		</div>
                <?php /*<div class="row">
			<div class="col-md-2"><?php echo __('Date of Death'); ?></div>
			<div class="col-md-6"><?php echo $this->Utility->formatDate($obj['date_of_death']); ?></div>
		</div>*/ ?>
			<br class="clear_both"/>
		</div>
	</fieldset>

	<fieldset class="section_break">
		<legend><?php echo __('Address'); ?></legend>
		<div class="row">
			<div class="col-md-2"><?php echo __('Address'); ?></div>
			<div class="col-md-6 address"><?php echo nl2br($obj['address']); ?></div>
		</div>
		<div class="row">
			<div class="col-md-2"><?php echo __('Postal Code'); ?></div>
			<div class="col-md-6"><?php echo $obj['postal_code']; ?></div>
		</div>
	</fieldset>
	
	<?php if($obj['address_area_id']>0){ ?>
    </fieldset>
        <fieldset class="section_break">
        <legend><?php echo __('Address Area'); ?></legend>
        <?php echo @$this->Utility->showArea($this->Form, 'address_area_id',$obj['address_area_id'], array()); ?>
    </fieldset>
    <?php } ?>

    <?php if($obj['birthplace_area_id']>0){ ?>
    <fieldset class="section_break">
        <legend><?php echo __('Birth Place Area'); ?></legend>
        <?php echo @$this->Utility->showArea($this->Form, 'birthplace_area_id',$obj['birthplace_area_id'], array()); ?>
    </fieldset>
    <?php } ?>
	
	
	<?php echo $this->Form->end(); ?>
<?php $this->end(); ?>
