<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Textbooks'));

$this->start('contentActions');
echo $this->Html->link(__('View'), array('action' => 'textbooks', $selectedAcademicPeriod, $selectedProgramme, $selectedGrade), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

echo $this->Form->create('CensusTextbook', array(
    'inputDefaults' => array('label' => false, 'div' => false),
    'url' => array('controller' => 'Census', 'action' => 'textbooksEdit')
));
echo $this->element('census/academic_period_options');
?>

<?php if (!empty($programmeOptions)) : ?>
    <div class="row page-controls">
        <div class="col-md-3">
            <?php
            echo $this->Form->input('education_programme_id', array(
                'label' => false,
                'div' => false,
                'class' => 'form-control',
                'options' => $programmeOptions,
                'default' => $selectedProgramme,
                'onchange' => 'jsForm.change(this)',
                'url' => 'Census/' . $this->action . '/' . $selectedAcademicPeriod . '/'
            ));
            ?>
        </div>
    </div>
<?php endif; ?>

<?php
    $index = 0;
    foreach ($data as $key => $val) { ?>

    <fieldset class="section_group">
        <legend><?php echo $key ?></legend>
        <?php if (!empty($gradeOptions)) : ?>
            <div class="row page-controls">
                <div class="col-md-4">
                <?php
                    echo $this->Form->input('education_grade_id', array(
                        'label' => false,
                        'div' => false,
                        'class' => 'form-control',
                        'options' => $gradeOptions,
                        'default' => $selectedGrade,
                        'onchange' => 'jsForm.change(this)',
                        'url' => 'Census/' . $this->action . '/' . $selectedAcademicPeriod . '/' . $selectedProgramme . '/'
                    ));
                ?>
                </div>
            </div>
        <?php endif; ?>

        <table class="table table-striped table-hover table-bordered">
            <thead>
                <tr>
                    <th class="table_cell cell_grade"><?php echo __('Grade'); ?></th>
                    <th class="table_cell"><?php echo __('Subject'); ?></th>
                    <th class="table_cell"><?php echo __('Total'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $total = 0;
                foreach ($val as $record) {
                    $record_tag = "";
                    foreach ($source_type as $k => $v) {
                        if ($record['source'] == $v) {
                            $record_tag = "row_" . $k;
                        }
                    }
                    ?>

                    <tr>
                        <?php
                        echo $this->Form->hidden($index . '.id', array('value' => $record['id']));
                        echo $this->Form->hidden($index . '.education_grade_subject_id', array('value' => $record['education_grade_subject_id']));
                        echo $this->Form->hidden($index . '.institution_site_id', array('value' => $record['institution_site_id']));
                        ?>
                        <td class="table_cell <?php echo $record_tag; ?>"><?php echo $record['education_grade_name']; ?></td>
                        <td class="table_cell <?php echo $record_tag; ?>"><?php echo $record['education_subject_name']; ?></td>
                        <td class="table_cell">
                            <div class="input_wrapper">
                                <?php
                                echo $this->Form->input($index . '.value', array(
                                    'type' => 'text',
                                    'value' => $record['total'],
                                    'class' => $record_tag,
                                    'maxlength' => 10,
                                    'onkeypress' => 'return utility.integerCheck(event)'
                                ));
                                ?>
                            </div>
                        </td>
                    </tr>

                <?php $index++;
                } ?>
            </tbody>
        </table>
    </fieldset>
<?php } ?>

<?php if (!empty($data)) { ?>
    <div class="controls">
        <input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
    <?php echo $this->Html->link(__('Cancel'), array('action' => 'textbooks', $selectedAcademicPeriod, $selectedProgramme, $selectedGrade), array('class' => 'btn_cancel btn_left')); ?>
    </div>
<?php } ?>

<?php echo $this->Form->end(); ?>

<?php $this->end(); ?>