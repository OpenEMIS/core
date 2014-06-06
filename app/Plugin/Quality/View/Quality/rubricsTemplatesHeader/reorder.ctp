<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->script('field.option', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
$params = array_merge(array('action' => $_action), $conditions);
echo $this->Html->link($this->Label->get('general.back'), $params, array('class' => 'divider'));
$this->end();

$this->start('contentBody');
$formParams = array('controller' => $this->params['controller'], 'action' => $_action.'Move');
$formParams = array_merge($formParams, $conditions);
echo $this->Form->create($model, array('id' => 'OptionMoveForm', 'url' => $formParams));
echo $this->Form->hidden('id', array('class' => 'option-id'));
echo $this->Form->hidden('move', array('class' => 'option-move'));
echo $this->Form->end();
?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th><?php echo $this->Label->get('Quality.header'); ?></th>
				<th class="cell-order"><?php echo $this->Label->get('general.order'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php 
			if(!empty($data)) :
				$index = 1;
				foreach($data as $obj) :
			?>
			<tr row-id="<?php echo $obj[$model]['id']; ?>">
				<td><?php echo $obj[$model]['title']; ?></td>
				<td class="action">
					<?php
					$size = count($data);
					echo $this->element('layout/reorder', compact('index', 'size'));
					$index++;
					?>
				</td>
			</tr>
			<?php 
				endforeach;
			endif;
			?>
		</tbody>
	</table>
</div>

<?php $this->end(); ?>