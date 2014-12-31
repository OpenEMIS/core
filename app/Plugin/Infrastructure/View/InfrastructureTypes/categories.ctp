<?php 
echo $this->Html->css('/Infrastructure/css/infrastructure', 'stylesheet', array('inline' => false));
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Types'));
$this->start('contentActions');
$this->end();

$this->start('contentBody');
echo $this->element('nav_tabs');
?>
<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<tr>
				<th class="cell-visible"><?php echo $this->Label->get('general.visible'); ?></th>
				<th><?php echo $this->Label->get('general.category'); ?></th>
			</tr>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($data as $obj) : ?>
			<tr>
				<td class="center"><?php echo $this->Utility->checkOrCrossMarker($obj['InfrastructureCategory']['visible']==1); ?></td>
				<td><?php echo $this->Html->link($obj['InfrastructureCategory']['name'], array('plugin' => false, 'controller' => 'InfrastructureTypes', 'action' => 'index', 'category_id' => $obj['InfrastructureCategory']['id'])); ?></td>
			</tr>
			<?php endforeach ?>
		</tbody>
	</table>
</div>
<?php $this->end(); ?>