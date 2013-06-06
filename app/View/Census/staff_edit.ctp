<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);
echo $this->Html->script('census_staff', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="staff" class="content_wrapper edit">
	<?php
	echo $this->Form->create('CensusStaff', array(
		'id' => 'submitForm',
		'inputDefaults' => array('label' => false, 'div' => false),	
		'url' => array('controller' => 'Census', 'action' => 'staffEdit')
	));
	?>
	<h1>
		<span><?php echo __('Staff'); ?></span>
		<?php echo $this->Html->link(__('View'), array('action' => 'staff'), array('id' => 'edit-link', 'class' => 'divider')); ?>
	</h1>
	
	<div class="row year">
		<div class="label"><?php echo __('Year'); ?></div>
		<div class="value">
			<?php
			echo $this->Form->input('school_year_id', array(
				'id' => 'SchoolYearId',
				'options' => $years,
				'default' => $selectedYear
			));
			?>
		</div>
	<?php echo $this->element('census_legend'); ?>
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
			$index = 0;
			foreach($data as $record) {
				$total += $record['male'] + $record['female'];
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
				echo $this->Form->hidden($index . '.staff_category_id', array('value' => $record['staff_category_id']));
				?>
				<div class="table_cell <?php echo $record_tag; ?>"><?php echo $record['staff_category_name']; ?></div>
				<div class="table_cell">
					<div class="input_wrapper">
					<?php 
					echo $this->Form->input($index . '.male', array(
						'id' => 'CensusStaffMale',
						'class'=>$record_tag,
						'value' => $record['male'],
						'maxlength' => 10,
						'onkeypress' => 'return utility.integerCheck(event)'
					));
					?>
					</div>
				</div>
				<div class="table_cell">
					<div class="input_wrapper">
					<?php 
					echo $this->Form->input($index . '.female', array(
						'id' => 'CensusStaffFemale',
						'class'=>$record_tag,
						'value' => $record['female'],
						'maxlength' => 10,
						'onkeypress' => 'return utility.integerCheck(event)'
					));
					?>
					</div>
				</div>
				<div class="table_cell cell_total cell_number"><?php echo $record['male'] + $record['female']; ?></div>
			</div>
			<?php $index++; } ?>
		</div>
		
		<div class="table_foot">
			<div class="table_cell"></div>
			<div class="table_cell"></div>
			<div class="table_cell cell_label"><?php echo __('Total'); ?></div>
			<div class="table_cell cell_value cell_number"><?php echo $total; ?></div>
		</div>
	</div>
	
	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<input type="button" value="<?php echo __('Cancel'); ?>" class="btn_cancel btn_left" />
	</div>
	<?php echo $this->Form->end(); ?>
</div>