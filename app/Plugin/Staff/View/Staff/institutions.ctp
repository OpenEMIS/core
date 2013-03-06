<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('/Staff/css/staff', 'stylesheet', array('inline' => false));
echo $this->Html->script('/Staff/js/staff', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="institutions" class="content_wrapper">
	<h1>
		<span><?php echo __('Institutions'); ?></span>
		<?php
		if($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'institutionsEdit'), array('class' => 'divider'));
		}
		?>
	</h1>
	<?php echo $this->element('alert'); ?>

	<div class="table full_width">
		<div class="table_head">
			<div class="table_cell"><?php echo __('Institution'); ?></div>
			<div class="table_cell cell_year_month"><?php echo __('Start Date'); ?></div>
			<div class="table_cell cell_year_month"><?php echo __('End Date'); ?></div>
		</div>
		
		<div class="table_body">
			<?php foreach ($records as $record): ?>
			<div class="table_row">
				<div class="table_cell">
					<?php echo $record['Institution']['name'].' - '.$record['InstitutionSite']['name']; ?>
				</div>
				<div class="table_cell">
					<?php echo $this->Utility->formatDate($record['InstitutionSiteStaff']['start_date'],'F Y'); ?>
				</div>
				<div class="table_cell">
					<?php echo $this->Utility->formatDate($record['InstitutionSiteStaff']['end_date'],'F Y'); ?>
				</div>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
	
</div>
