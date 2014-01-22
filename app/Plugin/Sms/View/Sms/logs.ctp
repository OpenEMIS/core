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
		if($_delete) {
			echo $this->Html->link(__('Clear All'), array('action' => 'logsDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmClearAll(this)'));
		}
		?>
	</h1>
 	<?php echo $this->element('alert'); ?>

	<div class="row select_row">
		<div class="label">
			<?php
				echo $this->Form->input('type_id', array(
					'options' => $typeOptions,
					'default' => $selectedType,
					'empty' => __('All'),
					'label' => false,
					'url' => 'Sms/logs',
					'onchange' => 'jsForm.change(this)'
				));
			?>
		</div>
	</div>

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
					<div class="table_cell"><?php echo ($arrVal['SmsLog']['send_receive'])==1? __('Sent') : __('Recieved');?></div>
					</div>
			<?php	}
			}
			?>
		</div>
	</div>
</div>