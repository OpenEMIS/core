<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

echo $this->Html->script('institution_site_results', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="assessment" class="content_wrapper">
	<h1>
		<span><?php echo __('Assessments'); ?></span>
		<?php
		if($_edit) {
			echo $this->Html->link(__('Back'), array('action' => 'classesView', $classId), array('class' => 'divider'));
		}
		?>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<?php foreach($data as $obj) { ?>
	<fieldset class="section_group">
		<legend><?php echo $obj['name']; ?></legend>
		
		<div class="table allow_hover" action="InstitutionSites/classesResults/<?php echo $classId ?>/">
			<div class="table_head">
				<div class="table_cell"><?php echo __('Grade'); ?></div>
				<div class="table_cell"><?php echo __('Code'); ?></div>
				<div class="table_cell"><?php echo __('Name'); ?></div>
			</div>
			<div class="table_body">
				<?php foreach($obj['items'] as $item) { ?>
				<div class="table_row" row-id="<?php echo $item['id']; ?>">
					<div class="table_cell"><?php echo $item['grade']; ?></div>
					<div class="table_cell"><?php echo $item['code']; ?></div>
					<div class="table_cell"><?php echo $item['name']; ?></div>
				</div>
				<?php } ?>
			</div>
		</div>
	</fieldset>
	<?php } ?>
</div>
