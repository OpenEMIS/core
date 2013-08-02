<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="attendance" class="content_wrapper">
	<h1>
		<span><?php echo __('Validations'); ?></span>
		<?php 
		if($_execute) {
			echo $this->Html->link(__('Validate'), array('action' => 'validates', 1), array('class' => 'divider', 'onclick' => 'return Census.validate(this, "GET")'));
			if($allowUnvalidate) {
				echo $this->Html->link(__('Unvalidate'), array('action' => 'validates', 0), array('class' => 'divider', 'onclick' => 'return Census.validate(this, "GET")'));
			}
		}
		?>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<div class="table full_width">
		<div class="table_head">
			<div class="table_cell"><?php echo __('Year'); ?></div>
			<div class="table_cell"><?php echo __('By'); ?></div>
			<div class="table_cell"><?php echo __('Date'); ?></div>
			<div class="table_cell"><?php echo __('Status'); ?></div>
		</div>
		
		<div class="table_body">
			<?php foreach($data as $obj) { ?>
			<div class="table_row">
				<div class="table_cell"><?php echo $obj['SchoolYear']['name']; ?></div>
				<div class="table_cell"><?php echo $obj['SecurityUser']['first_name'] . ' ' . $obj['SecurityUser']['last_name']; ?></div>
				<div class="table_cell"><?php echo $this->Utility->formatDate($obj['CensusValidation']['created']); ?></div>
				<div class="table_cell"><?php echo $obj['CensusValidation']['status']==1 ? __('Validated') : __('Unvalidated'); ?></div>
			</div>
			<?php } ?>
		</div>
	</div>
</div>