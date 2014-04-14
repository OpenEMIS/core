<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="textbooks" class="content_wrapper">
	<h1>
		<span><?php echo __('Textbooks'); ?></span>
		<?php
		if($_edit && $isEditable) {
			echo $this->Html->link(__('Edit'), array('action' => 'textbooksEdit', $selectedYear), array('class' => 'divider'));
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
				'options' => $yearList,
				'default' => $selectedYear,
				'onchange' => 'Census.navigateYear(this)',
				'url' => 'Census/' . $this->action
			));
			?>
		</div>
		
	<?php echo $this->element('census_legend'); ?>
	</div>
	
	<?php foreach($data as $key => $val) { ?>
	<fieldset class="section_group">
		<legend><?php echo $key ?></legend>
		
		<div class="table">
			<div class="table_head">
				<div class="table_cell cell_grade"><?php echo __('Grade'); ?></div>
				<div class="table_cell"><?php echo __('Subject'); ?></div>
				<div class="table_cell"><?php echo __('Total'); ?></div>
			</div>
			
			<div class="table_body">
				<?php
				$total = 0;
				foreach($val as $record) { 
					$total += $record['total'];
					$record_tag="";
					foreach ($source_type as $k => $v) {
						if ($record['source']==$v) {
							$record_tag = "row_" . $k;
						}
					}
				?>
				<div class="table_row">
					<div class="table_cell <?php echo $record_tag; ?>"><?php echo $record['education_grade_name']; ?></div>
					<div class="table_cell <?php echo $record_tag; ?>"><?php echo $record['education_subject_name']; ?></div>
					<div class="table_cell cell_number <?php echo $record_tag; ?>"><?php echo $record['total']; ?></div>
				</div>
				<?php } ?>
			</div>
			
			<div class="table_foot">
				<div class="table_cell"></div>
				<div class="table_cell cell_label"><?php echo __('Total'); ?></div>
				<div class="table_cell cell_value cell_number"><?php echo $total; ?></div>
			</div>
		</div>
	</fieldset>
	<?php } ?>
</div>