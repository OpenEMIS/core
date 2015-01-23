<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Label->get("$model.title"));
$this->start('contentActions');
$this->end();

$this->start('contentBody');
?>

<div class="survey panel-group" id="survey_accordion" role="tablist" aria-multiselectable="true">
	<?php foreach ($data as $i => $obj) : ?>
		<?php if (!empty($obj['AcademicPeriod'])) : ?>
		<div class="panel panel-default">
			<div class="panel-heading" role="tab" id="survey_heading<?php echo $i; ?>">
				<h4 class="panel-title">
					<a class="collapsed" data-toggle="collapse" data-parent="#survey_accordion" href="#collapse<?php echo $i; ?>" aria-expanded="false" aria-controls="collapse<?php echo $i; ?>">
						<?php echo $obj['SurveyTemplate']['name']; ?>
					</a>
				</h4>
			</div>
			<div id="collapse<?php echo $i; ?>" class="panel-collapse collapse" role="tabpanel" aria-labelledby="survey_heading<?php echo $i; ?>">
				<ul class="list-group">
					<?php foreach ($obj['AcademicPeriod'] as $key => $period) : ?>
						<li class="list-group-item">
							<?php
								if($_add) {
									echo $this->Html->link($period['AcademicPeriod']['name'], array('action' => $model, 'add', $obj['SurveyTemplate']['id'], $period['AcademicPeriod']['id']));
								} else {
									echo $period['AcademicPeriod']['name'];
								}
							?>
							<span class="pull-right"><?php echo __('To be completed by : '); ?><?php echo $period['SurveyStatus']['date_disabled']; ?></span>
						</li>
					<?php endforeach ?>
				</ul>
			</div>
		</div>
		<?php endif ?>
	<?php endforeach ?>
</div>

<?php $this->end(); ?>