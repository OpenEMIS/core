<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="languages" class="content_wrapper">
	<h1>
		<span><?php echo __('Languages'); ?></span>
		<?php
		if($_add) {
			echo $this->Html->link(__('Add'), array('action' => 'languagesAdd'), array('class' => 'divider'));
		}
		?>
	</h1>
		
	<?php echo $this->element('alert'); ?>

	<div class="table allow_hover full_width" action="Teachers/languagesView/">
		<div class="table_head">
			<div class="table_cell"><?php echo __('Date'); ?></div>
			<div class="table_cell"><?php echo __('Language'); ?></div>
			<div class="table_cell"><?php echo __('Listening'); ?></div>
			<div class="table_cell"><?php echo __('Speaking'); ?></div>
			<div class="table_cell"><?php echo __('Reading'); ?></div>
			<div class="table_cell"><?php echo __('Writing'); ?></div>
		</div>
		
		<div class="table_body">
			<?php foreach($list as $obj): ?>
			<div class="table_row" row-id="<?php echo $obj['TeacherLanguage']['id']; ?>">
				<div class="table_cell"><?php echo $this->Utility->formatDate($obj['TeacherLanguage']['evaluation_date']); ?></div>
				<div class="table_cell"><?php echo $obj['Language']['name']; ?></div>
				<div class="table_cell"><?php echo $obj['TeacherLanguage']['listening']; ?></div>
				<div class="table_cell"><?php echo $obj['TeacherLanguage']['speaking']; ?></div>
				<div class="table_cell"><?php echo $obj['TeacherLanguage']['reading']; ?></div>
				<div class="table_cell"><?php echo $obj['TeacherLanguage']['writing']; ?></div>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>