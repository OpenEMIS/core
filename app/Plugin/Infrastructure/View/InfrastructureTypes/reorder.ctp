<?php 
echo $this->Html->script('field.option', false);
echo $this->Html->css('/Infrastructure/css/infrastructure', 'stylesheet', array('inline' => false));
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Types'));
$this->start('contentActions');
echo $this->Html->link(__('Back'), array('action' => 'index', $categoryId), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
echo $this->element('nav_tabs');
?>
<div class="row page-controls">
	<div class="col-md-3">
		<?php
		echo $this->Form->input('infrastructure_category_id', array(
			'id' => 'InfrastructureCategoryId',
			'label' => false,
			'div' => false,
			'class' => 'form-control',
			'options' => $categoryOptions,
			'default' => $categoryId,
			'onchange' => 'jsForm.change(this)',
			'url' => $this->params['controller'] . '/index'
		));
		?>
	</div>
</div>
<?php
$formOptions = array('controller' => 'InfrastructureTypes', 'action' => 'move', $categoryId, 'plugin' => false);

echo $this->Form->create($model, array('id' => 'OptionMoveForm', 'url' => $formOptions));
echo $this->Form->hidden('id', array('class' => 'option-id'));
echo $this->Form->hidden('move', array('class' => 'option-move'));
echo $this->Form->end();
?>
<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<tr>
				<th class="cell-visible"><?php echo $this->Label->get('general.visible'); ?></th>
				<th><?php echo $this->Label->get('general.name'); ?></th>
				<th class="cell-order"><?php echo $this->Label->get('general.order'); ?></th>
			</tr>
			</tr>
		</thead>
		<tbody>
			<?php 
			$index = 1;
			foreach ($data as $obj) :
			?>
			<tr row-id="<?php echo $obj[$model]['id']; ?>">
				<td class="center"><?php echo $this->Utility->checkOrCrossMarker($obj[$model]['visible']==1); ?></td>
				<td><?php echo $this->Html->link($obj[$model]['name'], array('action' => 'view', $obj[$model]['id'])); ?></td>
				<td class="action">
					<?php
					$size = count($data);
					echo $this->element('layout/reorder', compact('index', 'size'));
					$index++;
					?>
				</td>
			</tr>
			<?php endforeach ?>
		</tbody>
	</table>
</div>
<?php $this->end(); ?>