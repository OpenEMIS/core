<?php
    $alias = $ControllerAction['table']->alias();
    $this->Form->unlockField('Examination.examination_items');
    // pr($data);
?>

<?php if ($ControllerAction['action'] == 'view') : ?>
    <div class="table-in-view">
        <table class="table">
            <thead>
                <th><?= __('Code') ?></th>
                <th><?= __('Name') ?></th>
                <th><?= __('Weight') ?></th>
                <th><?= __('Grading Type') ?></th>
                <th><?= __('Date') ?></th>
                <th><?= __('Start Time') ?></th>
                <th><?= __('End Time') ?></th>
            </thead>
            <?php if (isset($data['examination_items'])) : ?>
                <tbody>
                    <?php foreach ($data['examination_items'] as $i => $item) : ?>
                        <tr>
                            <td><?= $item->education_subject->code; ?></td>
                            <td><?= $item->education_subject->name; ?></td>
                            <td><?= $item->weight; ?></td>
                            <td><?= $item->examination_grading_type_id; ?></td>
                            <td><?= $item->date->format('d-m-Y'); ?></td>
                            <td><?= $item->start_time->format('H:i A'); ?></td>
                            <td><?= $item->end_time->format('H:i A'); ?></td>
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
                            <th><?= __('Code') ?></th>
                            <th><?= __('Name') ?></th>
                            <th><?= __('Weight') ?></th>
                            <th><?= __('Grading Type') ?></th>
                            <th><?= __('Date') ?></th>
                            <th><?= __('Start Time') ?></th>
                            <th><?= __('End Time') ?></th>
                        </thead>
                        <?php if (isset($data['examination_items'])) : ?>
                            <tbody>
                                <?php foreach ($data['examination_items'] as $i => $item) : ?>
                                    <?php
                                        $fieldPrefix = "$alias.examination_items.$i";
                                        $joinDataPrefix = $fieldPrefix . '._joinData';
                                    ?>
                                    <tr>
                                        <td>
                                            <?php
                                                if ($ControllerAction['action'] == 'add') {
                                                    echo $this->Form->hidden("$fieldPrefix.education_subject_id", ['value' => $item['education_subject_id']]);
                                                    echo $item->education_subject_code;
                                                } else {
                                                    echo $this->Form->hidden("$fieldPrefix.education_subject_id", ['value' => $item['education_subject_id']]);
                                                    echo $item->education_subject->code;
                                                }
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                                if ($ControllerAction['action'] == 'add') {
                                                    echo $item->education_subject_name;
                                                } else {
                                                    echo $item->education_subject->name;
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
                                                echo $this->Form->input("$fieldPrefix.examination_grading_type_id", [
                                                    'type' => 'select',
                                                    'label' => false,
                                                    'options' => $examinationGradingTypeOptions
                                                ]);
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                                $attr = [];
                                                $attr['null'] = true;
                                                $attr['default_date'] = false;
                                                $attr['class'] = 'margin-top-10 no-margin-bottom';
                                                $attr['field'] = 'date';
                                                $attr['model'] = $fieldPrefix;
                                                $attr['id'] = 'date_'.$i;
                                                $attr['label'] = false;
                                                echo $this->HtmlField->date('edit', $item, $attr);
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                                $attr = [];
                                                $attr['null'] = true;
                                                $attr['default_time'] = false;
                                                $attr['class'] = 'no-margin-bottom';
                                                $attr['field'] = 'start_time';
                                                $attr['model'] = $fieldPrefix;
                                                $attr['id'] = 'start_time_'.$i;
                                                $attr['label'] = false;
                                                echo $this->HtmlField->time('edit', $item, $attr);
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                                $attr = [];
                                                $attr['null'] = true;
                                                $attr['default_time'] = false;
                                                $attr['class'] = 'no-margin-bottom';
                                                $attr['field'] = 'end_time';
                                                $attr['model'] = $fieldPrefix;
                                                $attr['id'] = 'end_time_'.$i;
                                                $attr['label'] = false;
                                                echo $this->HtmlField->time('edit', $item, $attr);
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