<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="staff" class="content_wrapper edit">
	<?php
	echo $this->Form->create('CensusStaff', array(
		'inputDefaults' => array('label' => false, 'div' => false),	
		'url' => array('controller' => 'Census', 'action' => 'staffEdit')
	));
	?>
	<h1>
		<span><?php echo __('Staff'); ?></span>
		<?php echo $this->Html->link(__('View'), array('action' => 'staff', $selectedYear), array('class' => 'divider')); ?>
	</h1>
	
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
			$index = 0;
			foreach($data as $record) {
				if($record['staff_category_visible'] == 1) {
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
				<?php
				echo $this->Form->hidden($index . '.id', array('value' => $record['id']));
				echo $this->Form->hidden($index . '.staff_category_id', array('value' => $record['staff_category_id']));
				?>
				<div class="table_cell <?php echo $record_tag; ?>"><?php echo $record['staff_category_name']; ?></div>
				<div class="table_cell">
					<div class="input_wrapper">
					<?php 
					echo $this->Form->input($index . '.male', array(
						'type' => 'text',
						'class' => 'computeTotal ' . $record_tag,
						'value' => empty($record['male']) ? 0 : $record['male'],
						'maxlength' => 10,
						'onkeypress' => 'return utility.integerCheck(event)',
						'onkeyup' => 'Census.computeTotal(this)'
					));
					?>
					</div>
				</div>
				<div class="table_cell">
					<div class="input_wrapper">
					<?php 
					echo $this->Form->input($index . '.female', array(
						'type' => 'text',
						'class' => 'computeTotal ' . $record_tag,
						'value' => empty($record['female']) ? 0 : $record['female'],
						'maxlength' => 10,
						'onkeypress' => 'return utility.integerCheck(event)',
						'onkeyup' => 'Census.computeTotal(this)'
					));
					?>
					</div>
				</div>
				<div class="table_cell cell_total cell_number"><?php echo $record['male'] + $record['female']; ?></div>
			</div>
			<?php 
					$index++; 
				} 
			}
			?>
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
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'staff', $selectedYear), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	<?php echo $this->Form->end(); ?>
</div>
