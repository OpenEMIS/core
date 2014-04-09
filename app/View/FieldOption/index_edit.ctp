<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->script('field.option', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
$params = array_merge(array('action' => 'index', $selectedOption));
echo $this->Html->link($this->Label->get('general.back'), $params, array('class' => 'divider'));
$this->end(); // end contentActions

$this->start('contentBody');
echo $this->Form->create($model, array(
	'id' => 'OptionMoveForm',
	'url' => array('controller' => $this->params['controller'], 'action' => 'reorder', $selectedOption)
));
echo $this->Form->hidden('id', array('class' => 'option-id'));
echo $this->Form->hidden('move', array('class' => 'option-move'));
foreach($conditions as $key => $val) {
	echo $this->Form->hidden('conditions.'.$key, array('value' => $val));
}
echo $this->Form->end();
?>

<div class="table_content">
	<table class="table table-striped">
		<thead>
			<tr>
				<td class="col-visible" style="width: 60px;"><?php echo $this->Label->get('general.visible'); ?></td>
				<td><?php echo __('Option'); ?></td>
				<td class="col-order"><?php echo $this->Label->get('general.order'); ?></td>
			</tr>
		</thead>
		<tbody>
			<?php 
			if(!empty($data)) :
				$index = 1;
				foreach($data as $obj) :
			?>
			<tr row-id="<?php echo $obj[$model]['id']; ?>">
				<td class="center"><?php echo $this->Utility->checkOrCrossMarker($obj[$model]['visible']==1); ?></td>
				<td><?php echo $this->Html->link($obj[$model]['name'], array('action' => 'view', $selectedOption, $obj[$model]['id'])); ?></td>
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

<?php $this->end(); // end contentBody ?>
