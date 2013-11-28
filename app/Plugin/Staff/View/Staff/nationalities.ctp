<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="nationality" class="content_wrapper">
	<h1>
		<span><?php echo __('Nationalities'); ?></span>
		<?php
		if($_add) {
			echo $this->Html->link(__('Add'), array('action' => 'nationalitiesAdd'), array('class' => 'divider'));
		}
		?>
	</h1>
		
	<?php echo $this->element('alert'); ?>

	<div class="table allow_hover full_width" action="Staff/nationalitiesView/">
		<div class="table_head">
			<div class="table_cell"><?php echo __('Country'); ?></div>
			<div class="table_cell"><?php echo __('Comment'); ?></div>
		</div>
		
		<div class="table_body">
			<?php foreach($list as $obj): ?>
			<div class="table_row" row-id="<?php echo $obj['StaffNationality']['id']; ?>">
				<div class="table_cell"><?php echo $obj['Country']['name']; ?></div>
				<div class="table_cell"><?php echo $obj['StaffNationality']['comments']; ?></div>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>