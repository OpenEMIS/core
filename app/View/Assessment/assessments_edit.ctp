<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('assessment', 'stylesheet', array('inline' => false));

echo $this->Html->script('assessment', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Edit National Assessment'));
$this->start('contentActions');
echo $this->Html->link(__('View'), array('action' => 'assessmentsView', $data['id']), array('class' => 'divider'));
$this->end();
$this->assign('contentId', 'assessmentAdd');
$this->assign('contentClass', 'edit');
$this->start('contentBody');
?>
<?php

$formOptions = $this->FormUtility->getFormOptions(array('controller' => 'Assessment','action' => 'assessmentsEdit', $data['id']));
echo $this->Form->create('AssessmentItemType', $formOptions);

echo $this->Form->hidden('id', array('value' => $data['id']));
?>
<fieldset class="section_group info">
	<legend><?php echo __('Assessment Details'); ?></legend>
	<?php echo $this->Form->input('code', array('value' => $data['code'])); ?>
	<?php echo $this->Form->input('name', array('value' => $data['name'])); ?>
	<?php echo $this->Form->input('description', array('type' => 'textarea', 'value' => $data['description'])); ?>
	<div class="row">
		<label class="col-md-3 control-label"><?php echo __('Education Level'); ?></label>
		<div class="col-md-4"><?php echo $data['education_level_name']; ?></div>
	</div>
	<div class="row">
		<label class="col-md-3 control-label"><?php echo __('Education Programme'); ?></label>
		<div class="col-md-4"><?php echo $data['education_programme_name']; ?></div>
	</div>
	<div class="row">
		<label class="col-md-3 control-label"><?php echo __('Education Grade'); ?></label>
		<div class="col-md-4"><?php echo $data['education_grade_name']; ?></div>
	</div>
	<?php
	echo $this->Form->input('visible', array(
		'class' => 'form-control',
		'options' => array(1 => __('Active'), 0 => __('Inactive')),
		'default' => $data['visible'] 
	));
	?>
</fieldset>

<fieldset class="section_group items">
	<legend><?php echo __('Assessment Items'); ?></legend>
	
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
			foreach($data['AssessmentItem'] as $i => $item) {
				$visible = isset($item['visible']) && $item['visible'] == 1;
			?>
			<tr class="table_row <?php echo $visible ? '' : 'inactive'; ?>">
				<?php
				echo $this->Form->hidden('education_grade_subject_id', array(
					'name' => sprintf($fieldName, $i, 'education_grade_subject_id'),
					'value' => $item['education_grade_subject_id']
				));
				echo $this->Form->hidden('id', array('name' => sprintf($fieldName, $i, 'id'), 'value' => $item['id'] > 0 ? $item['id'] : 0));
				echo $this->Form->hidden('code', array('name' => sprintf($fieldName, $i, 'code'), 'value' => $item['code']));
				echo $this->Form->hidden('name', array('name' => sprintf($fieldName, $i, 'name'), 'value' => $item['name']));
				echo $this->Form->hidden('assessment_item_type_id', array('name' => sprintf($fieldName, $i, 'assessment_item_type_id'), 'value' => $data['id']));
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
							'value' => strlen($item['min'])==0 ? 50 : $item['min'],
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
							'value' => strlen($item['max'])==0 ? 100 : $item['max'],
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
	<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
	<?php echo $this->Html->link(__('Cancel'), array('action' => 'index'), array('class' => 'btn_cancel btn_left')); ?>
</div>
<?php echo $this->Form->end(); ?>
<?php $this->end(); ?>
