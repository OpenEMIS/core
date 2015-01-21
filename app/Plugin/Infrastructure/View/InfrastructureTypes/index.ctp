<?php 
echo $this->Html->css('/Infrastructure/css/infrastructure', 'stylesheet', array('inline' => false));
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Types'));
$this->start('contentActions');
if ($_add) {
	echo $this->Html->link($this->Label->get('general.add'), array('action' => 'add', $categoryId, 'plugin' => false), array('class' => 'divider'));
}
if ($_edit && count($data) > 1) {
	echo $this->Html->link($this->Label->get('general.reorder'), array('action' => 'reorder', $categoryId, 'plugin' => false), array('class' => 'divider'));
}
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
<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<tr>
				<th class="cell-visible"><?php echo $this->Label->get('general.visible'); ?></th>
				<th><?php echo $this->Label->get('general.name'); ?></th>
			</tr>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($data as $obj) : ?>
			<tr>
				<td class="center"><?php echo $this->Utility->checkOrCrossMarker($obj[$model]['visible']==1); ?></td>
				<td><?php echo $this->Html->link($obj[$model]['name'], array('action' => 'view', $obj[$model]['id'])); ?></td>
			</tr>
			<?php endforeach ?>
		</tbody>
	</table>
</div>
<?php $this->end(); ?>