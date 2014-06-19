<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentBody');
echo $this->element('../Education/controls');
?>

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
?>

<?php echo $this->Form->end(); ?>

<?php $this->end(); ?>	
