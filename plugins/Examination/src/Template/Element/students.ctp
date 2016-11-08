<?= $this->Html->script('OpenEmis.../plugins/tableCheckable/jquery.tableCheckable', ['block' => true]) ?>

<?php
$alias = $ControllerAction['table']->alias();
$checkboxOptions = ['type' => 'checkbox', 'class' => 'icheck-input', 'label' => false, 'div' => false];
?>
<?php if ($action == 'add') : ?>
    <div class="input clearfix required">
        <label><?= isset($attr['label']) ? __($attr['label']) : __($attr['field']) ?></label>
        <div class="table-wrapper">
            <div class="table-in-view">
                <table class="table table-checkable">
                    <thead>
                        <tr>
                            <th class="checkbox-column"><input type="checkbox" class="icheck-input" /></th>
                            <th><?= __('Registration Number') ?></th>
                            <th><?= __('OpenEMIS ID') ?></th>
                            <th><?= __('Student') ?></th>
                            <th><?= __('Current Grade') ?></th>
                            <th><?= __('Special Needs') ?></th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (isset($attr['data']) && count($attr['data']) > 0) : ?>
                        <?php foreach ($attr['data'] as $i => $obj) : ?>
                            <tr>
                                <td class="checkbox-column">
                                    <?php
                                        $fieldPrefix = "$alias.examination_students.$i";
                                        echo $this->Form->input("$fieldPrefix.selected", $checkboxOptions);
                                        echo $this->Form->hidden("$fieldPrefix.student_id", ['value' => $obj->student_id]);
                                    ?>
                                </td>
                                <td><?= $this->Form->input("$fieldPrefix.registration_number", ['label' => false, 'maxlength' => 20]) ?></td>
                                <td><?= $obj->user->openemis_no ?></td>
                                <td><?= $obj->user->name ?></td>
                                <td><?= $obj->_matchingData['EducationGrades']->programme_grade_name ?></td>
                                <td>
                                    <?php
                                        $specialNeeds = [];
                                        foreach ($obj->user->special_needs as $key => $item) {
                                            $specialNeeds[] = $item->special_need_type->name;
                                        }
                                        echo implode(', ', $specialNeeds);
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    <?php else : ?>
                        <tr><td colspan="5"><?= __('There are no students selected') ?></td></tr>
                    <?php endif ?>

                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif ?>
