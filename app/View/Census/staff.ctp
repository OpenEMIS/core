<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="staff" class="content_wrapper">
	<h1>
		<span><?php echo __('Staff'); ?></span>
		<?php 
		if($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'staffEdit', $selectedYear), array('class' => 'divider'));
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
	<?php echo $this->element('census_legend'); ?>
	</div>
		
	<div class="table full_width">
		<div class="table_head">
			<div class="table_cell cell_category"><?php echo __('Position'); ?></div>
			<div class="table_cell"><?php echo __('Male'); ?></div>
			<div class="table_cell"><?php echo __('Female'); ?></div>
			<div class="table_cell"><?php echo __('Total'); ?></div>
		</div>
		
		<div class="table_body">
			<?php 
			$total = 0;
			foreach($data as $record) {
				if($record['staff_category_visible'] == 1) {
					$total += $record['male'] + $record['female'];
					$record_tag="";
					foreach ($source_type as $k => $v) {
						if ($record['source']==$v) {
							$record_tag = "row_" . $k;
						}
					}
			?>
			<div class="table_row">
				<div class="table_cell <?php echo $record_tag; ?>"><?php echo $record['staff_category_name']; ?></div>
				<div class="table_cell cell_number <?php echo $record_tag; ?>"><?php echo is_null($record['male']) ? 0 : $record['male']; ?></div>
				<div class="table_cell cell_number <?php echo $record_tag; ?>"><?php echo is_null($record['female']) ? 0 : $record['female']; ?></div>
				<div class="table_cell cell_number <?php echo $record_tag; ?>"><?php echo $record['male'] + $record['female']; ?></div>
			</div>
			<?php 
				} // end if
			} // end for
			?>
		</div>
		
		<div class="table_foot">
			<div class="table_cell"></div>
			<div class="table_cell"></div>
			<div class="table_cell cell_label"><?php echo __('Total'); ?></div>
			<div class="table_cell cell_value cell_number"><?php echo $total; ?></div>
		</div>
	</div>
</div>