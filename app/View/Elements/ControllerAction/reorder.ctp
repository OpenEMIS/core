<?php
//ControllerActionComponent - Version 1.0.1
// Requires FormUtilityHelper and LabelHelper
	if(isset($data)) {
		$formParams = array('plugin' => $this->params->plugin, 'controller' => $this->params['controller']);
		$formParams = array_merge($formParams, $actionUrl);
		echo $this->Form->create($model, array('id' => $model.'MoveForm', 'url' => $formParams, 'class' => 'reorder'));
			echo $this->Form->hidden('id', array('class' => 'option-id'));
			echo $this->Form->hidden('move', array('class' => 'option-move'));
		echo $this->Form->end();
	}
?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
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
						<tr row-id="<?php echo $obj[$model]['id']; ?>">
							<td><?php echo $obj[$model]['name'];?></td>
							<td class="action">
								<?php
									$size = count($data);
									echo $this->element('ControllerAction/order', compact('index', 'size'));
									$index++;
								?>
							</td>
						</tr>
				<?php endforeach ?>
			<?php endif ?>
		</tbody>
	</table>
</div>
