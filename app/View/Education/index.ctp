<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('education', 'stylesheet', array('inline' => false));

echo $this->Html->script('education', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="education" class="content_wrapper">
	<?php
	echo $this->Form->create('Education', array(
			'id' => 'submitForm',
			'onsubmit' => 'return false',
			'inputDefaults' => array('label' => false, 'div' => false),	
			'url' => array('controller' => 'Education', 'action' => 'index')
		)
	);
	?>
	<h1>
		<span><?php echo __('Education Structure'); ?></span>
		<?php 
		if($_view_setup) {
			echo $this->Html->link(__('Setup'), array('action' => 'setup'), array('class' => 'divider'));
		}
		?>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<?php if(isset($systems)) { ?>
	<div class="row select_row">
		<div class="label"><?php echo __('Education System'); ?></div>
		<div class="value">
			<?php
				echo $this->Form->input('education_system_id', array(
					'id' => 'EducationSystemId',
					'options' => $systems,
					'default' => $selectedSystem,
					'onchange' => 'education.switchSystem()'
				));
			?>
		</div>
	</div>
	<?php } ?>
	
	<?php if(!empty($levels)) { ?>
	<div class="row select_row">
		<div class="label"><?php echo __('Education Level'); ?></div>
		<div class="value">
			<?php 
			$index = 0;
			foreach($levels as $key => $val) {
			?>
			<div class="radio_wrapper">
				<input type="radio" name="level" id="<?php echo $val ?>" <?php echo $index++==0 ? 'checked="checked"' : '' ?>>
				<label for="<?php echo $val ?>"><?php echo $val ?></label>
			</div>
			<?php } ?>
		</div>
	</div>
	
	<?php 
	$allIndex = 0;
	$gradeSubjects = array();
	$level = reset($levels);
	?>
	<?php foreach($structure as $key => $group) { ?>
	<div class="programme_group<?php echo $key===$level ? ' current' : '' ?>" level="<?php echo $key ?>">
		<?php foreach($group as $programme) { ?>
		<fieldset class="section_group">
			<legend><?php echo $programme['name']; ?></legend>
			
			<div class="box programme_details">
				<h2><?php echo __('Details'); ?></h2>
				
				<div class="box_body">
					<div class="row">
						<div class="label"><?php echo __('Education Cycle'); ?></div>
						<div class="value"><?php echo $programme['cycle_name'] ?></div>
					</div>
					<div class="row">
						<div class="label"><?php echo __('Orientation'); ?></div>
						<div class="value"><?php echo $programme['orientation'] ?></div>
					</div>
					<div class="row">
						<div class="label"><?php echo __('Field of Study'); ?></div>
						<div class="value"><?php echo $programme['field'] ?></div>
					</div>
					<div class="row">
						<div class="label"><?php echo __('Duration'); ?></div>
						<div class="value"><?php echo $programme['duration'] ?> years</div>
					</div>
					<div class="row">
						<div class="label"><?php echo __('Certification'); ?></div>
						<div class="value"><?php echo $programme['certificate'] ?></div>
					</div>
				</div>
			</div>
			
			<div class="box programme_grades">
			<h2><?php echo __('Grades'); ?></h2>
				<div class="box_body">
					<div class="wrapper selected" grade-id="0">All Grades</div>
					<?php foreach($programme['grades'] as $gradeId => $gradeName) { ?>
					<div class="wrapper" grade-id="<?php echo $gradeId ?>"><?php echo $gradeName ?></div>
					<?php } ?>
				</div>
			</div>
			
			<div class="box programme_subjects">
				<h2><?php echo __('Subjects'); ?></h2>
				<div class="box_body">
					<?php foreach($programme['subjects'] as $gradeId => $subjectList) { ?>
					<div class="subject_list <?php echo $gradeId!=0 ? 'none' : ''; ?>"  grade-id="<?php echo $gradeId; ?>">
						<?php foreach($subjectList as $subject) { ?>
						<div class="wrapper"><?php echo $subject['education_subject_name']; ?></div>
						<?php } ?>
					</div>
					<?php } ?>
				</div>
			</div>
		</fieldset>
		<?php } ?>
	</div>
	<?php }
	} ?>
	
	<?php echo $this->Form->end(); ?>
</div>
