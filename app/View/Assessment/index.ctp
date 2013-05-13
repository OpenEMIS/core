<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('assessment', 'stylesheet', array('inline' => false));

echo $this->Html->script('assessment', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="assessment" class="content_wrapper">
	<h1>
		<span><?php echo __('National Assessments'); ?></span>
		<?php
		if($_edit && !empty($data)) {
			echo $this->Html->link(__('Edit'), array('action' => 'indexEdit', $selectedProgramme), array('class' => 'divider'));
		}
		if($_add) {
			echo $this->Html->link(__('Add'), array('action' => 'assessmentsAdd'), array('class' => 'divider'));
		}
		?>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<div class="filter_wrapper">
		<div class="row edit">
			<div class="label"><?php echo __('Education Programme'); ?></div>
			<div class="value">
				<?php
				echo $this->Form->input('education_programme_id', array(
					'id' => 'EducationProgrammeId',
					'label' => false,
					'div' => false,
					'class' => 'default',
					'options' => $programmeOptions,
					'default' => $selectedProgramme,
					'url' => 'Assessment/index/',
					'onchange' => 'Assessment.switchProgramme(this)'
				));
				?>
			</div>
		</div>
	</div>
	
	<?php foreach($data as $key => $obj) { ?>
	<fieldset class="section_group">
		<legend><?php echo $obj['name']; ?></legend>
		<div class="table allow_hover" action="Assessment/assessmentsView/">
			<div class="table_head">
				<div class="table_cell cell_code"><?php echo __('Code'); ?></div>
				<div class="table_cell cell_name"><?php echo __('Name'); ?></div>
				<div class="table_cell"><?php echo __('Description'); ?></div>
			</div>
			<div class="table_body">
				<?php foreach($obj['assessment'][$type] as $item) { ?>
				<div class="table_row <?php echo $item['visible'] == 0 ? 'inactive' : ''; ?>" row-id="<?php echo $item['id']; ?>">
					<div class="table_cell"><?php echo $item['code']; ?></div>
					<div class="table_cell"><?php echo $item['name']; ?></div>
					<div class="table_cell"><?php echo $item['description']; ?></div>
				</div>
				<?php } ?>
			</div>
		</div>
	</fieldset>
	<?php } ?>
</div>
