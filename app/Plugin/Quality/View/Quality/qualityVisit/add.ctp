<?php
//echo $this->Html->css('table', 'stylesheet', array('inline' => false));
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

$this->start('contentBody');
?>

<div id="quality_visit" class="content_wrapper edit add">
    <?php
    $actionName = $this->action;
    //$formOptions = array('controller' => 'Quality', 'action' => $actionName, 'plugin' => 'Quality');
    //$formOptions = array_merge($formOptions, $this->params['pass']);
	
	$formOptions = $this->FormUtility->getFormOptions(array('plugin' => 'Quality', 'controller' => 'Quality', 'action' => $actionName));
	
	$pathId = !empty($this->data[$modelName]['id']) ? '/' . $this->data[$modelName]['id'] : '';
	
	$formOptions['link'] = 'Quality/' . $this->action . $pathId;
	$formOptions['type'] = 'file';
	$formOptions['class'] = 'form-horizontal';
	
	$labelOptions = $formOptions['inputDefaults']['label'];
	echo $this->Form->create($modelName, $formOptions);

    //$pathId = !empty($this->data[$modelName]['id']) ? '/' . $this->data[$modelName]['id'] : '';
//    echo $this->Form->create($modelName, array(
//        'url' => $formOptions,
//        'link' => 'Quality/' . $this->action . $pathId,
//        'type' => 'file',
//		'class' => 'form-horizontal',
//        'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
//    ));
	
    ?>
    <?php
    if (!empty($this->data[$modelName]['id'])) {
        echo $this->Form->input('id', array('type' => 'hidden'));
    }
    ?>
    <?php echo $this->Form->input('maxFileSize', array('type' => 'hidden', 'name' => 'MAX_FILE_SIZE', 'value' => (2 * 1024 * 1024))); ?>
    <?php echo $this->Form->input('institution_site_id', array('type' => 'hidden')); ?>
	<?php 
	echo $this->FormUtility->datepicker('date', array('id' => 'date'));
	
	//$labelOptions['text'] = $this->Label->get('InstitutionSite.institution_site_provider_id');
	echo $this->Form->input('school_year_id', array('options' => $schoolYearOptions, 'label' => $labelOptions, 'onChange' => 'QualityVisit.updateURL(this)'));
	
	//$labelOptions['text'] = $this->Label->get('InstitutionSite.institution_site_provider_id');
	echo $this->Form->input('education_grade_id', array('options' => $gradesOptions, 'label' => $labelOptions, 'onChange' => 'QualityVisit.updateURL(this)'));
	
	//$labelOptions['text'] = $this->Label->get('InstitutionSite.institution_site_provider_id');
	echo $this->Form->input('institution_site_class_id', array('options' => $classOptions, 'label' => $labelOptions, 'onChange' => 'QualityVisit.updateURL(this)'));
	
	//$labelOptions['text'] = $this->Label->get('InstitutionSite.institution_site_provider_id');
	echo $this->Form->input('teacher_id', array('options' => $teacherOptions, 'label' => $labelOptions, 'onChange' => 'QualityVisit.updateURL(this)'));
	
	echo $this->Form->input('evaluator', array('disabled' => true));
	
	//$labelOptions['text'] = $this->Label->get('InstitutionSite.institution_site_provider_id');
	echo $this->Form->input('quality_type_id', array('options' => $visitOptions, 'label' => $labelOptions, 'onChange' => 'QualityVisit.updateURL(this)'));
	
	echo $this->Form->input('comment', array(
			'type' => 'textarea'
		));

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
<!--    <div class="row">
        <label class="col-md-3 control-label"><?php echo __('Attachment'); ?> </label>
        <div class="col-md-4">
            <div id="attachmensWrapper">
                <?php echo $this->Form->input('files.', array('type' => 'file', 'multiple', 'class' => 'form-error', 'name' => 'data[QualityInstitutionVisitAttachment][files][]')); ?>
            </div>
            <br/>
            <div id="image_upload_info">
                <em><?php echo __("Max File Size:") . ' 2 MB'; ?></em>
            </div>
        </div>
    </div>
    <div class="row">
        <label class="col-md-3 control-label">&nbsp</label>
        <div class="col-md-4">
            <a class="void icon_plus" onclick="QualityVisit.addExtraAttachment()" href="javascript: void(0)"><?php echo __('Add Attachment'); ?></a>
        </div>
    </div>-->
	<?php 
	$multiple = array('multipleURL' => $this->params['controller']."/qualityVisitAjaxAddField/");
	echo $this->Form->hidden('maxFileSize', array('name'=> 'MAX_FILE_SIZE','value'=>(2*1024*1024)));
	echo $this->element('templates/file_upload', compact('multiple'));
	?>
    <?php if (!empty($attachments)) { ?>
        <div class="row">
            <label class="col-md-3 control-label">&nbsp</label>
            <div class="col-md-4">
                <?php
                foreach($attachments as $file) {
                    echo '<div><div class="form_attachment_name">'.$file['file_name'].'</div><div class="form_attachment_delete"><span class="icon_delete" id="'.$file['id'].'" onclick="QualityVisit.removeAttachment(this)" title="Delete"></span></div></div>';
                }
                ?>
            </div>
        </div>
    <?php } ?>
    <div class="controls view_controls">
        <input type="submit" value="<?php echo __("Save"); ?>" class="btn_save btn_right" onclick="return Config.checkValidate();"/>
        <?php
        if ($type == 'add') {
            echo $this->Html->link(__('Cancel'), array('action' => 'qualityVisit'), array('class' => 'btn_cancel btn_left'));
        } else {
            echo $this->Html->link(__('Cancel'), array('action' => 'qualityVisitView', $this->data[$modelName]['id']), array('class' => 'btn_cancel btn_left'));
        }
        ?>
    </div>

    <?php echo $this->Form->end(); ?>
</div>
<?php $this->end(); ?>  