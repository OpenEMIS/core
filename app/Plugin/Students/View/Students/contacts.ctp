<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="contact" class="content_wrapper">
	<h1>
		<span><?php echo __('Contacts'); ?></span>
		<?php
		if($_add) {
			echo $this->Html->link(__('Add'), array('action' => 'contactsAdd'), array('class' => 'divider'));
		}
		?>
	</h1>
		
	<?php echo $this->element('alert'); ?>

	<div class="table allow_hover full_width" action="Students/contactsView/">
		<div class="table_head">
			<div class="table_cell"><?php echo __('Type'); ?></div>
			<div class="table_cell"><?php echo __('Description'); ?></div>
			<div class="table_cell"><?php echo __('Value'); ?></div>
			<div class="table_cell"><?php echo __('Preferred'); ?></div>
		</div>
		
		<div class="table_body">
			<?php foreach($list as $obj): ?>
			<div class="table_row" row-id="<?php echo $obj['StudentContact']['id']; ?>">
				<div class="table_cell"><?php echo $contactOptions[$obj['ContactType']['contact_option_id']]; ?></div>
				<div class="table_cell"><?php echo $obj['ContactType']['name']; ?></div>
				<div class="table_cell"><?php echo $obj['StudentContact']['value']; ?></div>
				<div class="table_cell cell_visible"><?php echo $this->Utility->checkOrCrossMarker($obj['StudentContact']['preferred']==1); ?></div>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>