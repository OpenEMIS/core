<div class="table-responsive">
	<table class="table table-bordered">
		<tbody>
			<?php if (!empty($rubricCriterias)) : ?>
				<?php
					$headerOrder = 0;
					$criteriaOrder = 0;
				?>
				<?php foreach ($rubricCriterias as $obj) : ?>
					<?php $sectionOrder = $obj['RubricSection']['order']; ?>
					<?php if ($obj['RubricCriteria']['type'] == 1) : ?>
						<?php $criteriaOrder = 0; ?>
						<tr>
							<td><?php echo $sectionOrder . "." . ++$headerOrder; ?></td>
							<td><strong><?php echo __('Header'); ?></strong></td>
							<td colspan="<?php echo $rubricTemplateOptionCount; ?>"><?php echo $obj['RubricCriteria']['name']; ?></td>
						</tr>
					<?php elseif ($obj['RubricCriteria']['type'] == 2) : ?>
						<?php
							$criteriaOptions = array();
							foreach ($obj['RubricCriteriaOption'] as $rubricCriteriaOption) {
								$templateOptionId = $rubricCriteriaOption['rubric_template_option_id'];
								$criteriaOptions[$templateOptionId] = $rubricCriteriaOption;
							}
						?>
						<tr>
							<td class="active" rowspan="5"><?php echo $sectionOrder . "." . $headerOrder . "." . ++$criteriaOrder; ?></td>
							<td class="active"><strong><?php echo __('Criteria'); ?></strong></td>
							<td class="active" align="center" colspan="<?php echo $rubricTemplateOptionCount; ?>"><?php echo __('Descriptors') ?></td>
						</tr>
						<tr>
							<td class="active"><?php echo __('Level'); ?></td>
							<?php foreach ($rubricTemplateOptions as $rubricTemplateOption) : ?>
								<td><?php echo $rubricTemplateOption['name']; ?></td>
							<?php endforeach ?>				
						</tr>
						<tr class="criteriaRow">
							<td>
								<?php
									$criteriaAnswerId = 0;
									$rubricSectionId = $obj['RubricCriteria']['rubric_section_id'];
									$rubricCriteriaId = $obj['RubricCriteria']['id'];

									$fieldName = "InstitutionSiteQualityRubricAnswer.".$rubricCriteriaId.".rubric_criteria_option_id";
									$error = $this->Form->isFieldError($fieldName) ? $this->Form->error($fieldName) : '';

									if(isset($this->request->data['InstitutionSiteQualityRubricAnswer'][$rubricCriteriaId])) {
										echo $this->Form->hidden("InstitutionSiteQualityRubricAnswer.$rubricCriteriaId.id");
										$criteriaAnswerId = $this->request->data['InstitutionSiteQualityRubricAnswer'][$rubricCriteriaId]['rubric_criteria_option_id'];
									}
									echo $this->Form->hidden("InstitutionSiteQualityRubricAnswer.$rubricCriteriaId.rubric_section_id", array('value' => $rubricSectionId));
									echo $this->Form->hidden("InstitutionSiteQualityRubricAnswer.$rubricCriteriaId.rubric_criteria_id", array('value' => $rubricCriteriaId));
									echo $this->Form->hidden("InstitutionSiteQualityRubricAnswer.$rubricCriteriaId.rubric_criteria_option_id", array('class' => 'criteriaAnswer'));
								?>
								<div><?php echo $obj['RubricCriteria']['name']; ?></div>
								<?php echo $error; ?>
							</td>
							<?php foreach ($rubricTemplateOptions as $rubricTemplateOption) : ?>
								<?php
									$templateOptionId = $rubricTemplateOption['id'];
									$criteriaOptionId = isset($criteriaOptions[$templateOptionId]['id']) ? $criteriaOptions[$templateOptionId]['id'] : 0;
									$criteriaOptionName = isset($criteriaOptions[$templateOptionId]['name']) ? $criteriaOptions[$templateOptionId]['name'] : __('N.A.');
								?>
								<?php if ($selectedAction == 0 || $selectedAction == 1) : ?>
									<?php $bgColor = $rubricTemplateOption['color']; ?>
									<td id="criteriaOption<?php echo $criteriaOptionId; ?>" class="criteriaCell" style="cursor: pointer;" onclick="$(this).parents().children('td').find('input[type=hidden].criteriaAnswer').val(<?php echo $criteriaOptionId; ?>);InstitutionSiteQualityRubric.changeBgColor(this);">
										<?php
											echo $criteriaOptionName;
											echo $this->Form->hidden("criteria.row".$rubricCriteriaId.".cell".$templateOptionId.".bgColor", array('class' => 'criteriaCell', 'value' => $bgColor));
										?>
									</td>
								<?php else : ?>
									<?php $bgColor = $criteriaAnswerId == $criteriaOptionId ? $rubricTemplateOption['color'] : 'white'; ?>
									<td style="background-color: <?php echo $bgColor; ?>;">
										<?php
											echo $criteriaOptionName;
											echo $this->Form->hidden("criteria.row".$rubricCriteriaId.".cell".$templateOptionId.".bgColor", array('class' => 'criteriaCell', 'value' => $bgColor));
										?>
									</td>
								<?php endif ?>
							<?php endforeach ?>
						</tr>
						<tr>
							<td class="active"></td>
							<td class="active" align="center" colspan="<?php echo $rubricTemplateOptionCount; ?>"><?php echo __('Weighting') ?></td>
						</tr>
						<tr>
							<td></td>
							<?php foreach ($rubricTemplateOptions as $rubricTemplateOption) : ?>
								<td><?php echo $rubricTemplateOption['weighting']; ?></td>
							<?php endforeach ?>
						</tr>
					<?php endif ?>
				<?php endforeach ?>
			<?php endif ?>
		</tbody>
	</table>
</div>
