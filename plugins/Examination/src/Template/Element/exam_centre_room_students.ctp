<?php
    $alias = $ControllerAction['table']->alias();
    if ($ControllerAction['action'] == 'add') {
        $this->Form->unlockField('Examinations.examination_items');
    }
?>

<?php if ($ControllerAction['action'] == 'view') : ?>
    <?php if (isset($data['students']) && !empty($data['students'])) : ?>
    <div class="table-in-view">
        <table class="table">
            <thead>
                <th><?= __('Registration Number') ?></th>
                <th><?= __('OpenEMIS ID') ?></th>
                <th><?= __('Name') ?></th>
            </thead>
            <tbody>
                <?php foreach ($data['students'] as $i => $item) :?>
                    <tr>
                        <td><?php
                        $registrationNo = isset($attr['registrationNoList'][$item->id]) ? $attr['registrationNoList'][$item->id] : '';
                        echo $registrationNo;
                        ?></td>
                        <td><?= $item->openemis_no; ?></td>
                        <td><?= $item->name; ?></td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
    <?php else :
        echo __('No Students');
    ?>

    <?php endif; ?>
<?php endif ?>
