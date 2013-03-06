<?php 
echo $this->Html->css('jquery_ui', 'stylesheet', array('inline' => false));
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('/Teachers/css/teachers', 'stylesheet', array('inline' => false));
echo $this->Html->script('/Teachers/js/qualifications', false);
?>

<?php echo $this->element('breadcrumb'); ?>
<div id="qualification" class="content_wrapper">

	<h1>
		<span><?php echo __('Qualifications'); ?></span>
		<!-- <a class="divider void" onClick="objTeacherQualifications.show('QualificationAdd')">Add</a> -->
        <?php 
        if($_edit) {
            echo $this->Html->link(__('Edit'), array('action' => 'qualificationsEdit'), array('class' => 'divider'));
        }
        ?>
	</h1>
	<?php echo $this->element('alert'); ?>

	<div class="table full_width">
		<div class="table_head">
			<div class="table_cell"><?php echo __('Date of Issue'); ?></div>
			<div class="table_cell"><?php echo __('Certificate'); ?></div>
			<div class="table_cell"><?php echo __('Certificate No.'); ?></div>
			<div class="table_cell"><?php echo __('Issued By'); ?></div>
		</div>
		
		<div class="table_body">
			<?php foreach($list as $obj): ?>
			<div class="table_row">
				<div class="table_cell"><?php echo $this->Utility->formatDate($obj['issue_date']); ?></div>
				<div class="table_cell"><?php echo $obj['certificate']; ?></div>
				<div class="table_cell"><?php echo $obj['certificate_no']; ?></div>
				<div class="table_cell"><?php echo $obj['institute']; ?></div>
			</div>
			<?php endforeach; ?>
		</div>
	</div>

    <?php
    echo $this->Form->create('TeacherQualification', array(
        'id' => 'TeacherQualification',
        'model' => 'TeacherQualification',
        'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
    ));
    ?>
    <?php echo $this->Form->end(); ?>

</div>