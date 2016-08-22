<?php
    $alias = $ControllerAction['table']->alias();
    $this->Form->unlockField('Assessments.assessment_items');
    // pr($data);
?>

<?php if ($ControllerAction['action'] == 'view') : ?>
    <div class="table-in-view">
        <table class="table">
            <thead>
                <th><?= $this->Label->get('Assessments.educationSubject'); ?></th>
                <th><?= $this->Label->get('Assessments.assessmentGradingType'); ?></th>
            </thead>
            <?php if (isset($data['assessment_items'])) : ?>
                <tbody>
                    <?php foreach ($data['assessment_items'] as $i => $item) : ?>
                        <tr>
                            <td><?= $item->education_subject->name ?></td>
                            <td><?= $assessmentGradingTypeOptions[$item->_joinData->assessment_grading_type_id]; ?></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            <?php endif ?>
        </table>
    </div>
<?php elseif ($ControllerAction['action'] == 'add' || $ControllerAction['action'] == 'edit') : ?>
    <div class="required">
        <label><?= isset($attr['label']) ? __($attr['label']) : __($attr['field']) ?></label>
        <div class="input-form-wrapper">
            <div class="table-wrapper">
                <div class="table-in-view">
                    <table class="table">
                        <thead>
                            <th><?= $this->Label->get('Assessments.educationSubject'); ?></th>
                            <th><?= $this->Label->get('Assessments.assessmentGradingType'); ?></th>
                        </thead>
                        <?php if (isset($data['assessment_items'])) : ?>
                            <tbody>
                                <?php foreach ($data['assessment_items'] as $i => $item) : ?>
                                    <?php
                                        $fieldPrefix = "$alias.assessment_items.$i";
                                        $joinDataPrefix = $fieldPrefix . '._joinData';
                                    ?>
                                    <tr>
                                        <td>
                                            <?php
                                                echo $this->Form->hidden("$fieldPrefix.id", ['value' => $item['assessment_item_id']]);

                                                if ($ControllerAction['action'] == 'add') {
                                                    echo $item['education_subject_name'];
                                                    echo $this->Form->hidden("$fieldPrefix.education_subject_name", ['value' => $item['education_subject_name']]);
                                                } else {
                                                    echo $item->education_subject->name;
                                                    echo $this->Form->hidden("$fieldPrefix.education_subject_name", ['value' => $item->education_subject->name]);
                                                }
                                                echo $this->Form->hidden("$joinDataPrefix.assessment_item_id", ['value' => $item['assessment_item_id']]);

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
    </div>
<?php endif ?>