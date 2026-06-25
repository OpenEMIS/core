<?php
$model = $ControllerAction['table'];
$alias = $model->getAlias();
$this->Form->create();
$this->Form->unlockField("$alias.staff_leave_types");
//dd($data);
?>

<?php if ($ControllerAction['action'] == 'view') : ?>
    <div class="table-wrapper">
        <div class="table-in-view">
            <table class="table">
                <thead>
                <tr>
                    <th><?= __('Code'); ?></th>
                    <th><?= __('Name'); ?></th>
                    <th><?= __('Days'); ?></th>
                    <th><?= __('Rollover'); ?></th>
                </tr>
                </thead>
                <?php if (!empty($data->staff_leave_types)) : ?>
                    <tbody>
                    <?php foreach ($data->staff_leave_types as $key => $type) : ?>

                        <tr>
                            <td><?= h($type['code']); ?></td>
                            <td><?= h($type['name']); ?></td>
                            <td><?= h($type['days']); ?></td>
                            <td><?= $type['rollover'] == 1 ? __('Yes') : __('No'); ?></td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                <?php endif ?>
            </table>
        </div>
    </div>
<?php elseif ($ControllerAction['action'] == 'add' || $ControllerAction['action'] == 'edit') : ?>

    <div class="input">
        <label for="<?= $attr['id'] ?>"><?= isset($attr['label']) ? $attr['label'] : $attr['field'] ?></label>
        <div class="input-form-wrapper">
            <div class="table-wrapper">
                <div class="table-in-view">
                    <table class="table table-checkable table-input">
                        <thead>
                        <tr>
                            <th><?= __('Enable'); ?></th>
                            <th><?= __('Code'); ?></th>
                            <th><?= __('Name'); ?></th>
                            <th><?= __('Days'); ?></th>
                            <th><?= __('Rollover'); ?></th>
                            <th></th>
                        </tr>
                        </thead>
                        <?php if (!empty($data->staff_leave_types)) : ?>
                            <tbody>
                            <?php foreach ($data->staff_leave_types as $key => $type) : ?>
                                <tr>
                                    <td class="checkbox-column">
                                        <?= $this->Form->checkbox("$alias.staff_leave_types.$key.enable", ['checked' => $type['enable'], 'class' => 'no-selection-label', 'kd-checkbox-radio' => '']) ?>
                                    </td>
                                    <td>
                                        <?php
                                        if (isset($type['staff_policy_leave_type_id'])) {
                                            echo $this->Form->hidden("$alias.staff_leave_types.$key.staff_policy_leave_type_id", ['value' => $type['staff_policy_leave_type_id'], 'label' => false]);
                                        }
                                        if (!isset($type['staff_policy_leave_type_id'])) {
                                            echo $this->Form->hidden("$alias.staff_leave_types.$key.staff_policy_leave_type_id", ['value' => null, 'label' => false]);
                                        }
                                        if (isset($type['staff_leave_type_id'])) {
                                            echo $this->Form->hidden("$alias.staff_leave_types.$key.staff_leave_type_id", ['value' => $type['staff_leave_type_id'], 'label' => false]);
                                        }
                                        echo $this->Form->input("$alias.staff_leave_types.$key.code", ['value' => $type['code'], 'label' => false, 'readonly' => true]);
                                        ?>
                                    </td>
                                    <td>
                                        <?= $this->Form->input("$alias.staff_leave_types.$key.name", ['value' => $type['name'], 'readonly' => true, 'label' => false]); ?>
                                    </td>
                                    <td>
                                        <?= $this->Form->input("$alias.staff_leave_types.$key.days", ['value' => $type['days'], 'label' => false, 'type' => 'number']); ?>
                                    </td>
                                    <td>
                                        <?= $this->Form->select("$alias.staff_leave_types.$key.rollover", ['1' => __('Yes'), '0' => __('No')], ['value' => $type['rollover']]); ?>
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
