<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('setup_variables', 'stylesheet', array('inline' => false));

echo $this->Html->script('setup_variables', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="messages" class="content_wrapper">
	<h1>
		<span><?php echo __('Responses'); ?></span>
		<?php
		if($_add) {
			echo $this->Html->link(__('Clear All'), array('action' => 'responsesDelete'), array('class' => 'divider'));
		}
		?>
	</h1>
 	<?php echo $this->element('alert'); ?>
		
		
	<div class="table full_width">
		<div class="table_head">
			<div class="table_cell"><?php echo __('Date/Time'); ?></div>
			<div class="table_cell"><?php echo __('Number'); ?></div>
			<div class="table_cell"><?php echo __('Message');?></div>
			<div class="table_cell"><?php echo __('Response');?></div>
		</div>
		
		<div class="table_body">
			<?php
			if(count($data) > 0){
				foreach($data as $arrVal){ ?>
				   <div class="table_row">
					<div class="table_cell"><?php echo $arrVal['SmsResponse']['sent']; ?></div>
					<div class="table_cell"><?php echo $arrVal['SmsResponse']['number'];?></div>
					<div class="table_cell"><?php echo $arrVal['SmsResponse']['message'];?></div>
					<div class="table_cell"><?php echo $arrVal['SmsResponse']['response'];?></div>
					</div>
			<?php	}
			}
			?>
		</div>
	</div>
</div>