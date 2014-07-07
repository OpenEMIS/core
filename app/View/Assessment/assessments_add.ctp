<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('assessment', 'stylesheet', array('inline' => false));

echo $this->Html->script('assessment', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Add National Assessment'));
$this->start('contentActions');
echo $this->Html->link(__('List'), array('action' => 'index'), array('class' => 'divider'));
$this->end();
$this->assign('contentId', 'assessmentAdd');
$this->assign('contentClass', 'edit');
$this->start('contentBody');
?>
<?php
$formOptions = $this->FormUtility->getFormOptions(array('controller' => 'Assessment','action' => 'assessmentsAdd'));
echo $this->Form->create('AssessmentItemType', array_merge($formOptions, array('id'=> 'submitForm')));
?>


<fieldset class="section_group info">
	<legend><?php echo __('National Assessment Details'); ?></legend>
	<?php echo $this->Form->input('code'); ?>
	<?php echo $this->Form->input('name'); ?>
	<?php echo $this->Form->input('description', array('type' => 'textarea')); ?>
	<?php
	echo $this->Form->input('education_programme_id', array(
		'options' => $programmeOptions,
		'default' => $selectedProgramme,
		'onchange' => 'Assessment.loadGradeList(this)',
		'url' => 'Assessment/loadGradeList'
	));
	?>
	<?php
	echo $this->Form->input('education_grade_id', array(
		'id' => 'EducationGradeId',
		'options' => $gradeOptions,
		'default' => $selectedGrade,
		'empty' => '-- ' . (empty($gradeOptions) ? __('No Grade Available') : __('Select Grade')) . ' --',
		'onchange' => 'Assessment.loadSubjectList(this)',
		'url' => 'Assessment/loadSubjectList'
	));
	?>
</fieldset>

<fieldset class="section_group items">
	<legend><?php echo __('National Assessment Items'); ?></legend>
	
	<div class="table-responsive">
    <table class="table table-striped table-hover table-bordered">
		<thead class="table_head">
			<tr>
				<th class="cell_checkbox"><input type="checkbox" onchange="jsForm.toggleSelect(this);" /></th>
				<th class="cell_subject_code"><?php echo __('Subject Code'); ?></th>
				<th class=""><?php echo __('Subject Name'); ?></th>
				<th class="cell_number_input"><?php echo __('Minimum'); ?></th>
				<th class="cell_number_input"><?php echo __('Maximum'); ?></th>
			</tr>
		</thead>
		<tbody class="table_body">
			<?php 
			$fieldName = 'data[AssessmentItem][%d][%s]';
			foreach($items as $i => $item) { 
				$visible = isset($item['visible']) && $item['visible'] == 1;
			?>
			<tr class="table_row <?php echo $visible ? '' : 'inactive'; ?>">
				<?php
				echo $this->Form->hidden('education_grade_subject_id', array(
					'name' => sprintf($fieldName, $i, 'education_grade_subject_id'),
					'value' => $item['education_grade_subject_id']
				));
				echo $this->Form->hidden('code', array('name' => sprintf($fieldName, $i, 'code'), 'value' => $item['code']));
				echo $this->Form->hidden('name', array('name' => sprintf($fieldName, $i, 'name'), 'value' => $item['name']));
				?>
				<td class="">
					<input type="checkbox" name="<?php echo sprintf($fieldName, $i, 'visible'); ?>" value="1" autocomplete="off" onChange="jsList.activate(this, '.table_row')" <?php echo $visible ? 'checked="checked"' : ''; ?>/>
				</td>
				<td class=""><?php echo $item['code']; ?></td>
				<td class=""><?php echo $item['name']; ?></td>
				<td class="input">
					<?php 
						echo $this->Form->input('min', array(
							'label' => false,
							'div' => false,
							'name' => sprintf($fieldName, $i, 'min'),
							'value' => $item['min'],
							'maxlength' => 4,
							'onkeypress' => 'return utility.integerCheck(event)'
						));
					?>
				</td>
				<td class="input">
					<?php 
						echo $this->Form->input('max', array(
							'label' => false,
							'div' => false,
							'name' => sprintf($fieldName, $i, 'max'),
							'value' => $item['max'],
							'maxlength' => 4,
							'onkeypress' => 'return utility.integerCheck(event)'
						));
					?>
				</td>
			</tr>
			<?php } ?>
		</tbody>
		</table>
	</div>
</fieldset>

<div class="controls">
	<input type="submit" value="<?php echo __('Add'); ?>" class="btn_save btn_right" />
	<?php echo $this->Html->link(__('Cancel'), array('action' => 'index'), array('class' => 'btn_cancel btn_left')); ?>
</div>
<?php echo $this->Form->end(); ?>

<?php $this->end(); ?>