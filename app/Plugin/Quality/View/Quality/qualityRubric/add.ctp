<?php
//echo $this->Html->css('table', 'stylesheet', array('inline' => false));
//2echo $this->Html->css('institution', 'stylesheet', array('inline' => false));
echo $this->Html->script('app.date', false);
echo $this->Html->script('config', false);
echo $this->Html->script('Quality.quality.rubric', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($subheader));
$this->start('contentActions');

$this->end();
$this->start('contentBody');

$actionName = $this->action;
$formOptions = array('controller' => 'Quality', 'action' => $actionName, 'plugin' => 'Quality');
$formOptions = array_merge($formOptions, $this->params['pass']);
$pathId = !empty($this->data[$modelName]['id']) ? '/' . $this->data[$modelName]['id'] : '';
echo $this->Form->create($modelName, array(
	'url' => $formOptions,
	'link' => 'Quality/' . $this->action . $pathId,
	'class' => 'form-horizontal',
	'type' => 'file',
	'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
));
?>
<?php
if (!empty($this->data[$modelName]['id'])) {
	echo $this->Form->input('id', array('type' => 'hidden'));
}
?>

<?php echo $this->Form->input('institution_site_id', array('type' => 'hidden')); ?>

<div class="form-group">
	<label class="col-md-3 control-label"><?php echo __('School Year'); ?></label>
	<div class="col-md-4">
		<?php
		if ($type == 'add') {
			echo $this->Form->input('school_year_id', array('id' => 'schoolYearId', 'options' => $schoolYearOptions, 'onChange' => 'QualityRubric.updateURL(this)', 'class' => 'form-control'));
		} else {
			if (isset($schoolYearOptions[$this->data['QualityInstitutionRubric']['school_year_id']])) {
				echo $schoolYearOptions[$this->data['QualityInstitutionRubric']['school_year_id']];
			} else {
				echo $schoolYearOptions[0];
			}
		}
		?>
	</div>
</div>
<div class="form-group">
	<label class="col-md-3 control-label"><?php echo __('Grade'); ?></label>
	<div class="col-md-4">
		<?php
		if ($type == 'add') {
			echo $this->Form->input('institution_site_class_grade_id', array('id' => 'institutionSiteClassGradeId', 'options' => $gradeOptions, 'onChange' => 'QualityRubric.updateURL(this)', 'class' => 'form-control'));
		} else {
			if (isset($gradeOptions[$this->data['QualityInstitutionRubric']['institution_site_class_grade_id']])) {
				echo $gradeOptions[$this->data['QualityInstitutionRubric']['institution_site_class_grade_id']];
			} else {
				echo $gradeOptions[0];
			}
		}
		?>
	</div>
</div>
<div class="form-group">
	<label class="col-md-3 control-label"><?php echo __('Class'); ?></label>
	<div class="col-md-4">
		<?php
		if ($type == 'add') {
			echo $this->Form->input('institution_site_class_id', array('id' => 'institutionSiteClassId', 'options' => $classOptions, 'onChange' => 'QualityRubric.updateURL(this)', 'class' => 'form-control'));
		} else {
			if (isset($classOptions[$this->data['QualityInstitutionRubric']['institution_site_class_id']])) {
				echo $classOptions[$this->data['QualityInstitutionRubric']['institution_site_class_id']];
			} else {
				echo $classOptions[0];
			}
		}
		?>
	</div>
	<div class="value"><?php ?></div>
</div>
<div class="form-group">
	<label class="col-md-3 control-label"><?php echo __('Name'); ?></label>
	<div class="col-md-4">
		<?php
		if ($type == 'add') {
			echo $this->Form->input('rubric_template_id', array('id' => 'rubricsTemplateId', 'options' => $rubricOptions, 'onChange' => 'QualityRubric.updateURL(this)', 'class' => 'form-control'));
		} else {
			if (isset($rubricOptions[$this->data['QualityInstitutionRubric']['rubric_template_id']])) {
				echo $rubricOptions[$this->data['QualityInstitutionRubric']['rubric_template_id']];
			} else {
				echo $rubricOptions[0];
			}
		}
		?>
	</div>
</div>


<div class="form-group">
	<label class="col-md-3 control-label"><?php echo __('Teacher'); ?></label>
	<div class="col-md-4">
		<?php
		if ($type == 'add') {
			echo $this->Form->input('teacher_id', array('id' => 'institutionSiteTeacherId', 'options' => $teacherOptions, 'onChange' => 'QualityRubric.updateURL(this)', 'class' => 'form-control'));
		} else {
			if (isset($teacherOptions[$this->data['QualityInstitutionRubric']['teacher_id']])) {
				echo $teacherOptions[$this->data['QualityInstitutionRubric']['teacher_id']];
			} else {
				echo $teacherOptions[0];
			}
		}
		?>
	</div>
</div>
<div class="form-group">
	<label class="col-md-3 control-label"><?php echo __('Evaluator'); ?></label>
	<div class="col-md-4">
		<?php
		if ($type == 'add') {
			echo $this->Form->input('evaluator', array('disabled' => true, 'class' => 'form-control'));
		} else {

			echo $this->data['QualityInstitutionRubric']['evaluator'];
		}
		?>
	</div>
</div>
<?php if ($type == 'edit') : ?>
	<div class="form-group">
		<label class="col-md-3 control-label"><?php echo __('Comment'); ?></label>
		<div class="col-md-4">
			<?php echo $this->Form->input('comment', array('type' => 'textarea', 'class' => 'form-control')); ?>
			<br/>
			<div id="image_upload_info" style="clear: both">
				<em>
					<?php echo __("Maximum 150 words per comment"); ?>
				</em>
			</div>
		</div>
	</div>       
<?php endif; ?>
<div class="controls view_controls">

	<input type="submit" value="<?php echo ($type == 'add') ? __("Start") : __("Save"); ?>" class="btn_save btn_right" onclick="return Config.checkValidate();"/>
	<?php
	if ($type == 'add') {
		echo $this->Html->link(__('Cancel'), array('action' => 'qualityRubric'), array('class' => 'btn_cancel btn_left'));
	} else {
		echo $this->Html->link(__('Cancel'), array('action' => 'qualityRubricView', $this->data[$modelName]['id']), array('class' => 'btn_cancel btn_left'));
	}
	?>
</div>

<?php echo $this->Form->end(); ?>

<?php $this->end(); ?>  