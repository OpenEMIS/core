<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('setup_variables', 'stylesheet', array('inline' => false));

echo $this->Html->script('setup_variables', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="messages" class="content_wrapper">
	<h1>
		<span><?php echo __('Messages'); ?></span>
		<?php
		if($_add) {
			echo $this->Html->link(__('Add'), array('action' => 'messagesAdd'), array('class' => 'divider'));
		}
		?>
	</h1>
 	<?php echo $this->element('alert'); ?>
		
		
	<div class="table allow_hover full_width" action="Sms/messagesView/">
		<div class="table_head">
			<div class="table_cell cell_visible"><?php echo __('Visible'); ?></div>
			<div class="table_cell"><?php echo __('Message'); ?></div>
			<div class="table_cell cell_order"><?php echo __('Order');?></div>
		</div>
		
		<div class="table_body">
			<?php
			if(count($data) > 0){
				foreach($data as $arrVal){ ?>
				   <div class="table_row" row-id="<?php echo $arrVal['SmsMessage']['id'];?>">
					<div class="table_cell cell_visible"><?php echo $this->Utility->checkOrCrossMarker($arrVal['SmsMessage']['active']==1); ?></div>
					<div class="table_cell"><?php echo $arrVal['SmsMessage']['message'];?></div>
					<div class="table_cell cell_order"><?php echo $arrVal['SmsMessage']['order'];?></div>
					</div>
			<?php	}
			}
			?>
		</div>
	</div>
</div>