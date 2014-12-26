<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('jquery-ui.min', 'stylesheet', array('inline' => false));
echo $this->Html->script('jquery-ui.min', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('System') . ' ' . __('Processes'));

$this->start('contentActions');

$this->end();

$this->start('contentBody');

?>
<div class="row select_row page-controls">
    <div class="col-md-4">
        <?php
            echo $this->Form->input('type', array(
                'options' => $typeOptions,
                'default' => $selectedType,
                'label' => false,
                'url' => 'Config/index',
                'class' => 'form-control',
                'onchange' => 'jsForm.change(this)',
                'div' => false
            ));
        ?>
    </div>
</div>
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
			<?php
			if (!empty($alertProcess)):
				$formOptions = $this->FormUtility->getFormOptions(array('controller' => 'Alerts', 'action' => 'processActions'));
				$labelOptions = $formOptions['inputDefaults']['label'];
				echo $this->Form->create('Alert', $formOptions);
				?>
				<tr>
					<td><?php echo __('Alert Process'); ?></td>
					<td><?php echo $alertProcess['status'] == 'Active' ? __($alertProcess['status']) : 'Inactive'; ?></td>
					<td><?php echo!empty($alertProcess['start_date']) ? $this->Utility->formatDate($alertProcess['start_date']) : ''; ?></td>
					<td><?php echo $alertProcess['status'] == 'Inactive' ? $this->Utility->formatDate($alertProcess['end_date']) : ''; ?></td>
					<td class="text-center">
						<button name="submit" type="submit" value="<?php echo $alertProcess['status'] == 'Active' ? 'Stop' : 'Start'; ?>" class="btn_save btn_right"><?php echo $alertProcess['status'] == 'Active' ? __('Stop') : __('Start'); ?></button>
					</td>
				</tr>
				<?php
				echo $this->Form->end();
			endif;
			?>
		</tbody>
	</table>
</div>
<?php
$this->end();
?>