<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="textbooks" class="content_wrapper edit">
	<?php
	echo $this->Form->create('CensusAssessment', array(
		'inputDefaults' => array('label' => false, 'div' => false),	
		'url' => array('controller' => 'Census', 'action' => 'assessmentsEdit')
	));
	?>
	<h1>
		<span><?php echo __('Assessments'); ?></span>
		<?php echo $this->Html->link(__('View'), array('action' => 'assessments', $selectedYear), array('class' => 'divider')); ?>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<div class="row year">
		<div class="label"><?php echo __('Year'); ?></div>
		<div class="value">
			<?php
			echo $this->Form->input('school_year_id', array(
				'options' => $years,
				'default' => $selectedYear,
				'onchange' => 'Census.navigateYear(this)',
				'url' => 'Census/' . $this->action
			));
			?>
		</div>
		<?php echo $this->element('census_legend'); ?>
	</div>
	
	<?php 
	$index = 0;
	foreach($data as $key => $val) { 
	?>
	<fieldset class="section_group">
		<legend><?php echo $key ?></legend>
		
		<div class="table">
			<div class="table_head">
				<div class="table_cell cell_grade"><?php echo __('Grade'); ?></div>
				<div class="table_cell"><?php echo __('Subject'); ?></div>
				<div class="table_cell"><?php echo __('Score'); ?></div>
			</div>
			
			<div class="table_body">
				<?php 
				$total = 0;
				foreach($val as $record) {
					$record_tag="";
					foreach ($source_type as $k => $v) {
						if ($record['source']==$v) {
							$record_tag = "row_" . $k;
						}
					}
				?>
				<div class="table_row">
					<?php
					echo $this->Form->hidden($index . '.id', array('value' => $record['id']));
					echo $this->Form->hidden($index . '.education_grade_subject_id', array('value' => $record['education_grade_subject_id']));
					echo $this->Form->hidden($index . '.institution_site_id', array('value' => $record['institution_site_id']));
					?>
					<div class="table_cell <?php echo $record_tag; ?>"><?php echo $record['education_grade_name']; ?></div>
					<div class="table_cell <?php echo $record_tag; ?>"><?php echo $record['education_subject_name']; ?></div>
					<div class="table_cell">
						<div class="input_wrapper">
						<?php echo $this->Form->input($index . '.value', array(
								'type' => 'text',
								'value' => $record['total'],
								'class'=>$record_tag,
								'maxlength' => 10,
								'onkeypress' => 'return utility.integerCheck(event)'
							));
						?>
						</div>
					</div>
				</div>
				<?php $index++; } ?>
			</div>
		</div>
	</fieldset>
	<?php } ?>
	
	<?php if(!empty($data)) { ?>
	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'assessments', $selectedYear), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	<?php } ?>
	
	<?php echo $this->Form->end(); ?>
</div>