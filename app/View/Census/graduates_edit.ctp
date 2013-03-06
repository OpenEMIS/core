<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);
echo $this->Html->script('census_graduates', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="graduates" class="content_wrapper edit">
	<?php
	echo $this->Form->create('CensusGraduate', array(
		'inputDefaults' => array('label' => false, 'div' => false),	
		'url' => array('controller' => 'Census', 'action' => 'graduatesEdit')
	));
	?>
	<h1>
		<span><?php echo __('Graduates'); ?></span>
		<?php echo $this->Html->link(__('View'), array('action' => 'graduates'), array('id' => 'edit-link', 'class' => 'divider')); ?>
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
	</div>
	
	<?php 
	$index = 0;
	$total = 0;
	foreach($data as $key => $val) { 
	?>
	<fieldset class="section_group">
		<legend><?php echo $key ?></legend>
		
		<div class="table">
			<div class="table_head">
				<div class="table_cell cell_programme"><?php echo __('Programme'); ?></div>
				<div class="table_cell cell_certificate"><?php echo __('Certification'); ?></div>
				<div class="table_cell"><?php echo __('Male'); ?></div>
				<div class="table_cell"><?php echo __('Female'); ?></div>
				<div class="table_cell"><?php echo __('Total'); ?></div>
			</div>
			
			<div class="table_body">
				<?php 
				foreach($val as $record) { 
					$total += $record['male'] + $record['female'];
				?>
				<div class="table_row">
					<?php
					echo $this->Form->hidden($index . '.id', array('value' => $record['id']));
					echo $this->Form->hidden($index . '.institution_site_programme_id', array(
						'value' => $record['institution_site_programme_id']
					));
					?>
					<div class="table_cell"><?php echo $record['education_programme_name']; ?></div>
					<div class="table_cell"><?php echo $record['education_certification_name']; ?></div>
					<div class="table_cell">
						<div class="input_wrapper">
						<?php echo $this->Form->input($index . '.male', array(
								'id' => 'CensusGraduateMale',
								'type' => 'text',
								'value' => is_null($record['male']) ? 0 : $record['male'],
								'maxlength' => 9,
								'onkeypress' => 'return utility.integerCheck(event)'
							)); 
						?>
						</div>
					</div>
					<div class="table_cell">
						<div class="input_wrapper">
						<?php echo $this->Form->input($index . '.female', array(
								'id' => 'CensusGraduateFemale',
								'type' => 'text',
								'value' => is_null($record['female']) ? 0 : $record['female'],
								'maxlength' => 9,
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
				<div class="table_cell"></div>
				<div class="table_cell cell_label">Total</div>
				<div class="table_cell cell_value cell_number"><?php echo $total; ?></div>
			</div>
		</div>
	</fieldset>
	<?php } ?>
	
	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<input type="button" value="<?php echo __('Cancel'); ?>" class="btn_cancel btn_left" />
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>

