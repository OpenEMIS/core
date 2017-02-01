<?php
    $alias = $ControllerAction['table']->alias();
    $this->Form->unlockField('Competency.competency_items');
    // pr($data);
?>

<?php if ($ControllerAction['action'] == 'view') { ?>
    <div class="table-in-view">
        <table class="table">
            <?php if (isset($data['competency_items'])) : ?>
                <tbody>
                    <?php foreach ($data['competency_items'] as $i => $item) : ?>
                        <tr>
                            <td><?= $item->name; ?></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            <?php endif ?>
        </table>
    </div>
<?php } else if ($ControllerAction['action'] == 'edit') { ?>
    <div class="input required">
    <label><?= isset($attr['label']) ? __($attr['label']) : __($attr['field']) ?></label>
        <div class="table-wrapper">
            <div class="table-in-view">
                <table class="table">
                    <?php if (isset($data['competency_items'])) : ?>
                        <tbody>
                            <?php foreach ($data['competency_items'] as $i => $item) : ?>
                                <tr>
                                    <td><?= $item->name; ?></td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    <?php endif ?>
                </table>
            </div>
        </div>
    </label>
    </div>
<?php } ?>