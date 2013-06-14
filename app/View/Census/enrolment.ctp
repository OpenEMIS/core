<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);
echo $this->Html->script('census_enrolment', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="enrolment" class="content_wrapper">
	<h1>
		<span><?php echo __('Enrolment'); ?></span>
		<?php
		if($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'enrolmentEdit', $selectedYear), array('class' => 'divider'));
		}
		?>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<div class="row year">
		<div class="label"><?php echo __('Year'); ?></div>
		<div class="value">
			<?php
			echo $this->Form->input('school_year_id', array(
				'label' => false,
				'div' => false,
				'options' => $years,
				'default' => $selectedYear,
				'onchange' => 'Census.navigateYear(this)',
				'url' => 'Census/' . $this->action
			));
			?>
		</div>
		
		<div class="row_item_legend">
		<ul class="legend">
			<li><span class="dataentry"></span><?php echo __('Data Entry'); ?></li>
			<li><span class="external"></span><?php echo __('External'); ?></li>
			<li><span class="estimate"></span><?php echo __('Estimate'); ?></li>
		</ul>
		</div>
	</div>
	
	<?php foreach($data as $key => $obj) { ?>
	<fieldset class="section_group" url="Census/enrolmentAjax/<?php echo $selectedYear; ?>">
		<legend><?php echo $obj['name']; ?></legend>
		
		<div class="row" style="margin-bottom: 15px;">
			<div class="label grade"><?php echo __('Grade'); ?></div>
			<div class="value grade">
			<?php
				echo $this->Form->input('education_grade_id', array(
					'id' => 'EducationGradeId',
					'label' => false,
					'div' => false,
					'options' => $obj['grades'],
					'onchange' => 'CensusEnrolment.get(this)',
					'autocomplete' => 'off'
				));
			?>
			</div>
			<div class="label category"><?php echo __('Category'); ?></div>
			<div class="value category">
			<?php
				echo $this->Form->input('student_category_id', array(
					'id' => 'StudentCategoryId',
					'label' => false,
					'div' => false,
					'options' => $category,
					'onchange' => 'CensusEnrolment.get(this)',
					'autocomplete' => 'off'
				));
			?>
			</div>
		</div>
		
		<div class="table">
			<div class="table_head">
				<div class="table_cell"><?php echo __('Age'); ?></div>
				<div class="table_cell"><?php echo __('Male'); ?></div>
				<div class="table_cell"><?php echo __('Female'); ?></div>
				<div class="table_cell"><?php echo __('Total'); ?></div>
			</div>
			
			<?php 
			$total = 0;
			$records = $obj['enrolment'];
			if(!empty($records) && !(sizeof($records)==1 && $records[0]['male']==0 && $records[0]['female']==0)) {
			?>
			<div class="table_body">
				<?php
				foreach($records as $record) {
					$total += $record['male'] + $record['female'];
					$record_tag="";
					switch ($record['source']) {
						case 1:
							$record_tag.="row_external";break;
						case 2:
							$record_tag.="row_estimate";break;
					}
				?>
				<div class="table_row">
					<div class="table_cell cell_number <?php echo $record_tag; ?>"><?php echo $record['age']; ?></div>
					<div class="table_cell cell_number <?php echo $record_tag; ?>"><?php echo $record['male']; ?></div>
					<div class="table_cell cell_number <?php echo $record_tag; ?>"><?php echo $record['female']; ?></div>
					<div class="table_cell cell_number <?php echo $record_tag; ?>"><?php echo $record['male'] + $record['female']; ?></div>
				</div>
				<?php } // end foreach (records) ?>
			</div>
			<?php } ?>
			
			<div class="table_foot">
				<div class="table_cell"></div>
				<div class="table_cell"></div>
				<div class="table_cell cell_label"><?php echo __('Total'); ?></div>
				<div class="table_cell cell_value cell_number"><?php echo $total ?></div>
			</div>
		</div>
	</fieldset>
	<?php } // end foreach (data) ?>
</div>
