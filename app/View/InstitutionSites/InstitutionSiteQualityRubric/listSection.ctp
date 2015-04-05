<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);
$this->start('contentActions');
	echo $this->Html->link($this->Label->get('general.back'), array('action' => $model, 'index', 'status' => $selectedStatus), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th><?php echo __('No.') ?></th>
				<th><?php echo $this->Label->get('RubricSection.name'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php $named = $this->request->params['named']; ?>
			<?php foreach ($data as $key => $obj) : ?>
				<tr>
					<td><?php echo $obj['RubricSection']['order']; ?></td>
					<td>
						<?php
							$actionUrl = array('action' => $model, 'edit', $obj['RubricSection']['id']);
							echo $this->Html->link($obj['RubricSection']['name'], array_merge($actionUrl, $named));
						?>
					</td>
				</tr>
			<?php endforeach ?>
		</tbody>
	</table>
</div>

<?php
$this->end();
?>
