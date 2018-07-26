<?php
    $model = $ControllerAction['table'];
    $alias = $model->alias();
    $this->Form->unlockField("$alias.appraisal_dropdown_options");
    $this->Form->unlockField("$alias.is_default");
?>

<?php if ($ControllerAction['action'] == 'view') : ?>
    <div class="table-wrapper">
        <div class="table-in-view">
            <table class="table">
                <thead>
                    <tr>
                        <th><?= $this->Label->get('general.name'); ?></th>
                        <th><?= $this->Label->get('general.default'); ?></th>
                    </tr>
                </thead>
                <?php if (!empty($data->appraisal_dropdown_options)) : ?>
                    <tbody>
                        <?php foreach ($data->appraisal_dropdown_options as $key => $obj) : ?>
                            <tr>
                                <td><?= $obj->name; ?></td>
                                <td>
                                    <?php if ($obj->is_default == 1) : ?>
                                        <i class="fa fa-check"></i>
                                    <?php else : ?>
                                        <i class="fa fa-close"></i>
                                    <?php endif ?>
                                </td>
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
            <div class="table-toolbar">
                <button onclick="$('#reload').val('addOption').click();return false;" class="btn btn-default btn-xs">
                    <i class="fa fa-plus"></i>
                    <span><?= __('Add');?></span>
                </button>
            </div>
            <div class="table-wrapper">
                <table class="table table-checkable table-input">
                    <thead>
                        <tr>
                            <th><?= $this->Label->get('general.name'); ?></th>
                            <th><?= $this->Label->get('general.default'); ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <?php if (!empty($data->appraisal_dropdown_options)) : ?>
                        <tbody>
                            <?php foreach ($data->appraisal_dropdown_options as $key => $obj) : ?>
                                <tr>
                                    <td>
                                        <?php
                                            if ($obj->has('id')) {
                                                echo $this->Form->hidden("$alias.appraisal_dropdown_options.$key.id");
                                            }
                                            echo $this->Form->input("$alias.appraisal_dropdown_options.$key.name", ['label' => false]);
                                            echo $this->Form->hidden("$alias.appraisal_dropdown_options.$key.is_default", ['value' => 0]);
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                            $attributes = ['label' => false, 'legend' => false, 'hiddenField' => false];
                                            if ($data->appraisal_dropdown_options[$key]->is_default == 1) {
                                                $attributes['value'] = $key;
                                            }
                                        ?>
                                        <?= $this->Form->radio("$alias.is_default", [$key => false], $attributes) ?>
                                        <?= $this->Form->label("$alias.is_default", false, ['for' => null]) ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($obj->appraisal_dropdown_answers)) : ?>
                                            <?= __('In use') ?>
                                        <?php else : ?>
                                            <button class="btn btn-dropdown action-toggle btn-single-action" style="cursor: pointer;" title="<?= $this->Label->get('general.delete.label'); ?>" onclick="jsTable.doRemove(this);">
                                            <i class="fa fa-trash"></i>&nbsp;<span><?= __('Delete')?></span>
                                            </button>
                                        <?php endif ?>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    <?php endif ?>
                </table>
            </div>
        </div>
    </div>
<?php endif ?>
