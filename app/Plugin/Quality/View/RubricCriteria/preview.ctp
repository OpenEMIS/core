<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);
$this->start('contentActions');
if (!empty($selectedSection)) {
	echo $this->Html->link($this->Label->get('general.back'), array('action' => $model, 'index', 'template' => $selectedTemplate, 'section' => $selectedSection), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
echo $this->element('/../../Plugin/Quality/View/QualityRubrics/nav_tabs');
echo $this->element('/../../Plugin/Quality/View/QualityRubrics/controls');
?>

<div class="table-responsive">
	<table class="table table-bordered">
		<tbody>
			<?php if (!empty($data)) : ?>
				<?php foreach ($data as $obj) : ?>
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
						<tr>
							<td><?php echo $obj['RubricCriteria']['name']; ?></td>
							<?php foreach ($rubricTemplateOptions as $key => $rubricTemplateOption) : ?>
								<?php
									$bgColor = 'white';
									$highlightBgColor = "#" . $rubricTemplateOption['color'];
									$criteriaOptionName = isset($criteriaOptions[$key]['name']) ? $criteriaOptions[$key]['name'] : __('N.A.');
								?>
								<td onmouseout='$(this).css("background-color", "<?php echo $bgColor; ?>");' onmouseover='$(this).css("background-color", "<?php echo $highlightBgColor; ?>");'><?php echo $criteriaOptionName; ?></td>
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
