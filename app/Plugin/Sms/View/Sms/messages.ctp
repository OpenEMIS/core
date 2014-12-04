<?php
echo $this->Html->script('setup_variables', false);
echo $this->Html->script('/Sms/js/sms', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Questions'));
$this->start('contentActions');
if($_add) {
    echo $this->Html->link(__('Add'), array('action' => 'messagesAdd'), array('class' => 'divider', 'id'=>'add'));
}
$this->end();

$this->start('contentBody');
?>
<?php echo $this->element('alert'); ?>
	
<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead action="Sms/messagesView/">
			<tr class="table_head">
				<td class="table_cell cell_order"><?php echo __('Enabled'); ?></td>
				<td class="table_cell"><?php echo __('Message'); ?></td>
				<td class="table_cell cell_order"><?php echo __('Order');?></td>
			</tr>
		</thead>
		
		<tbody class="table_body">
			<?php
			if(count($data) > 0){
				foreach($data as $arrVal){ ?>
				<tr class="table_row" row-id="<?php echo $arrVal['SmsMessage']['id'];?>">
					<td class="table_cell cell_visible"><?php echo $this->Utility->checkOrCrossMarker($arrVal['SmsMessage']['enabled']==1); ?></td>
					<td class="table_cell"><?php echo $this->Html->link($arrVal['SmsMessage']['message'], array('action' => 'messagesView', $arrVal['SmsMessage']['id']), array('escape' => false)); ?></td>
					<td class="table_cell"><?php echo $arrVal['SmsMessage']['order'];?></td>
				</tr>
			<?php	}
			}
			?>
		</tbody>
	</table>
</div>

<?php $this->end(); ?>  