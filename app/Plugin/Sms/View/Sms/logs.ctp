<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('setup_variables', 'stylesheet', array('inline' => false));

echo $this->Html->script('setup_variables', false);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Logs'));
$this->start('contentActions');
echo $this->Html->link(__('Download'), array('action' => 'logsDownload', $selectedType), array('class' => 'divider'));
		
if($_delete) {
	echo $this->Html->link(__('Clear All'), array('action' => 'logsDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmClearAll(this)'));
}
$this->end();

$this->start('contentBody');
?>

<?php echo $this->element('alert'); ?>

<div class="row select_row form-group">
    <div class="col-md-4">
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

<div class="table-responsive">
<table class="table table-striped table-hover table-bordered">
	<thead class="table_head">
		<tr>
			<td class="table_cell"><?php echo __('Date/Time'); ?></td>
			<td class="table_cell"><?php echo __('Number'); ?></td>
			<td class="table_cell"><?php echo __('Message');?></td>
			<td class="table_cell"><?php echo __('Type');?></td>
		</tr>
	</thead>
	
	<tbody class="table_body">
		<?php
		if(count($data) > 0){
			foreach($data as $arrVal){ ?>
		   	<tr class="table_row">
				<td class="table_cell"><?php echo $arrVal['SmsLog']['created']; ?></td>
				<td class="table_cell"><?php echo $arrVal['SmsLog']['number'];?></td>
				<td class="table_cell"><?php echo $arrVal['SmsLog']['message'];?></td>
				<td class="table_cell"><?php echo ($arrVal['SmsLog']['send_receive'])==1? __('Sent') : __('Received');?></td>
			</tr>
		<?php	}
		}
		?>
	</tbody>
</table>
</div>

<?php $this->end(); ?>  