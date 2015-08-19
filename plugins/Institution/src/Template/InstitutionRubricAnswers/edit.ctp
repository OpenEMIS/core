<?php
echo $this->Html->script('Institution.institution_rubric_answers', ['block' => true]);
?>
<?php
$this->extend('OpenEmis./Layout/Panel');
$this->start('toolbar');
	foreach ($toolbarButtons as $key => $btn) {
		if ($btn['type'] == 'button') {
			echo $this->Html->link($btn['label'], $btn['url'], $btn['attr']);
		} else if ($btn['type'] == 'element') {
			echo $this->element($btn['element'], $btn['data'], $btn['options']);
		}
	}
$this->end();

$this->start('panelBody');
	$template = $this->ControllerAction->getFormTemplate();
	$formOptions = $this->ControllerAction->getFormOptions();
	$this->Form->templates($template);
	
	echo $this->Form->create($data, $formOptions);
?>
	<?php
		$sectionOrder = 0;
		$headerOrder = 0;
		$criteriaOrder = 0;
		// pr($data->rubric_template_options);
	?>
	<div class="clearfix"></div>
		<div class="clearfix">
			<?php
				echo $this->Form->input($alias.".rubric_template_id", [
					'label' => $this->Label->get('InstitutionRubricAnswers.rubric_template_id'),
					'type' => 'string',
					'value' => $data->rubric_template->name,
					'readonly' => 'readonly'
				]);
				echo $this->Form->hidden("$alias.id");
			?>
		</div>
	</div>
	<div class="table-responsive">
		<table class="table table-bordered">
			<thead>
				<tr>
					<th><?= $data->rubric_section->order; ?></th>
					<th><?= $this->Label->get('InstitutionRubricAnswers.rubric_section_id'); ?></th>
					<th colspan="<?= $data->count; ?>"><?= $data->rubric_section->name; ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($data->rubric_criterias as $criteriaObj) : ?>
					<?php $sectionOrder = $data->rubric_section->order; ?>
					<?php
						// pr($criteriaObj);die;
					?>
					<?php if ($criteriaObj->type == 1) : ?>
						<!-- Header -->
						<?php $criteriaOrder = 0; ?>
						<tr>
							<td><?= $sectionOrder . "." . ++$headerOrder; ?></td>
							<td><strong><?= __('Header'); ?></strong></td>
							<td colspan="<?= $data->count; ?>"><?= $criteriaObj->name; ?></td>
						</tr>
						<!-- End -->
					<?php elseif ($criteriaObj->type == 2) : ?>
						<?php
							$criteriaOptions = [];
							foreach ($criteriaObj->rubric_criteria_options as $rubricCriteriaOption) {
								$templateOptionId = $rubricCriteriaOption->rubric_template_option_id;
								$criteriaOptions[$templateOptionId] = $rubricCriteriaOption;
							}
						?>
						<!-- Criteria -->
						<tr>
							<td class="active" rowspan="5"><?= $sectionOrder . "." . $headerOrder . "." . ++$criteriaOrder; ?></td>
							<td class="active"><strong><?= __('Criteria'); ?></strong></td>
							<td class="active" align="center" colspan="<?= $data->count; ?>"><?= __('Descriptors'); ?></td>
						</tr>
						<!-- End -->
						<!-- Level -->
						<tr>
							<td class="active"><?= __('Level'); ?></td>
							<?php foreach ($data->rubric_template_options as $rubricTemplateOption) : ?>
								<td><?= $rubricTemplateOption['name']; ?></td>
							<?php endforeach ?>				
						</tr>
						<!-- End -->
						<tr class="criteriaRow">
							<td>
								<?php
									$criteriaAnswerId = 0;
									$rubricSectionId = $criteriaObj->rubric_section_id;
									$rubricCriteriaId = $criteriaObj->id;

									$fieldPrefix = $alias . '.institution_rubric_answers.' . $rubricCriteriaId;
									// if(isset($this->request->data['InstitutionSiteQualityRubricAnswer'][$rubricCriteriaId])) {
									// 	echo $this->Form->hidden("InstitutionSiteQualityRubricAnswer.$rubricCriteriaId.id");
									// 	$criteriaAnswerId = $this->request->data['InstitutionSiteQualityRubricAnswer'][$rubricCriteriaId]['rubric_criteria_option_id'];
									// }
									echo $criteriaObj->name;
									echo $this->Form->hidden("$fieldPrefix.rubric_section_id", ['value' => $rubricSectionId]);
									echo $this->Form->hidden("$fieldPrefix.rubric_criteria_id", ['value' => $rubricCriteriaId]);
									echo $this->Form->hidden("$fieldPrefix.rubric_criteria_option_id", ['class' => 'criteriaAnswer']);
								?>
							</td>
							<?php foreach ($data->rubric_template_options as $obj) : ?>
								<?php
									$templateOptionId = $obj->id;
									$criteriaOptionId = isset($criteriaOptions[$templateOptionId]['id']) ? $criteriaOptions[$templateOptionId]['id'] : 0;
									$criteriaOptionName = isset($criteriaOptions[$templateOptionId]['name']) ? $criteriaOptions[$templateOptionId]['name'] : __('N.A.');
								?>
								<?php if ($data->status == 0 || $data->status == 1) : ?>
									<?php $bgColor = $obj->color; ?>
									<td id="criteriaOption_<?= $criteriaOptionId; ?>" class="criteriaCell" style="cursor: pointer;" onclick="$(this).parents().children('td').find('input[type=hidden].criteriaAnswer').val(<?= $criteriaOptionId; ?>);InstitutionRubricAnswer.changeBgColor(this);">
										<?php
											echo $criteriaOptionName;
											echo $this->Form->hidden("criteria.row".$rubricCriteriaId.".cell".$templateOptionId.".bgColor", ['class' => 'criteriaCell', 'value' => $bgColor]);
										?>
									</td>
								<?php else : ?>
									<?php $bgColor = $criteriaAnswerId == $criteriaOptionId ? $rubricTemplateOption['color'] : 'white'; ?>
									<td style="background-color: <?php echo $bgColor; ?>;">
										<?php
											echo $criteriaOptionName;
											echo $this->Form->hidden("criteria.row".$rubricCriteriaId.".cell".$templateOptionId.".bgColor", ['class' => 'criteriaCell', 'value' => $bgColor]);
										?>
									</td>
								<?php endif ?>
							<?php endforeach ?>
						</tr>
						<!-- Weighting -->
						<tr>
							<td class="active"></td>
							<td class="active" align="center" colspan="<?php echo $data->count; ?>"><?php echo __('Weighting') ?></td>
						</tr>
						<tr>
							<td></td>
							<?php foreach ($data->rubric_template_options as $obj) : ?>
								<td><?= $obj->weighting; ?></td>
							<?php endforeach ?>
						</tr>
						<!-- End -->
						<?php
							// pr($rubricTemplateOptions);
							// pr($fieldPrefix);
							// pr($sectionOrder);
							// pr($criteriaOptions);
						?>
					<?php endif ?>
				<?php endforeach ?>
			</tbody>
		</table>
	</div>

<?php
	// pr($data);
	echo $this->ControllerAction->getFormButtons();
	echo $this->Form->end();
$this->end();
?>
