<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('jquery-ui.min', 'stylesheet', array('inline' => false));
echo $this->Html->script('jquery-ui.min', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Alert Process'));

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
				<th><?php echo $this->Label->get('general.status'); ?></th>
				<th><?php echo $this->Label->get('Alert.start_date'); ?></th>
				<th><?php echo $this->Label->get('Alert.end_date'); ?></th>
				<th><?php echo $this->Label->get('Alert.interval'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if (!empty($process)): ?>
				<tr>
					<td><?php echo $process['status'] == 'Active' ? __($process['status']) : 'Inactive'; ?></td>
					<td><?php echo $process['status'] == 'Active' ?  $this->Utility->formatDate($process['start_date']) : ''; ?></td>
					<td><?php echo $process['status'] == 'Active' ?  $this->Utility->formatDate($process['end_date']) : ''; ?></td>
					<td><?php echo '24 ' . __(' hours'); ?></td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>
	<div class="controls">
		<input name="submit_button" type="submit" value="<?php echo $process['status'] == 'Active' ? __('Stop') : __('Start'); ?>" class="btn_save btn_right" />
	</div>
</div>
<?php
echo $this->Form->end();
$this->end();
?>