<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="behaviour" class="content_wrapper">
	<h1>
		<span><?php echo __('Behaviour'); ?></span>
		<?php 
		if($_edit && $isEditable) {
			echo $this->Html->link(__('Edit'), array('action' => 'behaviourEdit', $selectedYear), array('class' => 'divider'));
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
		
	<div class="table full_width">
		<div class="table_head">
			<div class="table_cell cell_category"><?php echo __('Category'); ?></div>
			<div class="table_cell"><?php echo __('Male'); ?></div>
			<div class="table_cell"><?php echo __('Female'); ?></div>
			<div class="table_cell"><?php echo __('Total'); ?></div>
		</div>
		
		<div class="table_body">
			<?php 
			$total = 0;
			foreach($data as $record) {
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
				<div class="table_cell <?php echo $record_tag; ?>"><?php echo $record['name']; ?></div>
				<div class="table_cell cell_number <?php echo $record_tag; ?>"><?php echo is_null($record['male']) ? 0 : $record['male']; ?></div>
				<div class="table_cell cell_number <?php echo $record_tag; ?>"><?php echo is_null($record['female']) ? 0 : $record['female']; ?></div>
				<div class="table_cell cell_number <?php echo $record_tag; ?>"><?php echo $record['male'] + $record['female']; ?></div>
			</div>
			<?php 
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