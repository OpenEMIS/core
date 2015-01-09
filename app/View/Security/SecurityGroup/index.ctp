<?php
echo $this->Html->css('search', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Groups'));
$this->start('contentActions');
	if($_add) {
		echo $this->Html->link($this->Label->get('general.add'), array('action' => $model, 'add'), array('class' => 'divider'));
	}
$this->end();

$this->start('contentBody');
?>

<?php
echo $this->element('layout/search', array(
	'model' => $model, 
	'placeholder' => 'Group Name', 
	'formOptions' => array('url' => array('controller' => 'Security', 'action' => 'SecurityGroup'))
))
?>
<?php if (!empty($data)) : ?>
<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered table-sortable">
		<thead>
			<tr>
				<th><?php echo $this->Paginator->sort('name') ?></th>
				<th><?php echo __('No of Users') ?></th>
			</tr>
		</thead>
		
		<tbody>
		<?php 
			foreach ($data as $obj):
				$id = $obj[$model]['id'];
				$name = $this->Utility->highlight($search, $obj[$model]['name']);
				
		?>
			<tr>
				<td><?php echo $this->Html->link($name, array('action' => $model, 'view', $obj[$model]['id']), array('escape' => false)); ?></td>
				<td><?php echo $obj[0]['no_of_users'] ?></td>
			</tr>
		<?php endforeach ?>
		</tbody>
	</table>
</div>

<?php endif ?>
<?php echo $this->element('layout/pagination', array('displayCount' => false)) ?>
<?php $this->end(); ?>
