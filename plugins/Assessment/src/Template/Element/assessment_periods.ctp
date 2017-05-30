<?php
    $alias = $ControllerAction['table']->alias();
    $this->Form->unlockField('Assessments.education_subjects');
    // pr($data);
?>

<?php if ($ControllerAction['action'] == 'view') : ?>
    <div class="table-in-view">
        <table class="table">
            <thead>
                <th><?= $this->Label->get('Assessments.educationSubject'); ?></th>
                <th><?= $this->Label->get('Assessments.assessmentGradingType'); ?></th>
            </thead>
            <?php if (isset($data['education_subjects'])) : ?>
                <tbody>
                    <?php foreach ($data['education_subjects'] as $i => $item) : ?>
                        <tr>
                            <td><?= $item->code . ' - ' . $item->name ?></td>
                            <td><?= $assessmentGradingTypeOptions[$item->_joinData->assessment_grading_type_id]; ?></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            <?php endif ?>
        </table>
    </div>
<?php elseif ($ControllerAction['action'] == 'add' || $ControllerAction['action'] == 'edit') : ?>
    <div class="input required">
        <label><?= isset($attr['label']) ? __($attr['label']) : __($attr['field']) ?></label>
        <div class="table-wrapper">
            <div class="table-in-view">
                <table class="table">
                    <thead>
                        <th><?= $this->Label->get('Assessments.educationSubject'); ?></th>
                        <th><?= $this->Label->get('Assessments.assessmentGradingType'); ?></th>
                    </thead>
                    <?php if (isset($data['education_subjects'])) : ?>
                        <tbody>
                            <?php foreach ($data['education_subjects'] as $i => $item) : ?>
                                <?php
                                    $fieldPrefix = "$alias.education_subjects.$i";
                                    $joinDataPrefix = $fieldPrefix . '._joinData';
                                ?>
                                <tr>
                                    <td>
                                        <?php
                                            echo $this->Form->hidden("$fieldPrefix.id", ['value' => $item['education_subject_id']]);

                                            if ($ControllerAction['action'] == 'add') {
                                                echo $item['education_subject_name'];
                                                echo $this->Form->hidden("$fieldPrefix.education_subject_name", ['value' => $item['education_subject_name']]);
                                            } else {
                                                echo $item->code . ' - ' . $item->name;
                                                echo $this->Form->hidden("$fieldPrefix.education_subject_name", ['value' => $item->code . ' - ' . $item->name]);
                                            }
                                            echo $this->Form->hidden("$joinDataPrefix.education_subject_id", ['value' => $item['education_subject_id']]);

                                            if (isset($item->id)) {
                                                echo $this->Form->hidden("$joinDataPrefix.id", ['value' => $item['_joinData']['id']]);
                                                echo $this->Form->hidden("$joinDataPrefix.assessment_period_id", ['value' => $data['id']]);
                                            }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                            echo $this->Form->input("$joinDataPrefix.assessment_grading_type_id", [
                                                'type' => 'select',
                                                'label' => false,
                                                'options' => $assessmentGradingTypeOptions
                                            ]);
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    <?php endif ?>
                </table>
            </div>
        </div>
    </div>
<?php endif ?>