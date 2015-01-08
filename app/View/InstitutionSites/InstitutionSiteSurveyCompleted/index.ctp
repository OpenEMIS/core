<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Label->get("$model.title"));
$this->start('contentActions');
$this->end();

$this->start('contentBody');
?>
<?php echo $this->element('alert'); ?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th><?php echo __('Name'); ?></th>
				<th><?php echo __('Academic Period'); ?></th>
				<th><?php echo __('Date Completed'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($data as $obj) : ?>
				<tr>
					<td><?php echo $this->Html->link($obj['SurveyTemplate']['name'], array('action' => $model, 'view', $obj[$model]['id'])) ?></td>
					<td><?php echo $obj['SurveyStatusPeriod']['AcademicPeriod']['name'] ?></td>
					<td><?php echo !empty($obj[$model]['modified']) ? $obj[$model]['modified'] : $obj[$model]['created']; ?></td>
				</tr>
			<?php endforeach ?>
		</tbody>
	</table>
</div>

<?php $this->end(); ?>