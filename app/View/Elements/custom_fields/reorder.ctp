<?php
echo $this->Html->script('reorder', false);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);
$this->start('contentActions');
	$params = $this->params->named;
	echo $this->Html->link($this->Label->get('general.back'), array_merge(array('action' => 'index'), $params), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
	echo $this->element('/custom_fields/controls');

	if(isset($data)) {
		$formParams = array('plugin' => $this->params->plugin, 'controller' => $this->params['controller'], 'action' => 'moveOrder', $selectedGroup);
		$formParams = array_merge($formParams, $params);
		echo $this->Form->create($Custom_Field, array('id' => $Custom_Field.'MoveForm', 'url' => $formParams, 'class' => 'reorder'));
			echo $this->Form->hidden('id', array('class' => 'option-id'));
			echo $this->Form->hidden('move', array('class' => 'option-move'));
		echo $this->Form->end();
	}
?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th class="cell-visible"><?php echo __('Visible'); ?></th>
				<th><?php echo __('Name'); ?></th>
				<th class="cell-order"><?php echo __('Order'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if(isset($data)) : ?>
				<?php
					$index = 1;
					foreach ($data as $obj) :
				?>
						<tr row-id="<?php echo $obj[$Custom_Field]['id']; ?>">
							<td class="center"><?php echo $this->Utility->checkOrCrossMarker($obj[$Custom_Field]['visible']==1); ?></td>
							<td><?php echo $obj[$Custom_Field]['name'];?></td>
							<td class="action">
								<?php
									$size = count($data);
									echo $this->element('/custom_fields/order', compact('index', 'size'));
									$index++;
								?>
							</td>
						</tr>
				<?php endforeach ?>
			<?php endif ?>
		</tbody>
	</table>
</div>

<?php $this->end(); ?>