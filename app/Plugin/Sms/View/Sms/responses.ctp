<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('custom_fields', 'stylesheet', array('inline' => false));
echo $this->Html->script('jquery.tools', false);
echo $this->Html->script('jquery.quicksand', false);

echo $this->Html->script('/Sms/js/sms', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="messages" class="content_wrapper">
	<h1>
		<span><?php echo __('Responses'); ?></span>
		<?php
		echo $this->Html->link(__('Download'), array('action' => 'responsesDownload'), array('class' => 'divider'));
		
		if($_delete) {
			echo $this->Html->link(__('Clear All'), array('action' => 'responsesDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
		}
		?>
	</h1>
 	<?php echo $this->element('alert'); ?>
		
	<div class="table full_width">
		<div class="table_head">
			<div class="table_cell"><?php echo __('Number'); ?></div>
			<?php foreach($messages as $message){?>
				<?php 
				$content = $message['SmsMessage']['message'];
				/*
				if (strlen($content) >= 21){
				    echo __(substr($content, 0, 20). "...");
				 }else{
				    echo __($content); 
				}*/

				$pos = strlen($content);
				if(strlen($content)>20){
					$pos=strpos($content, ' ', 20);

					echo '<div class="table_cell" title="' . $message['SmsMessage']['message'] . '">';
					echo __(substr($content,0,$pos) . "..."); 
					echo '</div>';
				}else{
					echo '<div class="table_cell">';
					echo __($content);
					echo '</div>';
				}

    		?>
			<?php } ?>
		</div>
		
		<div class="table_body">
			<?php
			if(count($data) > 0){
				foreach($data as $arrVal){ ?>
			   <div class="table_row">
					<div class="table_cell"><?php echo $arrVal['SmsResponse']['number'];?></div>
					<?php for($i=$min;$i<=$max;$i++){ 
						$response = '';
						if($i==$min){
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