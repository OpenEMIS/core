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
                <th><?= __('Education Subject') ?></th>
            </thead>
            <?php if (isset($data['examination_items'])) : ?>
                <tbody>
                    <?php foreach ($data['examination_items'] as $i => $item) : ?>
                        <tr>
                            <td><?= $item->code; ?></td>
                            <td><?= $item->name; ?></td>
                            <td>
                                <?php
                                    if ($item->has('education_subject') && $item->education_subject->has('name')) {
                                        echo $item->education_subject->name;
                                    }
                                ?>
                            </td>
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
                    <th><?= __('Education Subject') ?></th>
                </thead>
                <?php if (isset($attr['data'])) : ?>
                    <tbody>
                        <?php foreach ($attr['data'] as $i => $item) : ?>
                            <tr>
                                <td><?= $item['item_code']; ?></td>
                                <td><?= __($item['item_name']); ?></td>
                                <td><?= __($item['education_subject']); ?></td>
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