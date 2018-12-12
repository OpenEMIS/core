<?php
    $alias = $ControllerAction['table']->alias();
    $fieldKey = 'timeslots';
    $action = $ControllerAction['action'];

    if ($ControllerAction['action'] == 'add') {
        $this->Form->unlockField($alias . '.' . $fieldKey);
    }
    // pr($data);
    // die;
?>

<?php if ($ControllerAction['action'] == 'view') : ?>
    <div class="table-in-view">
        <table class="table">
            <thead>
                <th><?= __('Start Time') ?></th>
                <th><?= __('End Time') ?></th>
                <th><?= __('Interval') . ' (' . __('mins') . ')' ?></th>
            </thead>

            <?php if ($data->has('timeslots') && !empty($data->timeslots)) : ?>
                <tbody>
                    <?php foreach ($data->timeslots as $i => $timeslot) : ?>
                        <tr>
                            <td><?= $timeslot->start_time ?></td>
                            <td><?= $timeslot->end_time ?></td>
                            <td><?= $timeslot->interval ?></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            <?php endif ?>
        </table>
    </div>

<?php elseif ($ControllerAction['action'] == 'add') : ?>
    <?php
        $addButtonAttr = [
            'label' => __('Add Interval'),
            'type' => 'button',
            'class' => 'btn btn-default',
            'aria-expanded' => 'true',
            'onclick' => "$('#reload').val('addTimeslot').click();"
        ];

        if (!$data->has('institution_shift_id') || $ControllerAction['action'] == 'edit') {
            $addButtonAttr['disabled'] = 'disabled';
        }

        echo $this->Form->input('<i class="fa fa-plus"></i> <span>'.__('Add').'</span>', $addButtonAttr);
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
                            <th><?= __('Interval') . ' (' . __('mins') . ')' ?></th>
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
                                                echo $this->Form->input("$fieldPrefix.interval", [
                                                    'type' => 'number',
                                                    'label' => false,
                                                    'onblur' => "$('#reload').val('changeInterval').click();"
                                                ]);
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                                if ($i == (count($data[$fieldKey]) - 1)) {
                                                    echo '<button onclick="jsTable.doRemove(this); $(\'#reload\').click();" aria-expanded="true" type="button" class="btn btn-dropdown action-toggle btn-single-action"><i class="fa fa-trash"></i>&nbsp;<span>'.__('Delete').'</span></button>';
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

<?php elseif ($ControllerAction['action'] == 'edit') : ?>
    <div class="input clearfix required">
        <label><?= __($attr['label']) ?></label>
        <div class="input-form-wrapper">
            <div class="table-wrapper">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <th><?= __('Start Time') ?></th>
                            <th><?= __('End Time') ?></th>
                            <th><?= __('Interval') . ' (' . __('mins') . ')' ?></th>
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
                                                echo $slot->interval;
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
