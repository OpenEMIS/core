<?php
$alias = $ControllerAction['table']->alias();
$this->Form->unlockField('Assessments.assessment_items');
    // pr($data);
?>

<?php if ($ControllerAction['action'] == 'view') : ?>
    <!-- POCOR-7999 for readibility -->
    <?= $this->element('assessment_items_view') ?>
<?php endif ?>
<?php
if ($ControllerAction['action'] == 'add') : ?>
    <div class="input requireds">
        <label><?= isset($attr['label']) ? __($attr['label']) : __($attr['field']) ?></label>
        <div class="table-wrapper">
            <div class="table-in-view">
                <table class="table">
                    <thead>
                    <th><?= $this->Label->get('Assessments.educationSubject'); ?></th>
                    <th><?= $this->Label->get('Assessments.subjectWeight'); ?></th>
                    <th><?= $this->Label->get('Assessments.classification'); ?></th>
                    </thead>
                    <?php if (isset($data['assessment_items'])) : ?>
                        <tbody>
                        <!-- POCOR-7999 for readibility -->
                        <?php foreach ($data['assessment_items'] as $i => $item) : ?>
                            <?php
                            $fieldPrefix = "$alias.assessment_items.$i";

                            $field_id = "$fieldPrefix.id";
                            $value_id = $item->id;

                            $field_education_subject_id = "$fieldPrefix.education_subject_id";
                            $value_education_subject_id = $item->education_subject_id;

                            $field_weight = "$fieldPrefix.weight";
                            $field_classification = "$fieldPrefix.classification";
                            ?>
                            <tr>
                                <td>
                                    <?php
                                    echo $item->education_subject_name;

                                    echo $this->Form->hidden($field_education_subject_id,
                                        ['value' => $value_education_subject_id]);
                                    if (isset($value_id)) {
                                        echo $this->Form->hidden($field_id, ['value' => $value_id]);
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    echo $this->Form->input($field_weight, [
                                        'type' => 'float',
                                        'label' => false,
                                        'onblur' => "return utility.checkDecimal(this, 2);",
                                        'onkeypress' => "return utility.floatCheck(event)",

                                    ]);
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    echo $this->Form->input($field_classification, [
                                        'type' => 'string',
                                        'label' => false
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
<!-- POCOR-7999 for readibility -->
<?php if ($ControllerAction['action'] == 'edit') : //POCOR-6780 ?>
    <div class="input requireds">
        <label><?= isset($attr['label']) ? __($attr['label']) : __($attr['field']) ?></label>
        <div class="table-wrapper">
            <div class="table-in-view">
                <table class="table">
                    <thead>
                    <th><?php
                        $grade_education_subjects = $data['grade_education_subjects'];

                        echo $this->Form->checkbox("all_check",
                            ['id' => 'selectAll', 'class' => 'no-selection-label',
                                'kd-checkbox-radio' => '', 'disabled' => true]); ?></th>
                    <th><?= $this->Label->get('Assessments.educationSubject'); ?></th>
                    <th><?= $this->Label->get('Assessments.subjectWeight'); ?></th>
                    <th><?= $this->Label->get('Assessments.classification'); ?></th>
                    </thead>
                    <?php if (isset($grade_education_subjects)) : ?>
                        <tbody>
                        <?php $counter = 0;  ?>
                        <?php foreach ($grade_education_subjects as $grade_education_subject) :
                            $education_subject_id = $grade_education_subject['id'];
                            $itemName = $grade_education_subject['label'];
                            $present = $grade_education_subject['present'];
                            $weight = $grade_education_subject['assessment_item_weight'];
                            $classification = $grade_education_subject['assessment_item_classification'];
                            $fieldPrefix = "$alias.assessment_items.$education_subject_id";
                            $field_weight = "$fieldPrefix.weight";
                            $field_education_subject_check = "$fieldPrefix.education_subject_check";
                            ?>

                            <tr>
                                <td>
                                    <?php
                                    if ($present) {
                                        echo $this->Form->checkbox($field_education_subject_check,
                                            ['checked' => $education_subject_id,
                                                'onclick' => 'return false',
                                                'class' => 'no-selection-label',
                                                'kd-checkbox-radio' => '']);
                                    }
                                    if (!$present) {
                                        echo $this->Form->hidden("$fieldPrefix.id_check",
                                            ['value' => $education_subject_id]);
                                        echo $this->Form->checkbox($field_education_subject_check,
                                            ['class' => 'no-selection-label',
                                                'kd-checkbox-radio' => '']);
                                    }

                                    ?>
                                </td>
                                <td> <?php
                                    if ($present) {
                                        echo $itemName;;
                                    }
                                    if (!$present) {
                                        echo $itemName;
                                    }
                                    ?>
                                    <?php
                                    echo $this->Form->hidden("$fieldPrefix.education_subject_id",
                                        ['value' => $education_subject_id]);
                                    echo $this->Form->hidden("$fieldPrefix.assessment_id",
                                        ['value' => $data['assessment_id']]);
                                    ?>
                                <td>
                                    <?php
                                    if ($present) {
                                        echo $this->Form->input($field_weight, [
                                            'type' => 'float',
                                            'label' => false,
                                            'onblur' => "return utility.checkDecimal(this, 2);",
                                            'onkeypress' => "return utility.floatCheck(event)",
                                            'required' => false,
                                            'value' => $weight
                                        ]);
                                    }
                                    if (!$present) {
                                        echo $this->Form->input($field_weight, [
                                            'type' => 'float',
                                            'label' => false,
                                            'onblur' => "return utility.checkDecimal(this, 2);",
                                            'onkeypress' => "return utility.floatCheck(event)",
                                            'required' => false,
                                            'value' => '0.00'
                                        ]);
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    if ($present) {
                                        echo $this->Form->input("$fieldPrefix.classification", [
                                            'type' => 'string',
                                            'label' => false,
                                            'value' => $classification
                                        ]);
                                    }
                                    if (!$present) {
                                        echo $this->Form->input("$fieldPrefix.classification", [
                                            'type' => 'string',
                                            'label' => false,
                                            'value' => ''
                                        ]);
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php
                            $counter++;
                            ?>
                        <?php endforeach ?>
                        </tbody>
                    <?php endif ?>
                </table>
            </div>
        </div>
    </div>
<?php endif ?>
<!-- <script>
    $(document).ready(function(){
        $("#selectAll").click(function(){
                $("input[type=checkbox]").prop('checked', $(this).prop('checked'));

        });
    });
</script> -->
