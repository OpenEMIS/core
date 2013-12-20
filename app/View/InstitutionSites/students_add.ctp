<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->css('search', 'stylesheet', array('inline' => false));
echo $this->Html->css('webkit_scrollbar', 'stylesheet', array('inline' => false));

echo $this->Html->script('app.date', false);
echo $this->Html->script('institution_site_students', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="studentsAdd" class="content_wrapper edit">
	<h1>
		<span><?php echo __('Add Student'); ?></span>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<fieldset class="section_group" id="search">
		<legend><?php echo __('Search'); ?></legend>
		
		<div class="row">
			<div class="search_wrapper">
				<?php 
					echo $this->Form->input('SearchField', array(
						'id' => 'SearchField',
						'label' => false,
						'div' => false,
						'class' => 'default',
						'placeholder' => __('Identification No, First Name or Last Name'),
						'onkeypress' => 'return InstitutionSiteStudents.search(this, event)'
					));
				?>
				<span class="icon_clear" onClick="$('#SearchField').val('')">X</span>
			</div>
			<span class="left icon_search" url="InstitutionSites/studentsSearch/" onClick="InstitutionSiteStudents.search(this)"></span>
		</div>
		
		<div class="table_scrollable">
			<div class="table table_header">
				<div class="table_head">
					<div class="table_cell cell_id_no"><?php echo __('Identification No'); ?></div>
					<div class="table_cell"><?php echo __('First Name'); ?></div>
                                        <div class="table_cell"><?php echo __('Middle Name'); ?></div>
					<div class="table_cell"><?php echo __('Last Name'); ?></div>
				</div>
			</div>
			<div class="list_wrapper hidden" limit="4" style="height: 98px;">
				<div class="table allow_hover">
					<div class="table_body"></div>
				</div>
			</div>
		</div>
	</fieldset>
	
	<?php
	echo $this->Form->create('InstitutionSiteStudent', array(
		'id' => 'submitForm',
		'onsubmit' => 'return InstitutionSiteStudents.validateStudentAdd()',
		'inputDefaults' => array('label' => false, 'div' => false, 'autocomplete' => 'off'),
		'url' => array('controller' => 'InstitutionSites', 'action' => 'studentsSave')
	));
	echo $this->Form->hidden('student_id', array('id' => 'StudentId', 'value' => 0, 'autocomplete' => 'off'));
	?>
	
	<div class="info">
		<div class="row">
			<div class="label"><?php echo __('Identification No'); ?></div>
			<div class="value" id="IdentificationNo"></div>
		</div>
		
		<div class="row">
			<div class="label"><?php echo __('First Name'); ?></div>
			<div class="value" id="FirstName"></div>
		</div>
            
                <div class="row">
			<div class="label"><?php echo __('Middle Name'); ?></div>
			<div class="value" id="MiddleName"></div>
		</div>
		
		<div class="row">
			<div class="label"><?php echo __('Last Name'); ?></div>
			<div class="value" id="LastName"></div>
		</div>
            
                <div class="row">
			<div class="label"><?php echo __('Preferred Name'); ?></div>
			<div class="value" id="PreferredName"></div>
		</div>
		
		<div class="row">
			<div class="label"><?php echo __('Gender'); ?></div>
			<div class="value" id="Gender"></div>
		</div>
		
		<div class="row">
			<div class="label"><?php echo __('Year'); ?></div>
			<div class="value">
				<?php 
				echo $this->Form->input('school_year_id', array(
					'class' => 'default',
					'options' => $yearOptions,
					'url' => 'InstitutionSites/programmesOptions',
					'onchange' => 'InstitutionSiteStudents.getProgrammeOptions(this)'
				));
				?>
			</div>
		</div>
		
		<div class="row">
			<div class="label"><?php echo __('Programme'); ?></div>
			<div class="value"><?php echo $this->Form->input('institution_site_programme_id', array('id' => 'InstitutionSiteProgrammeId', 'class' => 'default', 'options' => $programmeOptions)); ?></div>
		</div>
		
		<div class="row">
			<div class="label"><?php echo __('Status'); ?></div>
			<div class="value"><?php echo $this->Form->input('student_status_id', array('class' => 'default', 'options' => $statusOptions)); ?></div>
		</div>
		
		<div class="row">
			<div class="label"><?php echo __('Start Date'); ?></div>
			<div class="value"><?php echo $this->Form->input('start_date', array('type' => 'date', 'dateFormat' => 'DMY', 'minYear' => $minYear, 'maxYear' => $maxYear, 'empty' => __('Select'))); ?></div>
		</div>
	</div>
	
	<div class="controls">
		<input type="submit" value="<?php echo __('Add'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'students'), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	<?php echo $this->Form->end(); ?>
</div>