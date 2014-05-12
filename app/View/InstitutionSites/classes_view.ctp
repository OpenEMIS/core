<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->script('search', false);
?>

<?php echo $this->element('breadcrumb'); ?>
<div id="classes" class="content_wrapper">
    <h1>
        <span><?php echo $className; ?></span>
		<?php
		echo $this->Html->link(__('List'), array('action' => 'classes', $yearId), array('class' => 'divider'));
		if($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'classesEdit', $classId), array('class' => 'divider'));
		}
		if($_delete) {
			echo $this->Html->link(__('Delete'), array('action' => 'classesDelete', $classId), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
		}
		if($_accessControl->check($this->params['controller'], 'classesAttendance')) {
            echo $this->Html->link(__('Attendance'), array('action' => 'classesAttendance'), array('class' => 'divider'));
        }
		if($_accessControl->check($this->params['controller'], 'classesAssessments')) {
            echo $this->Html->link(__('Results'), array('action' => 'classesAssessments', $classId), array('class' => 'divider'));
        }
		?>
    </h1>
    <?php echo $this->element('alert'); ?>
	
	<?php
	$i=0;
	?>
	
	<div class="table full_width" style="margin-bottom: 20px;">
		<div class="table_head">
			<div class="table_cell cell_year"><?php echo __('Year'); ?></div>
			<div class="table_cell"><?php echo __('Grade'); ?></div>
                        <div class="table_cell"><?php echo __('Seats'); ?></div>
                        <div class="table_cell"><?php echo __('Shift'); ?></div>
		</div>
		<div class="table_body">
			<div class="table_row">
				<div class="table_cell cell_year"><?php echo $year; ?></div>
				<div class="table_cell">
				<?php foreach($grades as $id => $name) { $i++; ?>
					<div class="table_cell_row <?php echo $i==sizeof($grades) ? 'last' : ''; ?>"><?php echo $name; ?></div>
				<?php } ?>
				</div>
                                <div class="table_cell"><?php echo $no_of_seats; ?></div>
                                <div class="table_cell"><?php echo $no_of_shifts; ?></div>
			</div>
		</div>
	</div>

	<fieldset class="section_group">
    		<legend><?php echo __('Subjects'); ?></legend>
    		<div class="table">
    			<div class="table_head">
    				<div class="table_cell cell_year"><?php echo __('Code'); ?></div>
                    <div class="table_cell"><?php echo __('Name'); ?></div>
                    <div class="table_cell cell_category"><?php echo __('Grade'); ?></div>
    			</div>
    			<div class="table_body">
    				<?php foreach($subjects as $obj) { ?>
    				<div class="table_row">
    					<div class="table_cell"><?php echo $obj['EducationSubject']['code']; ?></div>
                        <div class="table_cell"><?php echo $obj['EducationSubject']['name']; ?></div>
                        <div class="table_cell"><?php echo $obj['EducationGrade']['name']; ?></div>
    				</div>
    				<?php } ?>
    			</div>
    		</div>
    	</fieldset>
	
	<fieldset class="section_group">
		<legend><?php echo __('Teachers'); ?></legend>
		<div class="table">
			<div class="table_head">
				<div class="table_cell cell_id_no"><?php echo __('OpenEMIS ID'); ?></div>
				<div class="table_cell"><?php echo __('Name'); ?></div>
			</div>
			<div class="table_body">
				<?php foreach($teachers as $obj) { ?>
				<div class="table_row">
					<div class="table_cell"><?php echo $obj['Teacher']['identification_no']; ?></div>
					<div class="table_cell"><?php echo $obj['Teacher']['first_name'] . ' ' . $obj['Teacher']['last_name']; ?></div>
				</div>
				<?php } ?>
			</div>
		</div>
	</fieldset>
	
	<fieldset class="section_group">
		<legend><?php echo __('Students'); ?></legend>
		<?php foreach($grades as $id => $name) { ?>
		
		<fieldset class="section_break">
			<legend><?php echo $name ?></legend>
			
			<div class="table">
				<div class="table_head">
					<div class="table_cell cell_id_no"><?php echo __('OpenEMIS ID'); ?></div>
					<div class="table_cell"><?php echo __('Name'); ?></div>
					<div class="table_cell cell_category"><?php echo __('Category'); ?></div>
				</div>
				
				<div class="table_body">
					<?php if(isset($students[$id])) { ?>
					<?php foreach($students[$id] as $obj) { ?>
					<div class="table_row">
						<div class="table_cell"><?php echo $obj['identification_no']; ?></div>
						<div class="table_cell"><?php echo $obj['first_name'] . ' ' . $obj['middle_name'] . ' ' . $obj['last_name']; ?></div>
						<div class="table_cell"><?php echo $obj['category']; ?></div>
					</div>
					<?php } // end for ?>
					<?php } // end if ?>
				</div>
			</div>
		</fieldset>
		
		<?php } ?>
	</fieldset>
</div>
