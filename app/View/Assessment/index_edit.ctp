<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('assessment', 'stylesheet', array('inline' => false));

echo $this->Html->script('assessment', false);
//echo $this->Html->script('jquery.quicksand', false);
//echo $this->Html->script('jquery.sort', false);
echo $this->Html->script('field.option', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Assessments'));
$this->start('contentActions');
if ($_edit && !empty($data)) {
	echo $this->Html->link(__('List'), array('action' => 'index', $selectedProgramme), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');

$formParams = array('controller' => $this->params['controller'], 'action' => 'move', $selectedProgramme);
echo $this->Form->create('AssessmentItemType', array('id' => 'OptionMoveForm', 'url' => $formParams));
echo $this->Form->hidden('id', array('class' => 'option-id'));
echo $this->Form->hidden('move', array('class' => 'option-move'));
echo $this->Form->hidden('gradeId', array('class' => 'option-grade-id'));
echo $this->Form->end();
?>
<div id="assessment" class="content_wrapper">
	<?php
//	echo $this->Form->create('Assessment', array(
//		'inputDefaults' => array('label' => false, 'div' => false, 'autocomplete' => 'off'),
//		'url' => array('controller' => 'Assessment', 'action' => 'indexEdit', $selectedProgramme)
//	));
	?>
	<div class="filter_wrapper">
		<div class="row edit">
			<label class="control-label col-md-3"><?php echo __('Education Programme'); ?></label>
			<div class="col-md-4">
				<?php
				echo $this->Form->input('education_programme_id', array(
					'id' => 'EducationProgrammeId',
					'class' => 'form-control',
					'options' => $programmeOptions,
					'default' => $selectedProgramme,
					'url' => 'Assessment/indexEdit/',
					'onchange' => 'Assessment.switchProgramme(this)'
				));
				?>
			</div>
		</div>
	</div>

	<?php
	$i = 0;
	foreach ($data as $key => $obj) {
		?>
		<fieldset class="section_group">
			<legend><?php echo $obj['name']; ?></legend>
			<table class="table table-striped table-hover table-bordered">
				<thead>
				<th class="cell-visible"><?php echo $this->Label->get('general.visible'); ?></th>
				<th class="cell_code"><?php echo __('Code'); ?></th>
				<th class=""><?php echo __('Name'); ?></th>
				<th class="cell_order"><?php echo __('Order'); ?></th>
				</thead>
				<tbody>
					<?php
					$items = $obj['assessment'][$type];
					if (!empty($items)) :
						$index = 1;
						$fieldindex = 0;
						foreach ($items as $item) :
							$isVisible = $item['visible'] == 1;
							?>
							<tr row-id="<?php echo $item['id']; ?>" grade-id="<?php echo $item['education_grade_id']; ?>">
								<td class="center"><?php echo $this->Utility->checkOrCrossMarker($item['visible']==1); ?></td>
								<td><?php echo $item['code']; ?></td>
								<td><?php echo $item['name']; ?></td>
								<td class="action">
									<?php
									$size = count($items);
									echo $this->element('layout/reorder', compact('index', 'size'));
									$index++;
									?>
								</td>
							</tr>
							<?php
							$fieldindex++;
						endforeach;
					endif;
					?>
				</tbody>
			</table>
		</fieldset>
	

	<?php } ?>

	<?php //echo $this->Form->end(); ?>
</div>
<?php $this->end(); ?>
