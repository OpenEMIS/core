<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->script('institution_site', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $assessmentName);

$this->start('contentActions');
echo $this->Html->link(__('Back'), array('action' => 'assessments'), array('class' => 'divider'));
if ($_edit) {
	echo $this->Html->link(__('Edit'), array('action' => 'assessmentsEdit', $selectedClass, $assessmentId, $selectedItem), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');

echo $this->Form->create('InstitutionSiteClassStudent', array(
	'inputDefaults' => array('label' => false, 'div' => false, 'autocomplete' => 'off'),
	'url' => array('controller' => $this->params['controller'], 'action' => 'assessmentsResults')
));
?>

<div id="results" class="content_wrapper assessments">
	<div class="topDropDownWrapper page-controls" url="InstitutionSites/assessmentsResults/<?php echo $selectedYear; ?>/<?php echo $assessmentId; ?>">
		<?php
		echo $this->Form->input('class_id', array('options' => $classOptions, 'value' => $selectedClass, 'id' => 'classId', 'class' => 'form-control', 'onchange' => 'objInstitutionSite.reloadAssessmentsPage(this)'));
		echo $this->Form->input('assessment_item_id', array('options' => $itemOptions, 'value' => $selectedItem, 'id' => 'assessmentItemId', 'class' => 'form-control', 'onchange' => 'objInstitutionSite.reloadAssessmentsPage(this)'));
		?>
	</div>
	<table class="table table-striped table-hover table-bordered" style="margin-top: 15px;">
		<thead>
			<tr>
				<th class="table_cell cell_id_no"><?php echo __('OpenEMIS ID'); ?></th>
				<th class="table_cell"><?php echo __('Student Name'); ?></th>
				<th class="table_cell cell_marks"><?php echo __('Marks'); ?></th>
				<th class="table_cell cell_grading"><?php echo __('Grading'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($data as $obj) { ?>
				<tr>
					<td class="table_cell"><?php echo $obj['Student']['identification_no']; ?></td>
					<td class="table_cell"><?php echo sprintf('%s %s %s', $obj['Student']['first_name'], $obj['Student']['middle_name'], $obj['Student']['last_name']); ?></td>
					<td class="table_cell center">
						<?php
						$marks = $obj['AssessmentItemResult']['marks'];
						if (is_null($marks) || strlen(trim($marks)) == 0) {
							echo __('Not Recorded');
						} else {
							if ($marks < $obj['AssessmentItem']['min']) {
								echo sprintf('<span class="red">%s</span>', $marks);
							} else {
								echo $marks;
							}
						}
						?>
					</td>
					<td class="table_cell center"><?php echo $obj['AssessmentResultType']['name']; ?></td>
				</tr>
			<?php } ?>
		</tbody>
	</table>
</div>
<?php
echo $this->Form->end();
$this->end();
?>
