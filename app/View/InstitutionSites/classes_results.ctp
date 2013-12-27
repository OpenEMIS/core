<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="results" class="content_wrapper">
	<h1>
		<span><?php echo __('Results'); ?></span>
		<?php
		echo $this->Html->link(__('Back'), array('action' => 'classesAssessments', $classId), array('class' => 'divider'));
		if($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'classesResultsEdit', $classId, $assessmentId, $selectedItem), array('class' => 'divider'));
		}
		?>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<?php if(!empty($itemOptions) && !empty($data)) { ?>
	<div class="row">
		<div class="label"><?php echo __('Subject'); ?></div>
		<div class="value">
			<?php
			echo $this->Form->input('subject_id', array(
				'div' => false,
				'label' => false,
				'options' => $itemOptions,
				'default' => $selectedItem,
				'url' => sprintf('%s/%s/%d/%d', $this->params['controller'], $this->action, $classId, $assessmentId),
				'onchange' => 'jsForm.change(this)'
			));
			?>
		</div>
	</div>
	
	<div class="table full_width" style="margin-top: 15px;">
		<div class="table_head">
			<div class="table_cell cell_id_no"><?php echo __('OpenEMIS ID'); ?></div>
			<div class="table_cell"><?php echo __('Student Name'); ?></div>
			<div class="table_cell cell_marks"><?php echo __('Marks'); ?></div>
			<div class="table_cell cell_grading"><?php echo __('Grading'); ?></div>
		</div>
		<div class="table_body">
			<?php foreach($data as $obj) { ?>
			<div class="table_row">
				<div class="table_cell"><?php echo $obj['Student']['identification_no']; ?></div>
				<div class="table_cell"><?php echo sprintf('%s %s', $obj['Student']['first_name'], $obj['Student']['last_name']); ?></div>
				<div class="table_cell center">
				<?php 
				$marks = $obj['AssessmentItemResult']['marks'];
				if(is_null($marks) || strlen(trim($marks))==0) {
					echo __('Not Recorded');
				} else {
					if($marks < $obj['AssessmentItem']['min']) {
						echo sprintf('<span class="red">%s</span>', $marks);
					} else {
						echo $marks;
					}
				}
				?>
				</div>
				<div class="table_cell center"><?php echo $obj['AssessmentResultType']['name']; ?></div>
			</div>
			<?php } ?>
		</div>
	</div>
	<?php } ?>
</div>
