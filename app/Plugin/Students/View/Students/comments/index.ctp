<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="bank_accounts" class="content_wrapper">
	<h1>
		<span><?php echo __('Comments'); ?></span>
		<?php
		if($_add) {
			echo $this->Html->link(__('Add'), array('action' => 'commentsAdd'), array('class' => 'divider'));
		}
		?>
	</h1>
		
	<?php echo $this->element('alert'); ?>

	<div class="table allow_hover full_width" action="Students/commentsView/">
		<div class="table_head">
			<div class="table_cell"><?php echo __('Date'); ?></div>
			<div class="table_cell"><?php echo __('Title'); ?></div>
			<div class="table_cell"><?php echo __('Comment'); ?></div>
		</div>
		
		<div class="table_body">
			<?php foreach($list as $obj): ?>
			<div class="table_row" row-id="<?php echo $obj['StudentComment']['id']; ?>">
				<div class="table_cell"><?php echo $this->Utility->formatDate($obj['StudentComment']['comment_date']); ?></div>
				<div class="table_cell"><?php echo $obj['StudentComment']['title']; ?></div>
				<div class="table_cell"><?php echo $obj['StudentComment']['comment']; ?></div>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>