<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

echo $this->Html->script('setup_variables', false);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Logs'));
$this->start('contentActions');
if($type == 'Survey'){
	echo $this->Html->link(__('Download'), array('action' => 'logsDownload', ''), array('class' => 'divider'));

	if ($_delete) {
		echo $this->Html->link(__('Clear All'), array('action' => 'logsDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmClearAll(this)'));
	}
}
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('action' => 'logs'));
$labelOptions = $formOptions['inputDefaults']['label'];
echo $this->Form->create('Log', $formOptions);

echo $this->Form->input('type', array('options' => $typeOptions, "onchange" => "$('#reload').click()"));
echo $this->Form->input('method', array('options' => $methodOptions, "onchange" => "$('#reload').click()"));
echo $this->Form->input('channel', array('options' => $channelOptions, "onchange" => "$('#reload').click()"));
echo $this->Form->input('status', array('options' => $statusOptions, "onchange" => "$('#reload').click()"));
echo $this->Form->button('reload', array('id' => 'reload', 'type' => 'submit', 'name' => 'submit', 'value' => 'reload', 'class' => 'hidden'));
echo $this->Form->end();?>
<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead class="table_head">
			<tr>
				<th><?php echo __('Date/Time'); ?></th>
				<th><?php echo __('Destination'); ?></th>
				<th><?php echo __('Method'); ?></th>
				<th><?php echo __('Channel'); ?></th>
				<th><?php echo __('Status'); ?></th>
			</tr>
		</thead>

		<tbody class="table_body">
			<?php
			if (count($data) > 0) {
				foreach ($data as $arrVal) {
					if($type == 'Alert'){
					?>
					<tr class="table_row">
						<td><?php echo $this->Html->link($arrVal['AlertLog']['created'], array('action' => 'view', $arrVal['AlertLog']['id']), array('class' => '')); ?></td>
						<td><?php echo $arrVal['AlertLog']['destination']; ?></td>
						<td><?php echo $arrVal['AlertLog']['method']; ?></td>
						<td><?php echo $arrVal['AlertLog']['channel']; ?></td>
						<td><?php echo $arrVal['AlertLog']['status']; ?></td>
					</tr>
					<?php
					}else if($type == 'Survey'){
					?>
					<tr class="table_row">
						<td><?php echo $arrVal['SmsLog']['created']; ?></td>
						<td><?php echo $arrVal['SmsLog']['number']; ?></td>
						<td><?php echo __('SMS'); ?></td>
						<td><?php echo $arrVal['SmsLog']['send_receive'] == 1 ? 'Sent' : 'Received'; ?></td>
						<td><?php echo __('Success'); ?></td>
					</tr>
					<?php
					}
					?>
					
				<?php
				}
			}
			?>
		</tbody>
	</table>
</div>

<?php $this->end(); ?>  