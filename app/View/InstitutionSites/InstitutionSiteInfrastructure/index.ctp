<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Infrastructure') . (isset($levelName) ? ' - ' . __($levelName) : ''));

$this->start('contentActions');
if ($_add && isset($levelId)) {
	echo $this->Html->link($this->Label->get('general.add'), array('action' => $model, 'add', $levelId), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
?>

<?php if (isset($levelOptions)) : ?>
<div class="row page-controls">
	<div class="col-md-3">
		<?php
		echo $this->Form->input('infrastructure_level_id', array(
			'id' => 'InfrastructureLevelId',
			'label' => false,
			'div' => false,
			'class' => 'form-control',
			'options' => $levelOptions,
			'default' => $levelId,
			'onchange' => 'jsForm.change(this)',
			'url' => $this->params['controller'] . '/' . $model . '/index'
		));
		?>
	</div>
</div>
<?php endif ?>

<?php if (isset($data)) : ?>
<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<?php if(!empty($parentLevel)):?>
					<th><?php echo __($parentLevel['InfrastructureLevel']['name']); ?></th>
				<?php endif;?>
				<th><?php echo __('Code'); ?></th>
				<th><?php echo __('Name'); ?></th>
				<th><?php echo __('Type'); ?></th>
			</tr>
		</thead>

		<tbody>
			<?php
			foreach ($data as $id => $obj) {
				$infrastructure = $obj['InstitutionSiteInfrastructure'];
				$type = $obj['InfrastructureType'];
				$i = 0;
				?>
				<tr>
					<?php if(!empty($parentLevel)):?>
						<td><?php echo !empty($obj['Parent']) ? $obj['Parent']['name'] : ''; ?></td>
					<?php endif;?>
					<td><?php echo $infrastructure['code']; ?></td>
					<td><?php echo $this->Html->link($infrastructure['name'], array('action' => $model, 'view', $infrastructure['id']), array('escape' => false)); ?></td>
					<td><?php echo $type['name']; ?></td>
				</tr>
			<?php }  ?>
		</tbody>
	</table>
</div>
<?php endif ?>
<?php $this->end(); ?>
