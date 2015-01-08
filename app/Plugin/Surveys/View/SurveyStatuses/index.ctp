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

<div class="row page-controls">
	<?php
		echo $this->Form->input('survey_status', array(
			'class' => 'form-control',
			'label' => false,
			'options' => $surveyStatusOptions,
			'default' => $selectedSurveyStatus,
			'div' => 'col-md-3',
			'url' => $this->params['controller'] . '/index',
			'onchange' => 'jsForm.change(this)'
		));
	?>
</div>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th><?php echo __('Name'); ?></th>
				<th><?php echo __('Module'); ?></th>
				<th><?php echo __('Date Enabled'); ?></th>
				<th><?php echo __('Date Disabled'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($data as $obj) : ?>
				<tr>
					<td><?php echo $this->Html->link($obj['SurveyTemplate']['name'], array('action' => 'view', $obj['SurveyStatus']['id'])) ?></td>
					<td><?php echo $moduleOptions[$obj['SurveyTemplate']['survey_module_id']] ?></td>
					<td><?php echo $obj['SurveyStatus']['date_enabled'] ?></td>
					<td><?php echo $obj['SurveyStatus']['date_disabled'] ?></td>
				</tr>
			<?php endforeach ?>
		</tbody>
	</table>
</div>

<?php $this->end(); ?>