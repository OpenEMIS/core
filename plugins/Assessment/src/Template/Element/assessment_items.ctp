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
                <th><?= $this->Label->get('Assessments.subjectWeight'); ?></th>
                <th><?= $this->Label->get('Assessments.classification'); ?></th>
            </thead>
            <?php if (isset($data['assessment_items'])) : ?>
                <tbody>
                    <?php foreach ($data['assessment_items'] as $i => $item) : ?>
                        <tr>
                            <td><?= $item->education_subject->code . ' - ' . $item->education_subject->name; ?></td>
                            <td><?= $item->weight; ?></td>
                            <td><?= $item->classification; ?></td>
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
                        <th><?= $this->Label->get('Assessments.subjectWeight'); ?></th>
                        <th><?= $this->Label->get('Assessments.classification'); ?></th>
                    </thead>
                    <?php if (isset($data['assessment_items'])) : ?>
                        <tbody>
                            <?php foreach ($data['assessment_items'] as $i => $item) : ?>
                                <?php
                                    $fieldPrefix = "$alias.assessment_items.$i";
                                ?>
                                <tr>
                                    <td>
                                        <?php
                                            echo $item->education_subject->code . ' - ' . $item->education_subject->name;
                                            echo $this->Form->hidden("$fieldPrefix.education_subject_id", ['value' => $item->education_subject_id]);
                                            if (isset($item->id)) {
                                                echo $this->Form->hidden("$fieldPrefix.id", ['value' => $item->id]);
                                            }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                            echo $this->Form->input("$fieldPrefix.weight", [
                                                'type' => 'float',
                                                'label' => false,
                                                'onblur' => "return utility.checkDecimal(this, 2);",
                                                'onkeypress' => "return utility.floatCheck(event)",
                                            ]);
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                            echo $this->Form->input("$fieldPrefix.classification", [
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