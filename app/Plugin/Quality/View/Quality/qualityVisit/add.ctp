<?php
echo $this->Html->css('attachments', 'stylesheet', array('inline' => false));
echo $this->Html->script('app.date', false);
echo $this->Html->script('config', false);
echo $this->Html->script('Quality.quality.visit', false);

echo $this->Html->css('../js/plugins/fileupload/bootstrap-fileupload', array('inline' => false));
echo $this->Html->script('plugins/fileupload/bootstrap-fileupload', false);

echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($subheader));
$this->assign('contentId','quality_visit');
$this->assign('contentClass','edit add');
$this->start('contentActions');
if (!empty($this->data[$model]['id'])) {
	$redirectAction = array('action' => 'qualityVisitView', $this->data[$model]['id']);
} else {
	$redirectAction = array('action' => 'qualityVisit');
}
echo $this->Html->link($this->Label->get('general.back'), $redirectAction, array('class' => 'divider'));

$this->end();
$this->start('contentBody');
?>
	<?php
	$actionName = $this->action;
	$formOptions = $this->FormUtility->getFormOptions(array('plugin' => 'Quality', 'controller' => 'Quality', 'action' => $actionName));

	$pathId = !empty($this->data[$modelName]['id']) ? '/' . $this->data[$modelName]['id'] : '';

	$formOptions['link'] = 'Quality/' . $this->action . $pathId;
	$formOptions['type'] = 'file';
	$formOptions['class'] = 'form-horizontal';
	$formOptions['deleteUrl'] = "Quality/qualityVisitAjaxRemoveAttachment/";
	$labelOptions = $formOptions['inputDefaults']['label'];
	echo $this->Form->create($modelName, $formOptions);

	if (!empty($this->data[$modelName]['id'])) {
		echo $this->Form->input('id', array('type' => 'hidden'));
	}
	echo $this->Form->input('maxFileSize', array('type' => 'hidden', 'name' => 'MAX_FILE_SIZE', 'value' => (2 * 1024 * 1024)));
	echo $this->Form->input('institution_site_id', array('type' => 'hidden'));

	echo $this->FormUtility->datepicker('date', array('id' => 'date'));
	echo $this->Form->input('school_year_id', array('id' => 'schoolYearId', 'options' => $schoolYearOptions,'onChange' => 'QualityVisit.updateURL(this)'));
	$labelOptions['text'] = $this->Label->get('general.grade');
	echo $this->Form->input('education_grade_id', array('id' => 'educationGradeId', 'options' => $gradesOptions, 'label' => $labelOptions, 'onChange' => 'QualityVisit.updateURL(this)'));
	$labelOptions['text'] = $this->Label->get('general.section');
	echo $this->Form->input('institution_site_section_id', array('id' => 'institutionSiteSectionId', 'options' => $sectionOptions, 'label' => $labelOptions, 'onChange' => 'QualityVisit.updateURL(this)'));
	echo $this->Form->input('staff_id', array('id' => 'staffId', 'options' => $staffOptions,'onChange' => 'QualityVisit.updateURL(this)'));
	echo $this->Form->input('evaluator', array('disabled' => true));
	$labelOptions['text'] = $this->Label->get('general.type');
	echo $this->Form->input('quality_visit_type_id', array('id' => 'qualityTypeId', 'options' => $visitOptions, 'label' => $labelOptions, 'onChange' => 'QualityVisit.updateURL(this)'));
	echo $this->Form->input('comment', array('type' => 'textarea'));
	?>
    <div class="row">
        <label class="col-md-3 control-label"></label>
        <div class="col-md-4">
            <div id="image_upload_info" style="clear: both">
                <em>
					<?php echo __("Maximum 150 words per comment"); ?>
                </em>
            </div>
        </div>
    </div>
	<?php
	$multiple = array('multipleURL' => $this->params['controller'] . "/qualityVisitAjaxAddField/");
	echo $this->Form->hidden('maxFileSize', array('name' => 'MAX_FILE_SIZE', 'value' => (2 * 1024 * 1024)));
	echo $this->element('templates/file_upload', compact('multiple'));

	if (!empty($attachments)) {
		$tableHeaders = array(__('File(s)'), '&nbsp;');
		$tableData = array();
		foreach ($attachments as $obj) {
			$row = array();
			$row[] = array($obj['QualityInstitutionVisitAttachment']['file_name'], array('file-id' => $obj['QualityInstitutionVisitAttachment']['id']));
			$row[] = '<span class="icon_delete" title="' . $this->Label->get('general.delete') . '" onClick="jsForm.deleteFile(' . $obj['QualityInstitutionVisitAttachment']['id'] . ')"></span>';
			$tableData[] = $row;
		}
		echo $this->element('templates/file_list', compact('tableHeaders', 'tableData'));
		
	}
	echo $this->FormUtility->getFormButtons(array('cancelURL' => $redirectAction));
	echo $this->Form->end();
	?>

<?php $this->end(); ?>  