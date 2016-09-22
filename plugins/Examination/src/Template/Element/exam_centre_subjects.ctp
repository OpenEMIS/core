<?php
    $alias = $ControllerAction['table']->alias();
    if ($ControllerAction['action'] == 'add') {
        $this->Form->unlockField('Examinations.examination_items');
    }
?>

<?php if ($ControllerAction['action'] == 'view') : ?>
    <div class="table-in-view">
        <table class="table">
            <thead>
                <th><?= __('Code') ?></th>
                <th><?= __('Name') ?></th>
            </thead>
            <?php if (isset($data['examination_centre_subjects'])) : ?>
                <tbody>
                    <?php foreach ($data['examination_centre_subjects'] as $i => $item) : ?>
                        <tr>
                            <td><?= $item->education_subject->code; ?></td>
                            <td><?= $item->education_subject->name; ?></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            <?php endif ?>
        </table>
    </div>
<?php endif ?>