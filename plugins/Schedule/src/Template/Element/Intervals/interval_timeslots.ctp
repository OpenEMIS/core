<?php
    $alias = $ControllerAction['table']->alias();
    $fieldKey = 'timeslots';
    $action = $ControllerAction['action'];

    if ($ControllerAction['action'] == 'add' || $ControllerAction['action'] == 'edit') {
        // $this->Form->unlockField('Examinations.examination_items');
    }

    // pr($data);
    // die;
?>

<?php if ($ControllerAction['action'] == 'view') : ?>
    <div class="table-in-view">
        <table class="table">
            <thead>
                <th><?= __('Code') ?></th>
                <th><?= __('Name') ?></th>
                <th><?= __('Weight') ?></th>
                <th><?= __('Education Subject') ?></th>
                <th><?= __('Grading Type') ?></th>
                <th><?= __('Date') ?></th>
                <th><?= __('Start Time') ?></th>
                <th><?= __('End Time') ?></th>
            </thead>

            <?php if (isset($data['examination_items'])) : ?>
                <tbody>
                    <?php foreach ($data['examination_items'] as $i => $item) : ?>
                        <tr>
                            <td><?= $item->code ?></td>
                            <td><?= $item->name ?></td>
                            <td><?= $item->weight ?></td>
                            <td>
                                <?php
                                    if ($item->has('education_subject') && $item->education_subject->has('name')) {
                                        echo $item->education_subject->name;
                                    }
                                ?>
                            </td>
                            <td><?= $item->examination_grading_type->name ?></td>
                            <td><?= !is_null($item->examination_date) ? $item->examination_date->format('d-m-Y') : '' ?></td>
                            <td><?= !is_null($item->start_time) ? $item->start_time->format('H:i A') : '' ?></td>
                            <td><?= !is_null($item->end_time) ? $item->end_time->format('H:i A') : '' ?></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            <?php endif ?>
        </table>
    </div>

<?php elseif ($ControllerAction['action'] == 'add') : ?>
    <?php
        if ($ControllerAction['action'] == 'add') {
            echo $this->Form->input('<i class="fa fa-plus"></i> <span>'.__('Add').'</span>', [
                'label' => __('Add Interval'),
                'type' => 'button',
                'class' => 'btn btn-default',
                'aria-expanded' => 'true',
                'onclick' => "$('#reload').val('addTimeslot').click();"
            ]);
        }
    ?>
    <div class="input clearfix required">
        <label><?= __($attr['label']) ?></label>
        <div class="input-form-wrapper">
            <div class="table-wrapper">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <th><?= __('Start Time') ?></th>
                            <th><?= __('End Time') ?></th>
                            <th><?= __('Interval') ?></th>
                            <th class="cell-delete"></th>
                        </thead>
                        <?php if (isset($data[$fieldKey])) : ?>
                            <tbody>
                                <?php foreach ($data[$fieldKey] as $i => $slot) : ?>
                                    <?php
                                        $fieldPrefix = "$alias.$fieldKey.$i";
                                        $joinDataPrefix = $fieldPrefix . '._joinData';
                                    ?>
                                    <tr>
                                        <td>
                                            <?php
                                                echo $slot->start_time;
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                                echo $slot->end_time;
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                                echo $this->Form->input("$fieldPrefix.inteval", [
                                                    'type' => 'string',
                                                    'label' => false
                                                ]);
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                                if (empty($item->student_results)) {
                                                    echo "<button onclick='jsTable.doRemove(this);' aria-expanded='true' type='button' class='btn btn-dropdown action-toggle btn-single-action'><i class='fa fa-trash'></i>&nbsp;<span>Delete</span></button>";
                                                } else {
                                                    $message = __('There are results for this examination item');
                                                    echo '<i class="fa fa-info-circle fa-lg icon-blue" data-toggle="tooltip" data-container="body" data-placement="top" data-animation="false" title="" data-html="true" data-original-title="' . $message . '"></i>';
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
