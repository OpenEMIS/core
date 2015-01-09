<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);
$this->start('contentActions');
	if ($_add) {
	    echo $this->Html->link(__('Add'), array('action' => 'add'), array('class' => 'divider'));
	}
$this->end();

$this->start('contentBody');
?>
<?php echo $this->element('alert'); ?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th><?php echo __('Name'); ?></th>
				<th><?php echo __('Module'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($data as $obj) : ?>
				<tr>
					<td><?php echo $this->Html->link($obj['SurveyTemplate']['name'], array('action' => 'view', $obj['SurveyTemplate']['id'])) ?></td>
					<td><?php echo $obj['SurveyModule']['name'] ?></td>
				</tr>
			<?php endforeach ?>
		</tbody>
	</table>
</div>

<?php $this->end(); ?>