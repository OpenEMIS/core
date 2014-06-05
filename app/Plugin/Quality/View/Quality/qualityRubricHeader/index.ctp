<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('table_cell', 'stylesheet', array('inline' => false));
echo $this->Html->css('/Quality/css/rubrics', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Utility->ellipsis(__($subheader), 50));
$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.back'), array('action' => 'qualityRubricView', $id), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
$params = implode('/', $this->params['pass']);
$defaultAction = 'qualityRubricAnswerView';
if ($_accessControl->check($this->params['controller'], 'qualityRubricAnswerExec') && $editiable) {
	$defaultAction = 'qualityRubricAnswerExec';
}
?>
<table class="table table-striped table-hover table-bordered" action="<?php echo $this->params['controller'] . '/' . $defaultAction . '/' . $params ?>/">
	<thead class="table_head">
		<tr>
			<th class="cell_id_no"><?php echo __('No.') ?></th>
			<th><?php echo __('Section Header') ?></th>
			<th class="cell_status"><?php echo __('Status') ?></th>
		</tr>
	</thead>
	<tbody class="table_body">
		<?php foreach ($data as $key => $item) { ?>
			<tr class="table_row"  row-id="<?php echo $item[$modelName]['id']; ?>">
				<td class="table_cell"><?php echo $key + 1; ?></td>
				<td class="table_cell"><?php echo $this->Html->link($item[$modelName]['title'], array('controller' => $this->params['controller'], 'action' => $defaultAction, $this->params['pass'][0], $this->params['pass'][1], $item[$modelName]['id']), array('escape' => false)); ?></td>

				<?php
				if (!empty($questionStatusData[$item[$modelName]['id']])) {
					switch ($questionStatusData[$item[$modelName]['id']]) {
						case 'Not Started':
							$fontColor = 'font-red';
							break;
						case 'Not Completed':
							$fontColor = 'font-orange';
							break;
						case 'Completed':
							$fontColor = 'font-green';
							break;
					}
				} else {
					$fontColor = '';
				}
				?>
				<td class="table_cell cell_status <?php echo $fontColor; ?>"><?php echo empty($questionStatusData[$item[$modelName]['id']]) ? '-' : $questionStatusData[$item[$modelName]['id']]; ?></td>
			</tr>
		<?php } ?>
	</tbody>
</table>

<?php $this->end(); ?>  