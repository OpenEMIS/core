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
            </thead>
            <?php if (isset($data['assessment_items'])) : ?>
                <tbody>
                    <?php foreach ($data['assessment_items'] as $i => $item) : ?>
                        <tr>
                            <td><?= $item->education_subject->code . ' - ' . $item->education_subject->name; ?></td>
                            <td><?= $item->weight; ?></td>
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
                            <th><?= $this->Label->get('Assessments.subjectWeight'); ?></th>
                        </thead>
                        <?php if (isset($data['assessment_items'])) : ?>
                            <tbody>
                                <?php foreach ($data['assessment_items'] as $i => $item) : ?>
                                    <?php
                                        $fieldPrefix = "$alias.assessment_items.$i";
                                    ?>
                                    <tr>
                                        <td>
                                            <div style="display:inline-block;">
                                            <?php
                                                echo $item->education_subject->code . ' - ' . $item->education_subject->name;
                                                echo $this->Form->hidden("$fieldPrefix.education_subject_id", ['value' => $item->education_subject_id]);
                                                if (isset($item->id)) {
                                                    echo $this->Form->hidden("$fieldPrefix.id", ['value' => $item->id]);
                                                }
                                                echo $this->Form->hidden("$fieldPrefix.status", ['value' => $item->status]);
                                            ?>
                                            </div>
                                            <?php if ($item->status == 'new') { ?>
                                                    &nbsp;<i class='fa fa-info-circle fa-lg table-tooltip icon-blue' data-toggle='tooltip' data-placement='bottom' title='<?= __('Newly Added Subject to the Education Grade')?>'></i>
                                            <?php } ?>
                                        </td>
                                        <td>
                                            <?php
                                                if ($item->status != 'deleted') {
                                                    echo $this->Form->input("$fieldPrefix.weight", [
                                                        'type' => 'float',
                                                        'label' => false,
                                                        'onblur' => "return utility.checkDecimal(this, 2);",
                                                        'onkeypress' => "return utility.floatCheck(event)",
                                                    ]);
                                                } else {
                                                    echo __('This Subject has been Removed from the Education Grade');
                                                }
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