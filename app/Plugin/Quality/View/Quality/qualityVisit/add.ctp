<?php
//echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('attachments', 'stylesheet', array('inline' => false));
echo $this->Html->script('app.date', false);
echo $this->Html->script('config', false);
echo $this->Html->script('Quality.quality.visit', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="quality_visit" class="content_wrapper edit add">
    <h1>
        <span><?php echo __($subheader); ?></span>
    </h1>
    <?php echo $this->element('alert'); ?>
    <?php
    $actionName = $this->action;
    $formOptions = array('controller' => 'Quality', 'action' => $actionName, 'plugin' => 'Quality');
    $formOptions = array_merge($formOptions, $this->params['pass']);

    $pathId = !empty($this->data[$modelName]['id']) ? '/' . $this->data[$modelName]['id'] : '';
    echo $this->Form->create($modelName, array(
        'url' => $formOptions,
        'link' => 'Quality/' . $this->action . $pathId,
        'type' => 'file',
        'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
    ));
    ?>
    <?php
    if (!empty($this->data[$modelName]['id'])) {
        echo $this->Form->input('id', array('type' => 'hidden'));
    }
    ?>
    <?php echo $this->Form->input('maxFileSize', array('type' => 'hidden', 'name' => 'MAX_FILE_SIZE', 'value' => (2 * 1024 * 1024))); ?>
    <?php echo $this->Form->input('institution_site_id', array('type' => 'hidden')); ?>
    <div class="row">
        <div class="label"><?php echo __('Date'); ?></div>
        <div class="value"><?php echo $this->Form->input('date', array('id' => 'date', 'type' => 'date', 'dateFormat' => 'DMY', 'before' => '<div class="left">', 'after' => '</div>', 'class' => false)); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('School Year'); ?></div>
        <div class="value"><?php echo $this->Form->input('school_year_id', array('id' => 'schoolYearId', 'options' => $schoolYearOptions, 'onChange' => 'QualityVisit.updateURL(this)')); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Grade'); ?></div>
        <div class="value"><?php echo $this->Form->input('education_grade_id', array('id' => 'educationGradeId', 'options' => $gradesOptions, 'onChange' => 'QualityVisit.updateURL(this)')); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Class'); ?></div>
        <div class="value"><?php echo $this->Form->input('institution_site_classes_id', array('id' => 'institutionSiteClassesId', 'options' => $classOptions, 'onChange' => 'QualityVisit.updateURL(this)')); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Teacher'); ?></div>
        <div class="value"><?php echo $this->Form->input('teacher_id', array('id' => 'institutionSiteTeacherId', 'options' => $teacherOptions, 'onChange' => 'QualityVisit.updateURL(this)')); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Evaluator'); ?></div>
        <div class="value"><?php echo $this->Form->input('evaluator', array('disabled' => true)); ?> </div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Type'); ?></div>
        <div class="value"><?php echo $this->Form->input('quality_type_id', array('id' => 'qualityTypeId', 'options' => $visitOptions, 'onChange' => 'QualityVisit.updateURL(this)')); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Comment'); ?></div>
        <div class="value">
            <?php echo $this->Form->input('comment', array('type' => 'textarea')); ?>
            <br/>
            <div id="image_upload_info" style="clear: both">
                <em>
                    <?php echo __("Maximum 150 words per comment"); ?>
                </em>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Attachment'); ?> </div>
        <div class="value">
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
        <div class="label">&nbsp</div>
        <div class="value">
            <a class="void icon_plus" onclick="QualityVisit.addExtraAttachment()" href="javascript: void(0)"><?php echo __('Add Attachment'); ?></a>
        </div>
    </div>
    <?php if (!empty($attachments)) { ?>
        <div class="row">
            <div class="label">&nbsp</div>
            <div class="value">
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