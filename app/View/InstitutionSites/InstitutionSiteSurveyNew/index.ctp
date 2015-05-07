<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Label->get("$model.title"));
$this->start('contentActions');
$this->end();

$this->start('contentBody');

if (!empty($data)) : ?>
<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered table-sortable">
		<thead>
			<tr>
				<th><?php echo __('Name') ?></th>
				<th><?php echo __('Academic Period') ?></th>
				<th><?php echo __('Complete By') ?></th>
				<th><?php echo __('Description') ?></th>
			</tr>
		</thead>
		
		<tbody>
		<?php
			foreach ($data as $i => $obj):
				foreach ($obj['AcademicPeriod'] as $key => $period) :
				$name = $this->Html->link($obj['SurveyTemplate']['name'], array('action' => $model, 'add', $obj['SurveyTemplate']['id'], $period['AcademicPeriod']['id']));
				$academicPeriod = $period['AcademicPeriod']['name'];
				$completeBy = $period['SurveyStatus']['date_disabled'];
				$description = $obj['SurveyTemplate']['description'];
		?>
			<tr>
				<td><?php echo $name; ?></td>
				<td><?php echo $academicPeriod; ?></td>
				<td><?php echo $completeBy; ?></td>
				<td><?php echo $description; ?></td>
			</tr>
			<?php endforeach ?>
		<?php endforeach ?>
		</tbody>
	</table>
</div>

<?php endif ?>
<?php $this->end(); ?>
