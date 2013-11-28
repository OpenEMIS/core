<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->css('search', 'stylesheet', array('inline' => false));
echo $this->Html->css('webkit_scrollbar', 'stylesheet', array('inline' => false));

echo $this->Html->script('app.date', false);
echo $this->Html->script('institution_site_teachers', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="teachersAdd" class="content_wrapper edit">
	<h1>
		<span><?php echo __('Add Teacher'); ?></span>
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
						'onkeypress' => 'return InstitutionSiteTeachers.search(this, event)'
					));
				?>
				<span class="icon_clear" onClick="$('#SearchField').val('')">X</span>
			</div>
			<span class="left icon_search" url="InstitutionSites/teachersSearch/" onClick="InstitutionSiteTeachers.search(this)"></span>
		</div>
		
		<div class="table_scrollable">
			<div class="table table_header">
				<div class="table_head">
					<div class="table_cell cell_id_no"><?php echo __('Identification No'); ?></div>
					<div class="table_cell"><?php echo __('First Name'); ?></div>
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
	echo $this->Form->create('InstitutionSiteTeacher', array(
		'id' => 'submitForm',
		'onsubmit' => 'return InstitutionSiteTeachers.validateTeacherAdd()',
		'inputDefaults' => array('label' => false, 'div' => false, 'autocomplete' => 'off'),
		'url' => array('controller' => 'InstitutionSites', 'action' => 'teachersSave')
	));
	echo $this->Form->hidden('teacher_id', array('id' => 'TeacherId', 'autocomplete' => 'off'));
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
			<div class="label"><?php echo __('Last Name'); ?></div>
			<div class="value" id="LastName"></div>
		</div>
		
		<div class="row">
			<div class="label"><?php echo __('Gender'); ?></div>
			<div class="value" id="Gender"></div>
		</div>
		
		<div class="row">
			<div class="label"><?php echo __('Position Number'); ?></div>
			<div class="value"><?php echo $this->Form->input('position_no', array('class' => 'default', 'maxlength' => 15)); ?></div>
		</div>
		
		<div class="row">
			<div class="label"><?php echo __('Position'); ?></div>
			<div class="value"><?php echo $this->Form->input('teacher_category_id', array('class' => 'default', 'options' => $categoryOptions)); ?></div>
		</div>
		
		<div class="row">
			<div class="label"><?php echo __('Status'); ?></div>
			<div class="value"><?php echo $this->Form->input('teacher_status_id', array('class' => 'default', 'options' => $statusOptions)); ?></div>
		</div>
		
		<div class="row">
			<div class="label"><?php echo __('Start Date'); ?></div>
			<div class="value"><?php echo $this->Form->input('start_date', array('type' => 'date', 'dateFormat' => 'DMY', 'minYear' => $minYear, 'maxYear' => $maxYear, 'empty' => __('Select'))); ?></div>
		</div>
		
		<div class="row">
			<div class="label"><?php echo __('FTE'); ?></div>
			<div class="value"><?php echo $this->Form->input('FTE', array('class' => 'default', 'onkeypress' => 'return utility.integerCheck(event)', 'maxlength' => 3)); ?></div>
		</div>
	</div>
	
	<div class="controls">
		<input type="submit" value="<?php echo __('Add'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'teachers'), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	<?php echo $this->Form->end(); ?>
</div>