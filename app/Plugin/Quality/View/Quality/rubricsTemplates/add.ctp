<?php
//echo $this->Html->css('table', 'stylesheet', array('inline' => false));
//2echo $this->Html->css('institution', 'stylesheet', array('inline' => false));
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->script('config', false);
echo $this->Html->script('/Quality/js/rubrics', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="rubrics_template" class="content_wrapper">
    <h1>
        <span><?php echo __($subheader); ?></span>
        <?php
        /* if($_add) {
          echo $this->Html->link(__('Add'), array('action' => 'rubrics_add'), array('class' => 'divider'));
          } */
        ?>
    </h1>
    <?php echo $this->element('alert'); ?>
    <?php
    $formOptions = array('controller' => 'Quality', 'action' => $this->action, 'plugin' => 'Quality');
    echo $this->Form->create($modelName, array(
        'url' => $formOptions,
        'type' => 'file',
        'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
    ));
    ?>
    <?php //echo $this->Form->input('institution_id', array('type' => 'hidden')); ?>
    <?php
    if (!empty($this->data[$modelName]['id'])) {
        echo $this->Form->input('id', array('type' => 'hidden'));
    }
    ?>
    <div class="row">
        <div class="label"><?php echo __('Name'); ?></div>
        <div class="value"><?php
            if ($type == 'add') {
                echo $this->Form->input('name');
            } else {
                echo $this->data[$modelName]['name'];
            }
            ?> </div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Description'); ?></div>
        <div class="value"><?php echo $this->Form->input('description'); ?> </div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Weighting'); ?></div>
        <div class="value"><?php
            if ($type == 'add') {
                echo $this->Form->input('weighting', array('options' => $weightingOptions));
            } else {
                echo $weightingOptions[$this->data[$modelName]['weighting']];
            }
            ?> </div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Pass Mark'); ?></div>
        <div class="value"><?php
            if ($type == 'add') {
                echo $this->Form->input('pass_mark');
            } else {
                echo $this->data[$modelName]['pass_mark'];
            }
            ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Security Role'); ?></div>
        <div class="value"><?php
            if ($type == 'add' || empty($this->data[$modelName]['security_role_id'])) {
                echo $this->Form->input('security_role_id', array('options' => $roleOptions));
            } else {
                echo $roleOptions[$this->data[$modelName]['security_role_id']];
            }
            ?> </div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Target Grades'); ?></div>
        <div class="value">
            <?php
            if (!empty($rubricGradesOptions)) {
                foreach ($rubricGradesOptions as $rubricGrade) {
                    echo $rubricGrade . '<br/>';
                }
            }
            ?>
            <div id='gradeWraper' class="table" style="width:247px;">
                <div class="table_body" style="display:table;">
                    <?php if ($type == 'add' || empty($rubricGradesOptions)) : ?>
                        <div class="table_row " row-id="0">
                            <div class="table_cell cell_description" style="width:90%">
                                <?php echo $this->Form->input('RubricsTemplateGrade.0.education_grade_id', array('options' => $gradeOptions, 'style' => array('width:200px')));?> 
                            </div>
                            <div class="table_cell cell_delete">
                                <!--<span class="icon_delete" onclick="rubricsTemplate.removeRubricTemplateGrade(this)" title="Delete"></span>-->
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
    <div class="row">
        <div class="label">&nbsp;</div>
        <div class="value"><a class="void icon_plus" onclick="rubricsTemplate.addRubricTemplateGrade(this)" url="Quality/rubricsTemplatesAjaxAddGrade"  href="javascript: void(0)"><?php echo __('Add Grade'); ?></a></div>
    </div>
    <div class="controls view_controls">
        <input type="submit" value="<?php echo __("Save"); ?>" class="btn_save btn_right" onclick="return Config.checkValidate();"/>
        <?php echo $this->Html->link(__('Cancel'), array('action' => 'rubricsTemplatesView', $id), array('class' => 'btn_cancel btn_left')); ?>
    </div>

    <?php echo $this->Form->end(); ?>
</div>