<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="graduates" class="content_wrapper">
	<h1>
		<span><?php echo __('Graduates'); ?></span>
		<?php
		if($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'graduatesEdit', $selectedYear), array('class' => 'divider'));
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

	<?php foreach($data as $key => $val) { ?>
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
				<?php foreach($val as $record) { 
					$record_tag="";
					foreach ($source_type as $k => $v) {
						if ($record['source']==$v) {
							$record_tag = "row_" . $k;
						}
					}
				?>
				<div class="table_row">
					<div class="table_cell <?php echo $record_tag; ?>"><?php echo $record['education_programme_name']; ?></div>
					<div class="table_cell <?php echo $record_tag; ?>"><?php echo $record['education_certification_name']; ?></div>
					<div class="table_cell cell_number <?php echo $record_tag; ?>"><?php echo is_null($record['male']) ? 0 : $record['male']; ?></div>
					<div class="table_cell cell_number <?php echo $record_tag; ?>"><?php echo is_null($record['female']) ? 0 : $record['female']; ?></div>
					<div class="table_cell cell_number <?php echo $record_tag; ?>"><?php echo $record['total']; ?></div>
				</div>
				<?php } ?>
			</div>
		</div>
	</fieldset>
	<?php } ?>
</div>