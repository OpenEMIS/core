<?php
echo $this->Html->css('search', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Label->get("$model.title"));
$this->start('contentActions');
	if ($_add) {
		echo $this->Html->link(__('Add'), array('action' => $model, 'add'), array('class' => 'divider'));
	}
$this->end();

$this->start('contentBody');
$model = 'SecurityUser';
?>

<?php 
echo $this->element('layout/search', array(
	'model' => $model, 
	'placeholder' => 'Username, First Name or Last Name', 
	'formOptions' => array('url' => array('controller' => 'Security', 'action' => $model))
))
?>

<?php if (!empty($data)) : ?>
<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered table-sortable">
		<thead>
			<tr>
				<th><?php echo $this->Paginator->sort('SecurityUser.openemis_no', __('OpenEMIS ID')) ?></th>
				<th><?php echo $this->Paginator->sort('SecurityUser.username', __('User Name')) ?></th>
				<th><?php echo $this->Paginator->sort('SecurityUser.first_name', __('First Name')) ?></th>
				<th><?php echo $this->Paginator->sort('SecurityUser.last_name', __('Last Name')) ?></th>
				<th><?php echo $this->Paginator->sort('SecurityUser.status', __('Status')) ?></th>
			</tr>
		</thead>
		
		<tbody>
		<?php 
			foreach ($data as $obj):
				$openemis_no = $this->Utility->highlight($search, $obj[$model]['openemis_no']);
				$username = $this->Utility->highlight($search, $obj[$model]['username']);
				$firstName = $this->Utility->highlight($search, $obj[$model]['first_name']);
				$lastName = $this->Utility->highlight($search, $obj[$model]['last_name']);
		?>
			<tr>
				<td><?php echo $this->Html->link($openemis_no, array('action' => $model, 'view', $obj[$model]['id']), array('escape' => false)); ?></td>
				<td><?php echo $username; ?></td>
				<td><?php echo $firstName; ?></td>
				<td><?php echo $lastName; ?></td>
				<td><?php echo $this->Utility->getStatus($obj[$model]['status']); ?></td>
			</tr>
		<?php endforeach ?>
		</tbody>
	</table>
</div>

<?php endif ?>
<?php echo $this->element('layout/pagination', array('displayCount' => false)) ?>
<?php $this->end(); ?>
