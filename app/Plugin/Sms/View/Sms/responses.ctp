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
		echo $this->Html->link(__('Download'), array('action' => 'responsesDownload'), array('class' => 'divider'));
		
		if($_delete) {
			echo $this->Html->link(__('Clear All'), array('action' => 'responsesDelete'), array('class' => 'divider'));
		}
		?>
	</h1>
 	<?php echo $this->element('alert'); ?>
		
		
	<div class="table full_width">
		<div class="table_head">
			<div class="table_cell"><?php echo __('Number'); ?></div>
			<?php foreach($messages as $message){?>
			<div class="table_cell"><?php echo __($message['SmsMessage']['message']);?></div>
			<?php } ?>
		</div>
		
		<div class="table_body">
			<?php
			if(count($data) > 0){
				foreach($data as $arrVal){ ?>
			   <div class="table_row">
					<div class="table_cell"><?php echo $arrVal['SmsResponse']['number'];?></div>
					<?php for($i=1;$i<=$max;$i++){ 
						$response = '';
						if($i==1){
							$response = $arrVal['SmsResponse']['response'];
						}else{
							$response = $arrVal['SmsResponse'.$i]['response'];
						}
					?>
					<div class="table_cell"><?php echo $response;?></div>
					<?php } ?>
				</div>
			<?php	
				}
			}
			?>
		</div>
	</div>
</div>