<?php
echo $this->Html->css('search', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('History'));

$this->start('contentActions');
	echo $this->Html->link(__('Back'), array('action' => 'view'), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
echo $this->element('layout/search', array('model' => $model, 'placeholder' => 'Search'))
?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered table-sortable">
		<thead>
			<tr>
				<th><?php echo $this->Paginator->sort('module', $this->Label->get('Activity.model')) ?></th>
				<th><?php echo $this->Paginator->sort('field', $this->Label->get('Activity.field')) ?></th>
				<th><?php echo $this->Label->get('Activity.old_value') ?></th>
				<th><?php echo $this->Label->get('Activity.new_value') ?></th>
				<th><?php echo $this->Paginator->sort('ModifiedUser.first_name', $this->Label->get('Activity.created_user_id')) ?></th>
				<th><?php echo $this->Paginator->sort('created', $this->Label->get('Activity.created')) ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($data as $obj) : ?>
			<tr>
				<td><?php echo $this->Utility->highlight($search, $this->Label->get($obj[$model]['model'] . '.module')) ?></td>
				<td><?php echo $this->Utility->highlight($search, $this->Label->getLabel($obj[$model]['model'], $obj[$model])) ?></td>
				<td><?php echo $this->Utility->highlight($search, $obj[$model]['old_value']) ?></td>
				<td><?php echo $this->Utility->highlight($search, $obj[$model]['new_value']) ?></td>
				<td><?php echo $this->Utility->highlight($search, ModelHelper::getName($obj['ModifiedUser'])) ?></td>
				<td><?php echo $this->Utility->highlight($search, $obj[$model]['created']) ?></td>
			</tr>
			<?php endforeach ?>
		</tbody>
	</table>
</div>

<?php $this->end() ?>
