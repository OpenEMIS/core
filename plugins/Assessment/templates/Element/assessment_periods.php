<?php
    $alias = $ControllerAction['table']->getAlias();
    $this->Form->create();
    $this->Form->unlockField('Assessments.education_subjects');
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
                            <td><?= $assessmentGradingTypeOptions[$item->_joinData->assessment_grading_type_id ?? null] ?? __('Not set'); ?></td>
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
                                            // POCOR-9937: one source for the subject id. Add rows are arrays carrying education_subject_id;
                                            // edit rows are EducationSubjects entities, whose own id is the subject id.
                                            $subjectId = $ControllerAction['action'] == 'add' ? $item['education_subject_id'] : $item['id'];
                                            echo $this->Form->hidden("$fieldPrefix.id", ['value' => $subjectId]);

                                            if ($ControllerAction['action'] == 'add') {
                                                echo $item['education_subject_name'];
                                                echo $this->Form->hidden("$fieldPrefix.education_subject_name", ['value' => $item['education_subject_name']]);
                                            } else {
                                                echo $item->code . ' - ' . $item->name;
                                                echo $this->Form->hidden("$fieldPrefix.education_subject_name", ['value' => $item->code . ' - ' . $item->name]);
                                            }
                                            echo $this->Form->hidden("$joinDataPrefix.education_subject_id", ['value' => $subjectId]);

                                            if (isset($item->id)) {
                                                echo $this->Form->hidden("$joinDataPrefix.assessment_period_id", ['value' => $data['id']]);
                                            }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        //POCOR-7409 start
                                        if($ControllerAction['action'] == 'add'){
                                            echo $this->Form->input("$joinDataPrefix.assessment_grading_type_id", [
                                                'type' => 'select',
                                                'label' => false,
                                                'options' => $assessmentGradingTypeOptions,
                                                // 'disabled' => 'disabled' //POCOR-3745
                                            ]);
                                        }
                                        if($ControllerAction['action'] == 'edit'){
                                            echo $this->Form->input("$joinDataPrefix.assessment_grading_type_id", [
                                                'type' => 'select',
                                                'label' => false,
                                                'options' => $assessmentGradingTypeOptions, //POCOR-8520
                                                // POCOR-9937: value is the option key (id), not its label. A missing or unknown saved id
                                                // shows "-- Select --" (and the save is rejected) instead of silently becoming the first option.
                                                'empty' => __('-- Select --'),
                                                'value' => $item['_joinData']['assessment_grading_type_id'] ?? null,
                                                //'disabled' => 'disabled' //POCOR-7431
                                            ]);
                                        }
                                         //POCOR-7409 end
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    <?php endif ?>
                </table>
            </div>
        </div>
        <?php // POCOR-9937: this custom element must print its own errors (e.g. a subject without a grading type) ?>
        <?php foreach (($data instanceof \Cake\Datasource\EntityInterface ? $data->getError('education_subjects') : []) as $error) : ?>
            <div class="error-message"><?= h($error) ?></div>
        <?php endforeach ?>
    </div>
<?php endif ?>