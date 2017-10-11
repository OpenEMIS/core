<?php
    $this->Form->unlockField('staff_positions');
    $showRadioButtons = isset($attr['showRadioButtons']) ? $attr['showRadioButtons'] : false;
    $staffData = isset($attr['staffData']) ? $attr['staffData'] : [];
?>

<?php if ($ControllerAction['action'] == 'add' || $ControllerAction['action'] == 'edit') : ?>
    <div class="input clearfix">
        <label><?= isset($attr['label']) ? __($attr['label']) : __($attr['field']) ?></label>
            <div class="table-wrapper">
                <div class="table-in-view">
                    <table class="table table-checkable">
                        <thead>
                            <?php if ($showRadioButtons) : ?>
                                <th></th>
                            <?php endif ?>
                            <th><?= __('Institution Position') ?></th>
                            <th><?= __('Start Date') ?></th>
                            <th><?= __('FTE') ?></th>
                            <th><?= __('Staff Type') ?></th>
                        </thead>
                        <tbody>
                            <?php foreach ($staffData as $i => $item) : ?>
                                <tr class="checked">
                                    <?php if ($showRadioButtons) : ?>
                                        <td class="checkbox-column">
                                            <?php if ($item['selected']) : ?>
                                                <input class="no-selection-label" kd-checkbox-radio type="radio" name="staff_positions" value="<?= $item['institution_staff_id'] ?>" checked>
                                            <?php else : ?>
                                                <input class="no-selection-label" kd-checkbox-radio type="radio" name="staff_positions" value="<?= $item['institution_staff_id'] ?>">
                                            <?php endif ?>
                                        </td>
                                    <?php endif ?>
                                    <td><?= $item['position'] ?></td>
                                    <td><?= $item['start_date'] ?></td>
                                    <td><?= $item['fte'] ?></td>
                                    <td><?= $item['staff_type'] ?></td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            </div>
    </div>
<?php endif ?>
