<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Label->get("$model.title"));
$this->start('contentActions');
$this->end();

$this->start('contentBody');
?>
<?php echo $this->element('alert'); ?>

<div class="survey panel-group" id="survey_accordion" role="tablist" aria-multiselectable="true">
	<?php foreach ($data as $i => $obj) : ?>
		<div class="panel panel-default">
			<div class="panel-heading" role="tab" id="survey_heading<?php echo $i; ?>">
				<h4 class="panel-title">
					<a class="collapsed" data-toggle="collapse" data-parent="#survey_accordion" href="#collapse<?php echo $i; ?>" aria-expanded="false" aria-controls="collapse<?php echo $i; ?>">
						<?php echo $obj['SurveyTemplate']['name']; ?>
					</a>
					<span><?php echo __('To be completed by : '); ?><?php echo $obj['SurveyStatus']['date_disabled']; ?></span>
				</h4>
			</div>
			<div id="collapse<?php echo $i; ?>" class="panel-collapse collapse" role="tabpanel" aria-labelledby="survey_heading<?php echo $i; ?>">
				<ul class="list-group">
					<?php foreach ($obj['AcademicPeriod'] as $key => $value) : ?>
						<li class="list-group-item">
							<?php echo $this->Html->link($value['name'], array('action' => $model, 'view', $value['SurveyStatusPeriod']['academic_period_id'], $value['SurveyStatusPeriod']['survey_status_id'])) ?>
						</li>
					<?php endforeach ?>
				</ul>
			</div>
		</div>
	<?php endforeach ?>
</div>

<?php $this->end(); ?>
