<?php
//echo $this->Html->css('table', 'stylesheet', array('inline' => false));
//2echo $this->Html->css('institution', 'stylesheet', array('inline' => false));
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->script('config', false);
echo $this->Html->script('/Quality/js/rubrics', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($subheader));
$this->start('contentActions');

$this->end();
$this->start('contentBody');
?>

<?php echo $this->element('alert'); ?>
<?php
$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $this->action), 'file');
echo $this->Form->create($modelName, $formOptions);
?>
<?php //echo $this->Form->input('institution_id', array('type' => 'hidden')); ?>
<?php
if (!empty($this->data[$modelName]['id'])) {
	echo $this->Form->input('id', array('type' => 'hidden'));
}
?>
<?php
$disabled = 'false';
if ($type != 'add') {
	$disabled = 'disabled';
}

echo $this->Form->input('name', array('disabled' => $disabled));
echo $this->Form->input('description');

echo $this->Form->input('weighting', array('label' => array('text' => __('Weightage'), 'class' => 'col-md-3 control-label'), 'disabled' => $disabled, 'options' => $weightingOptions));

echo $this->Form->input('pass_mark', array('disabled' => $disabled));

if ($type == 'add' || empty($this->data[$modelName]['security_role_id'])) :
	echo $this->Form->input('security_role_id', array('options' => $roleOptions));
else :
	?>
	<div class="form-group">
		<label class="col-md-3 control-label"><?php echo __('Security Role'); ?></label>
		<div class="col-md-4">
			<?php echo $roleOptions[$this->data[$modelName]['security_role_id']]; ?>
		</div>
	</div>
<?php endif;?> 
<div class="form-group">
	<label class="col-md-3 control-label"><?php echo __('Target Grades'); ?></label>
	<div class="col-md-4">
		<?php
		$tableData = array();
		foreach($rubricGradesOptions as $obj) {
			$row = array();
			$row[] = array($obj, array('colspan'=>2));
			//$row[] = $this->Html->link('', '', array('class' => 'void icon_delete', 'onclick'=>'rubricsTemplate.removeRubricTemplateGrade(this)'));//'<span class="icon_delete" onclick="rubricsTemplate.removeRubricTemplateGrade(this)" title="Delete"></span>';

			$tableData[] = $row;
		}
		?>
		<div id='gradeWraper' class="table-responsive">
			<table class="table table-striped table-hover table-bordered">
				<thead>
					<tr class="even" ></tr>
					<tr>
						<th colspan="2"><?php echo __('Selected Grade(s)');?></th>
					</tr>
				</thead>
				<tbody><?php echo $this->Html->tableCells($tableData); ?></tbody>
			</table>
		</div>
		<?php /*<div id='gradeWraper' class="table" style="width:247px;">
			<div class="table_body" style="display:table;">
				
					<div class="table_row " row-id="0">
						<div class="table_cell cell_description" style="width:90%">
							<?php echo $this->Form->input('RubricsTemplateGrade.0.education_grade_id', array('label' => false, 'options' => $gradeOptions, 'style' => array('width:200px'))); ?> 
						</div>
						<div class="table_cell cell_delete">
							<!--<span class="icon_delete" onclick="rubricsTemplate.removeRubricTemplateGrade(this)" title="Delete"></span>-->
						</div>
					</div>
				<?php endif; ?>
			</div>
		</div>*/ ?>
	</div>
</div>
<div class="form-group">
	<div class="col-md-3 control-label">&nbsp;</div>
	<div class="col-md-4"><a class="void icon_plus" onclick="rubricsTemplate.addRubricTemplateGrade(this)" url="Quality/rubricsTemplatesAjaxAddGrade"  href="javascript: void(0)"><?php echo __('Add Grade'); ?></a></div>
</div>
<?php echo $this->FormUtility->getFormButtons(array('cancelURL' =>array('action' => 'rubricsTemplatesView', $id))); ?>

<?php echo $this->Form->end(); ?>

<?php $this->end(); ?>