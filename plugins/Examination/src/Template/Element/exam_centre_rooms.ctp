<?php
    $alias = $ControllerAction['table']->alias();
    if ($ControllerAction['action'] == 'add') {
        $this->Form->unlockField('Examinations.examination_items');
    }
?>

<?php if ($ControllerAction['action'] == 'view') : ?>
    <?php if (isset($data['examination_centre_rooms'])) : ?>
    <div class="table-in-view">
        <table class="table">
            <thead>
                <th><?= __('Name') ?></th>
                <th><?= __('Size') ?></th>
                <th><?= __('Number of Seats') ?></th>
                <th><?= __('Number of Students')?></th>
                <th><?= __('Action') ?></th>
            </thead>
            <tbody>
                <?php foreach ($data['examination_centre_rooms'] as $i => $item) :?>
                    <tr>
                        <td><?= $item->name; ?></td>
                        <td><?= $item->size; ?></td>
                        <td><?= $item->number_of_seats; ?></td>
                        <td><?= count($item->examination_centre_room_students)?></td>
                        <td></td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
    <?php endif ?>

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
                </thead>
                <?php if (isset($attr['data'])) : ?>
                    <tbody>
                        <?php foreach ($attr['data'] as $i => $item) : ?>
                            <tr>
                                <td><?= $item['subject_code']; ?></td>
                                <td><?= __($item['subject_name']); ?></td>
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