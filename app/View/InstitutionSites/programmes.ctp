<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));

echo $this->Html->script('institution_site_programmes', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="programmes" class="content_wrapper">
	<h1>
		<span><?php echo __('Programmes'); ?></span>
		<?php
		if($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'programmesEdit', $selectedYear), array('class' => 'divider'));
		}
		?>
	</h1>
    <?php echo $this->element('alert'); ?>
	
	<div class="row year">
		<div class="label"><?php echo __('Year'); ?></div>
		<div class="value">
			<?php
			echo $this->Form->input('school_year_id', array(
				'id' => 'SchoolYearId',
				'label' => false,
				'div' => false,
				'options' => $yearOptions,
				'default' => $selectedYear,
				'url' => 'InstitutionSites/programmes',
				'onchange' => 'jsForm.change(this)'
			));
			?>
		</div>
	</div>
	
	<div class="table full_width">
		<div class="table_head">
			<div class="table_cell cell_system"><?php echo __('Programme'); ?></div>
			<div class="table_cell"><?php echo __('System') . ' - ' . __('Cycle'); ?></div>
		</div>
		
		<div class="table_body">
			<?php foreach($data as $obj) { ?>
			<div class="table_row" row-id="<?php echo $obj['education_programme_id']; ?>">
				<div class="table_cell"><?php echo $obj['education_programme_name']; ?></div>
				<div class="table_cell"><?php echo sprintf('%s - %s', $obj['education_system_name'], $obj['education_cycle_name']); ?></div>
			</div>
			<?php } ?>
		</div>
	</div>
</div>
