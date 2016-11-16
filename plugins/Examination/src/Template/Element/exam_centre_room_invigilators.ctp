<?php
    $alias = $ControllerAction['table']->alias();
    if ($ControllerAction['action'] == 'add') {
        $this->Form->unlockField('Examinations.examination_items');
    }
?>

<?php if ($ControllerAction['action'] == 'view') : ?>
    <?php if (isset($data['invigilators']) && !empty($data['invigilators'])) : ?>
    <div class="table-in-view">
        <table class="table">
            <thead>
                <th><?= __('OpenEMIS ID') ?></th>
                <th><?= __('Name') ?></th>
            </thead>
            <tbody>
                <?php foreach ($data['invigilators'] as $i => $item) :?>
                    <tr>
                        <td><?= $item->openemis_no; ?></td>
                        <td><?= $item->name; ?></td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
    <?php else :
        echo __('No Invigilators');
    ?>

    <?php endif; ?>
<?php endif ?>
