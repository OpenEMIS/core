<?= $this->Html->script('OpenEmis.../plugins/tableCheckable/jquery.tableCheckable', ['block' => true]) ?>

<?php if ($action == 'add') : ?>
    <div class="input clearfix required">
        <label><?= isset($attr['label']) ? __($attr['label']) : __($attr['field']) ?></label>
        <div class="table-wrapper">
            <div class="table-in-view">
                <table class="table table-checkable">
                    <thead>
                        <tr>
                            <th class="checkbox-column"><input type="checkbox" class="icheck-input" /></th>
                            <th><?= __('OpenEMIS ID') ?></th>
                            <th><?= __('Student') ?></th>
                            <th><?= __('Current Grade') ?></th>
                            <th><?= __('Special Needs') ?></th>
                        </tr>
                    </thead>
                    <?php if (isset($attr['data'])) :?>
                        <tbody>
                            <?php
                                $studentCount = 0;
                                foreach ($attr['data'] as $i => $obj) :
                            ?>
                                <tr>
                                    <td class="checkbox-column">
                                        <?php
                                            $alias = $ControllerAction['table']->alias();
                                            $fieldPrefix = "$alias.examination_students.$i";

                                            $checkboxOptions = ['type' => 'checkbox', 'class' => 'icheck-input', 'label' => false, 'div' => false];
                                            echo $this->Form->input("$fieldPrefix.selected", $checkboxOptions);
                                            echo $this->Form->hidden("$fieldPrefix.student_id", ['value' => $obj->student_id]);
                                        ?>
                                    </td>
                                    <td><?= $obj->user->openemis_no ?></td>
                                    <td><?= $obj->user->name ?></td>
                                    <td><?= $obj->_matchingData['EducationGrades']->programme_grade_name ?></td>
                                    <td>
                                        <?php
                                            $specialNeeds = '';
                                            if (isset($obj->user->special_needs)) {
                                                foreach ($obj->user->special_needs as $key => $item) {
                                                    $specialNeeds .= $item->special_need_type->name . ', ';
                                                }

                                                $specialNeeds = rtrim($specialNeeds, ', ');
                                            }
                                            echo $specialNeeds;
                                        ?>
                                    </td>
                                </tr>
                            <?php
                                $studentCount++;
                                endforeach;
                                if ($studentCount <= 0) {
                            ?>
                                <tr><td><?= __('There are no students selected') ?></td></tr>
                            <?php
                                }
                            ?>
                        </tbody>
                    <?php endif ?>
                </table>
            </div>
        </div>
    </div>
<?php endif ?>