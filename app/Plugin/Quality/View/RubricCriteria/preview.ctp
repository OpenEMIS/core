<?php
echo $this->Html->script('/Quality/js/rubric', false);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);
$this->start('contentActions');
if (!empty($selectedSection)) {
	echo $this->Html->link($this->Label->get('general.back'), array('action' => $model, 'index', 'template' => $selectedTemplate, 'section' => $selectedSection), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
echo $this->element($tabsElement, array(), array('plugin' => $this->params['plugin']));
echo $this->element($controlsElement, array(), array('plugin' => $this->params['plugin']));
?>

<div class="table-responsive">
	<table class="table table-bordered">
		<tbody>
			<?php if (!empty($data)) : ?>
				<?php foreach ($data as $key => $obj) : ?>
					<?php if ($obj['RubricCriteria']['type'] == 1) : ?>
						<tr>
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
							<td class="active"><strong><?php echo __('Criteria'); ?></strong></td>
							<td class="active" align="center" colspan="<?php echo $rubricTemplateOptionCount; ?>"><?php echo __('Descriptors') ?></td>
						</tr>
						<tr>
							<td class="active"><?php echo __('Level'); ?></td>
							<?php foreach ($rubricTemplateOptions as $rubricTemplateOption) : ?>
								<td><?php echo $rubricTemplateOption['name']; ?></td>
							<?php endforeach ?>				
						</tr>
						<tr class="criteriaRow<? echo $key; ?>">
							<td><?php echo $obj['RubricCriteria']['name']; ?></td>
							<?php foreach ($rubricTemplateOptions as $rubricTemplateOption) : ?>
								<?php
									$templateOptionId = $rubricTemplateOption['id'];
									$bgColor = $rubricTemplateOption['color'];
									$criteriaOptionName = isset($criteriaOptions[$templateOptionId]['name']) ? $criteriaOptions[$templateOptionId]['name'] : __('N.A.');
								?>
								<td class="criteriaCell" style="cursor: pointer;" onclick="Rubric.changeBgColor(this);">
									<?php
										echo $criteriaOptionName;
										echo $this->Form->hidden("row".$key.".cell".$templateOptionId.".bgColor", array('class' => 'criteriaCell', 'value' => $bgColor));
									?>
								</td>
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

<?php
$this->end();
?>
