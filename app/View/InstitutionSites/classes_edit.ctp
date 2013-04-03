<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));

echo $this->Html->script('institution_site_classes', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="classes" class="content_wrapper edit">
    <h1>
        <span><?php echo $className; ?></span>
		<?php
		echo $this->Html->link(__('View'), array('action' => 'classesView', $classId), array('class' => 'divider'));
		?>
    </h1>
    <?php echo $this->element('alert'); ?>
	
	<?php 
	echo $this->Form->create('InstitutionSiteClass', array(
		'url' => array('controller' => 'InstitutionSite', 'action' => 'classesEdit'),
		'inputDefaults' => array('label' => false, 'div' => false)
	));
	$i = 0;
	?>
	
	<div class="table full_width" style="margin-bottom: 20px;">
		<div class="table_head">
			<div class="table_cell cell_year"><?php echo __('Year'); ?></div>
			<div class="table_cell"><?php echo __('Grade'); ?></div>
		</div>
		<div class="table_body">
			<div class="table_row">
				<div class="table_cell cell_year"><?php echo $year; ?></div>
				<div class="table_cell">
				<?php foreach($grades as $id => $name) { $i++; ?>
					<div class="table_cell_row <?php echo $i==sizeof($grades) ? 'last' : ''; ?>"><?php echo $name; ?></div>
				<?php } ?>
				</div>
			</div>
		</div>
	</div>
	
	<?php foreach($grades as $id => $name) { ?>
	
	<fieldset class="section_group">
		<legend>
			<span><?php echo $name ?></span>
			<!--?php echo $this->Html->link(__('Remove All'), array('action' => 'studentsRemove'), array('class' => 'divider')); ?-->
		</legend>
		
		<div class="table">
			<div class="table_head">
				<div class="table_cell cell_id_no"><?php echo __('Identification No'); ?></div>
				<div class="table_cell"><?php echo __('Name'); ?></div>
				<div class="table_cell cell_gender">Gender</div>
				<div class="table_cell cell_delete"></div>
			</div>
			
			<div class="table_body" url="InstitutionSites/classesStudentAjax/<?php echo $id; ?>">
				<?php if(isset($students[$id])) { ?>
				<?php foreach($students[$id] as $obj) { ?>
				<div class="table_row" student-id="<?php echo $obj['id']; ?>">
					<div class="table_cell"><?php echo $obj['identification_no']; ?></div>
					<div class="table_cell"><?php echo $obj['first_name'] . ' ' . $obj['last_name']; ?></div>
					<div class="table_cell"><?php echo $this->Utility->formatGender($obj['gender']); ?></div>
					<div class="table_cell">
						<?php echo $this->Utility->getDeleteControl(array(
							'onclick' => 'InstitutionSiteClasses.deleteStudent(this)',
							'onDelete' => false
						));
						?>
					</div>
				</div>
				<?php } // end for ?>
				<?php } // end if ?>
			</div>
		</div>
		
		<div class="row">
			<?php $url = 'InstitutionSites/classesAddStudentRow/'.$year.'/'.$id; ?>
			<a class="void icon_plus" url="<?php echo $url; ?>"><?php echo __('Add').' '.__('Student'); ?></a>
		</div>
	</fieldset>
	
	<?php } ?>
	
	<!--div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'classesList'), array('class' => 'btn_cancel btn_left')); ?>
	</div-->
</div>
