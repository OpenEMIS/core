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
		$optionCount = 5;
	?>
	<div class="table-responsive">
		<table class="table table-bordered">
			<!-- <thead>
				<tr>
					<th><?= $this->Label->get('general.visible'); ?></th>
					<th colspan="<?= $optionCount; ?>"><?= $this->Label->get('general.name'); ?></th>
				</tr>
			</thead> -->
			<tbody>
				<?php foreach ($data as $entity) : ?>
					<?php $sectionOrder = $entity->rubric_section->order; ?>
					<?php if ($entity->type == 1) : ?>
						<?php $criteriaOrder = 0; ?>
						<tr>
							<td><?= $sectionOrder . "." . ++$headerOrder; ?></td>
							<td><strong><?= __('Header'); ?></strong></td>
							<td colspan="<?= $optionCount; ?>"><?= $entity->name; ?></td>
						</tr>
					<?php elseif ($entity->type == 2) : ?>
						<?php
							$criteriaOptions = array();
							foreach ($entity->rubric_criteria_options as $rubricCriteriaOption) {
								$templateOptionId = $rubricCriteriaOption->rubric_template_option_id;
								$criteriaOptions[$templateOptionId] = $rubricCriteriaOption;
							}
						?>
						<tr>
							<td class="active" rowspan="5"><?= $sectionOrder . "." . $headerOrder . "." . ++$criteriaOrder; ?></td>
							<td class="active"><strong><?= __('Criteria'); ?></strong></td>
							<td class="active" align="center" colspan="<?= $optionCount; ?>"><?= __('Descriptors'); ?></td>
						</tr>

						<?php
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
