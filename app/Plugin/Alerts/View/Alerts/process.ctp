<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('jquery-ui.min', 'stylesheet', array('inline' => false));
echo $this->Html->script('jquery-ui.min', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Processes'));

$this->start('contentActions');

$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('action' => 'process'));
$labelOptions = $formOptions['inputDefaults']['label'];
echo $this->Form->create('Alert', $formOptions);
?>
<div id="" class="">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th><?php echo $this->Label->get('general.name'); ?></th>
				<th><?php echo $this->Label->get('general.status'); ?></th>
				<th><?php echo $this->Label->get('Alert.start_date'); ?></th>
				<th><?php echo $this->Label->get('Alert.end_date'); ?></th>
				<th><?php echo $this->Label->get('general.action'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if (!empty($process)): ?>
				<tr>
					<td><?php echo __('Alert Process'); ?></td>
					<td><?php echo $process['status'] == 'Active' ? __($process['status']) : 'Inactive'; ?></td>
					<td><?php echo !empty($process['start_date']) ?  $this->Utility->formatDate($process['start_date']) : ''; ?></td>
					<td><?php echo $process['status'] == 'Inactive' ?  $this->Utility->formatDate($process['end_date']) : ''; ?></td>
					<td class="text-center">
						<button name="submit" type="submit" value="<?php echo $process['status'] == 'Active' ? 'Stop' : 'Start'; ?>" class="btn_save btn_right"><?php echo $process['status'] == 'Active' ? __('Stop') : __('Start'); ?></button>
					</td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>
<?php
echo $this->Form->end();
$this->end();
?>