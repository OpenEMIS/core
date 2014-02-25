<?php
//echo $this->Html->css('table', 'stylesheet', array('inline' => false));
//2echo $this->Html->css('institution', 'stylesheet', array('inline' => false));
echo $this->Html->script('app.date', false);
echo $this->Html->script('config', false);
echo $this->Html->script('Quality.quality.rubric', false);
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
    $pathId = !empty($this->data[$modelName]['id'])? '/'.$this->data[$modelName]['id'] : '';
    echo $this->Form->create($modelName, array(
        'url' => $formOptions,
        'link' => 'Quality/' . $this->action.$pathId,
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

    <div class="row">
        <div class="label"><?php echo __('School Year'); ?></div>
        <div class="value"><?php echo $this->Form->input('school_year_id', array('id' => 'schoolYearId', 'options' => $schoolYearOptions, 'onChange' => 'QualityRubric.updateURL(this)')); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Name'); ?></div>
        <div class="value"><?php echo $this->Form->input('rubric_template_id', array('id' => 'rubricsTemplateId', 'options' => $rubricOptions, 'onChange' => 'QualityRubric.updateURL(this)')); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Class'); ?></div>
        <div class="value"><?php echo $this->Form->input('institution_site_classes_id', array('id' => 'institutionSiteClassesId', 'options' => $classOptions, 'onChange' => 'QualityRubric.updateURL(this)')); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Teacher'); ?></div>
        <div class="value"><?php echo $this->Form->input('teacher_id', array('id' => 'institutionSiteTeacherId', 'options' => $teacherOptions, 'onChange' => 'QualityRubric.updateURL(this)')); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Supervisor'); ?></div>
        <div class="value"><?php echo $this->Form->input('supervisor', array('disabled' => true)); ?> </div>
    </div>
  
    <div class="controls view_controls">
        
        <input type="submit" value="<?php echo ($type == 'add')?__("Start"):__("Save"); ?>" class="btn_save btn_right" onclick="return Config.checkValidate();"/>
        <?php
        if ($type == 'add') {
            echo $this->Html->link(__('Cancel'), array('action' => 'qualityRubric'), array('class' => 'btn_cancel btn_left'));
        } else {
            echo $this->Html->link(__('Cancel'), array('action' => 'qualityRubricView', $this->data[$modelName]['id']), array('class' => 'btn_cancel btn_left'));
        }
        ?>
    </div>

<?php echo $this->Form->end(); ?>
</div>