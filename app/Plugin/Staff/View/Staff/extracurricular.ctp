<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="extracurricular" class="content_wrapper">
	<h1>
		<span><?php echo __('Extracurricular'); ?></span>
		<?php
		if($_add) {
			echo $this->Html->link(__('Add'), array('action' => 'extracurricularAdd'), array('class' => 'divider'));
		}
		?>
	</h1>
		
	<?php echo $this->element('alert'); ?>

	<div class="table allow_hover full_width" action="Staff/extracurricularView/">
		<div class="table_head">
			<div class="table_cell"><?php echo __('Year'); ?></div>
			<div class="table_cell"><?php echo __('Start Date'); ?></div>
            <div class="table_cell"><?php echo __('Type'); ?></div>
			<div class="table_cell"><?php echo __('Title'); ?></div>
		</div>
		
		<div class="table_body">
			<?php  foreach($list as $obj): ?>
			<div class="table_row" row-id="<?php echo $obj['StaffExtracurricular']['id']; ?>">
				<div class="table_cell"><?php echo $obj['SchoolYears']['name']; ?></div>
				<div class="table_cell"><?php echo $this->Utility->formatDate($obj['StaffExtracurricular']['start_date']); ?></div>
				<div class="table_cell"><?php echo $obj['ExtracurricularType']['name']; ?></div>
                <div class="table_cell"><?php echo $obj['StaffExtracurricular']['name']; ?></div>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>