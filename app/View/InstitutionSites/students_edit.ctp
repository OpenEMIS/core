<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('search', 'stylesheet', array('inline' => false));
echo $this->Html->css('pagination', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));

echo $this->Html->script('institution_site_students', false);
echo $this->Html->script('jquery.scrollTo', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="students" class="content_wrapper edit">
	<?php
	echo $this->Form->create('InstitutionSiteProgrammeStudent', array(
		'id' => 'submitForm',
		'inputDefaults' => array('label' => false, 'div' => false, 'autocomplete' => 'off'),
		'url' => array('controller' => 'InstitutionSites', 'action' => 'studentsEdit')
	));
	?>
	<h1>
		<span><?php echo __('Edit Programmes'); ?></span>
		<?php echo $this->Html->link(__('View'), array('action' => 'studentsView', $selectedYear, $selectedProgramme), array('class' => 'divider')); ?>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<div class="row">
		<div class="label"><?php echo __('Year'); ?></div>
		<div class="value">
			<?php
			echo $this->Form->input('school_year_id', array(
				'id' => 'SchoolYearId',
				'options' => $yearOptions,
				'default' => $selectedYear,
				'onchange' => 'InstitutionSiteStudents.navigate()'
			));
			?>
		</div>
	</div>
	
	<div class="row">
		<div class="label"><?php echo __('Programme'); ?></div>
		<div class="value">
			<?php
			echo $this->Form->input('institution_site_programme_id', array(
				'id' => 'InstitutionSiteProgrammeId',
				'class' => 'select',
				'options' => $programmeOptions,
				'default' => $selectedProgramme,
				'onchange' => 'InstitutionSiteStudents.navigate()'
			));
			?>
		</div>
	</div>
	
	<?php if($_add) { ?>
	<fieldset class="section_group" id="search_group">
		<legend><?php echo __('Search'); ?></legend>
		
		<div class="row">
			<div class="search_wrapper">
				<?php 
					echo $this->Form->input('SearchField', array(
						'id' => 'SearchField',
						'value' => '',
						'class' => 'default',
						'onkeypress' => 'InstitutionSiteStudents.doSearch(event)',
						'placeholder' => __('Student Identification No, First Name or Last Name')
					));
				?>
				<span class="icon_clear" onClick="InstitutionSiteStudents.clearSearch(this)">X</span>
			</div>
			<span class="left icon_search" url="InstitutionSites/studentsSearch?master" onClick="InstitutionSiteStudents.search(this)"></span>
		</div>
		
		<div class="table_scrollable" url="InstitutionSites/studentsAddToProgramme">
			<div class="table table_header">
				<div class="table_head">
					<div class="table_cell cell_id_no"><?php echo __('Identification No'); ?></div>
					<div class="table_cell"><?php echo __('Name'); ?></div>
					<div class="table_cell cell_icon_action"></div>
				</div>
			</div>
			<div class="list_wrapper hidden" limit="4">
				<div class="table">
					<div class="table_body"></div>
				</div>
			</div>
		</div>
	</fieldset>
	<?php } ?>
	
	<?php echo $this->Form->end(); ?>
	
	<fieldset class="section_group" id="student_group">
		<legend>
			<span><?php echo __('Students'); ?></span>
			<?php if($_delete) { ?>
			<a class="divider void" onclick="InstitutionSiteStudents.removeStudentFromList(this)"><?php echo __('Remove All'); ?></a>
			<?php } ?>
		</legend>
		
		<?php
		echo $this->Form->create('InstitutionSiteProgrammeStudent', array(
			'inputDefaults' => array('label' => false, 'div' => false, 'autocomplete' => 'off'),
			'url' => array('controller' => 'InstitutionSites', 'action' => 'studentsEdit')
		));
		?>
		<div class="table_scrollable <?php echo sizeof($data) > 7 ? 'scroll_active' : ''; ?>" url="InstitutionSites/studentsRemoveFromProgramme">
			<div class="table table_header">
				<div class="table_head">
					<div class="table_cell cell_id_no"><?php echo __('Identification No'); ?></div>
					<div class="table_cell"><?php echo __('Name'); ?></div>
					<div class="table_cell cell_start_date"><?php echo __('Start Date'); ?></div>
					<div class="table_cell cell_datepicker"><?php echo __('Completion Date'); ?></div>
					<?php if($_delete) { ?>
					<div class="table_cell cell_icon_action"></div>
					<?php } ?>
				</div>
			</div>
				
			<div class="list_wrapper" limit="7">
				<div class="table">
					<div class="table_body">
						<?php foreach($data as $i => $obj) { ?>
						<div class="table_row" student-id="<?php echo $obj['student_id']; ?>">
							<?php
							echo $this->Form->hidden($i.'.id', array('value' => $obj['id']));
							echo $this->Form->hidden($i.'.start_date.year', array('value' => $yearOptions[$selectedYear]));
							?>
							<div class="table_cell cell_id_no"><?php echo $obj['identification_no']; ?></div>
							<div class="table_cell"><?php echo $obj['first_name'] . ' ' . $obj['last_name']; ?></div>
							<div class="table_cell cell_start_date center">
								<?php
								echo $this->Form->input($i.'.start_date', array(
									'type' => 'date',
									'class' => 'select',
									'dateFormat' => 'DM',
									'selected' => $obj['start_date']
								));
								?>
							</div>
							<div class="table_cell cell_datepicker center">
								<?php
								echo $this->Form->input($i.'.end_date', array(
									'type' => 'date',
									'class' => 'select',
									'dateFormat' => 'DMY',
									'selected' => $obj['end_date'],
									'minYear' => $obj['year'],
									'maxYear' => $obj['year'] + 10,
									'orderYear' => 'asc'
								));
								?>
							</div>
							<?php if($_delete) { ?>
							<div class="table_cell cell_icon_action">
								<span class="icon_delete" onclick="InstitutionSiteStudents.removeStudentFromList(this)"></span>
							</div>
							<?php } ?>
						</div>
						<?php } ?>
					</div>
				</div>
			</div> <!-- end table -->
		</div> <!-- end table scrollable -->
		<?php echo $this->Form->end(); ?>
	</fieldset>
	
	<div class="controls">
		<input type="button" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php 
		echo $this->Html->link(__('Cancel'), 
			array('action' => 'studentsView', $selectedYear, $selectedProgramme), 
			array('class' => 'btn_cancel btn_left')); 
		?>
	</div>
	
</div>