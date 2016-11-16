<?php
    $alias = $ControllerAction['table']->alias();
    if ($ControllerAction['action'] == 'add') {
        $this->Form->unlockField('Examinations.examination_items');
    }
?>

<?php if ($ControllerAction['action'] == 'view') : ?>
    <?php if (isset($data['institutions']) && !empty($data['institutions'])) : ?>
    <div class="table-in-view">
        <table class="table">
            <thead>
                <th><?= __('Name') ?></th>
            </thead>
                <tbody>
                    <?php foreach ($data['institutions'] as $i => $item) : ?>
                        <tr>
                            <td><?= $item->code_name; ?></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>

        </table>
    </div>
    <?php else:
        echo __('No Institutions Linked');
    ?>
    <?php endif ?>
<?php endif ?>