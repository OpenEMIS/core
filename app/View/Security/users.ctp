<?php
echo $this->Html->css('search', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Users'));
$this->start('contentActions');
	if ($_accessControl->check($this->params['controller'], 'usersAdd')) {
		echo $this->Html->link(__('Add'), array('action' => 'usersAdd'), array('class' => 'divider'));
	}
$this->end();

$this->start('contentBody');
$model = 'SecurityUser';
?>

<?php 
echo $this->element('layout/search', array(
	'model' => $model, 
	'placeholder' => 'Username, First Name or Last Name', 
	'formOptions' => array('url' => array('controller' => 'Security', 'action' => 'users'))
))
?>

<?php if (!empty($data)) : ?>
<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered table-sortable">
		<thead>
			<tr>
				<th><?php echo $this->Paginator->sort('username') ?></th>
				<th><?php echo $this->Paginator->sort('first_name') ?></th>
				<th><?php echo $this->Paginator->sort('last_name') ?></th>
				<th><?php echo $this->Paginator->sort('status') ?></th>
			</tr>
		</thead>
		
		<tbody>
		<?php 
			foreach ($data as $obj):
				$username = $this->Utility->highlight($search, $obj[$model]['username']);
				$firstName = $this->Utility->highlight($search, $obj[$model]['first_name']);
				$lastName = $this->Utility->highlight($search, $obj[$model]['last_name']);
		?>
			<tr>
				<td><?php echo $username; ?></td>
				<td><?php echo $this->Html->link($firstName, array('action' => 'usersView', $obj[$model]['id']), array('escape' => false)); ?></td>
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
