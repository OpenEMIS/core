<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));

echo $this->Html->script('institution_site_programmes', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="programmes" class="content_wrapper">
	<h1>
		<span><?php echo __('Edit Programmes'); ?></span>
		<?php echo $this->Html->link(__('View'), array('action' => 'programmes', $selectedYear), array('class' => 'divider')); ?>
	</h1>
    <?php echo $this->element('alert'); ?>
	
	<?php 
	echo $this->Form->create('InstitutionSite', array(
		'url' => array('controller' => 'InstitutionSites', 'action' => 'programmes'),
		'inputDefaults' => array('label' => false, 'div' => false)
	)); 
	?>
	
	<div class="row year">
		<div class="label"><?php echo __('Year'); ?></div>
		<div class="value">
			<?php
			echo $this->Form->input('school_year_id', array(
				'id' => 'SchoolYearId',
				'options' => $yearOptions,
				'default' => $selectedYear,
				'url' => 'InstitutionSites/programmesEdit',
				'onchange' => 'jsForm.change(this)'
			));
			?>
		</div>
	</div>
	
	<div class="table full_width">
		<div class="table_head">
			<div class="table_cell cell_system"><?php echo __('Programme'); ?></div>
			<div class="table_cell"><?php echo __('System') . ' - ' . __('Cycle'); ?></div>
			<?php if($_delete) { ?>
			<div class="table_cell cell_delete">&nbsp;</div>
			<?php } ?>
		</div>
		
		<div class="table_body">
			<?php foreach($data as $obj) { ?>
			<div class="table_row" row-id="<?php echo $obj['education_programme_id']; ?>">
				<div class="table_cell"><?php echo $obj['education_programme_name']; ?></div>
				<div class="table_cell"><?php echo sprintf('%s - %s', $obj['education_system_name'], $obj['education_cycle_name']); ?></div>
				<?php if($_delete) { ?>
				<div class="table_cell cell_delete"><span class="icon_delete" href="InstitutionSites/programmesDelete/<?php echo $selectedYear . '/' . $obj['id']; ?>" onclick="jsForm.confirmDelete(this)"></span></div>
				<?php } ?>
			</div>
			<?php } ?>
		</div>
	</div>
	
	<?php if($_add) { ?>
	<div class="row">
		<a class="void icon_plus" url="InstitutionSites/programmesAdd/<?php echo $selectedYear; ?>"><?php echo __('Add').' '.__('Programme'); ?></a>
	</div>
	<?php } ?>
</div>
