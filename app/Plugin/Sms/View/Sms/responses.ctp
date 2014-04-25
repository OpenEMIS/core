<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('custom_fields', 'stylesheet', array('inline' => false));
echo $this->Html->script('jquery.tools', false);
echo $this->Html->script('jquery.quicksand', false);

echo $this->Html->script('/Sms/js/sms.responses', false);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Responses'));
$this->start('contentActions');
echo $this->Html->link(__('Download'), array('action' => 'responsesDownload'), array('class' => 'divider'));
		
if($_delete) {
	echo $this->Html->link(__('Clear All'), array('action' => 'responsesDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmClearAll(this)'));
}
$this->end();

$this->start('contentBody');
?>
<?php echo $this->element('alert'); ?>
		
<div class="table-responsive">
<table class="table table-striped table-hover table-bordered">
	<thead class="table_head">
		<tr>
			<td class="table_cell"><?php echo __('Number'); ?></td>
			<?php foreach($messages as $message){?>
				<?php 
				$content = $message['SmsMessage']['message'];
	
				$pos = strlen($content);
				if(strlen($content)>20){
					$pos=strpos($content, ' ', 20);

					echo '<td class="table_cell" title="' . $message['SmsMessage']['message'] . '">';
					echo __(substr($content,0,$pos) . "..."); 
					echo '</td>';
				}else{
					echo '<td class="table_cell">';
					echo __($content);
					echo '</td>';
				}

    		?>
			<?php } ?>
		</tr>
	</thead>	
	<tbody class="table_body">
		<?php
		if(count($data) > 0){
			foreach($data as $arrVal){ ?>
		   <tr class="table_row">
				<td class="table_cell"><?php echo $arrVal['SmsResponse']['number'];?></td>
				<?php for($i=$min;$i<=$max;$i++){ 
					$response = '';
					if($i==$min){
						$response = $arrVal['SmsResponse']['response'];
					}else{
						$response = $arrVal['SmsResponse'.$i]['response'];
					}
				?>
				<td class="table_cell"><?php echo $response;?></td>
				<?php } ?>
			</tr>
		<?php	
			}
		}
		?>
	</tbody>
</table>
</div>

<?php $this->end(); ?>  