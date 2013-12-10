<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('setup_variables', 'stylesheet', array('inline' => false));

echo $this->Html->script('setup_variables', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="messages" class="content_wrapper">
	<h1>
		<span><?php echo __('Logs'); ?></span>
		<?php
		if($_add) {
			echo $this->Html->link(__('Clear All'), array('action' => 'logsAdd'), array('class' => 'divider'));
		}
		?>
	</h1>
 	<?php echo $this->element('alert'); ?>
		
		
	<div class="table full_width">
		<div class="table_head">
			<div class="table_cell"><?php echo __('Date/Time'); ?></div>
			<div class="table_cell"><?php echo __('Number'); ?></div>
			<div class="table_cell"><?php echo __('Message');?></div>
			<div class="table_cell"><?php echo __('Type');?></div>
		</div>
		
		<div class="table_body">
			<?php
			if(count($data) > 0){
				foreach($data as $arrVal){ ?>
				   <div class="table_row">
					<div class="table_cell"><?php echo $arrVal['SmsLog']['created']; ?></div>
					<div class="table_cell"><?php echo $arrVal['SmsLog']['number'];?></div>
					<div class="table_cell"><?php echo $arrVal['SmsLog']['message'];?></div>
					<div class="table_cell"><?php echo $arrVal['SmsLog']['type'];?></div>
					</div>
			<?php	}
			}
			?>
		</div>
	</div>
</div>