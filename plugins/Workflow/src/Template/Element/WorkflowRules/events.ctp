<?php if ($action == 'view') : ?>
    <?php
        $tableHeaders = isset($attr['attr']['tableHeaders']) ? $attr['attr']['tableHeaders'] : [];
        $tableCells = isset($attr['attr']['tableCells']) ? $attr['attr']['tableCells'] : [];
    ?>
    <div class="table-wrapper">
        <div class="table-in-view">
            <table class="table">
                <thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
                <tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
            </table>
        </div>
    </div>
<?php elseif ($action == 'add' || $action == 'edit') : ?>
    <?php
        $model = $ControllerAction['table'];
        $alias = $model->alias();
        $eventOptions = isset($attr['attr']['eventOptions']) ? $attr['attr']['eventOptions'] : [];
        $eventSelectOptions = isset($attr['attr']['eventSelectOptions']) ? $attr['attr']['eventSelectOptions'] : [];
        $this->Form->unlockField("WorkflowRules.workflow_rule_events");
    ?>
    <div class="clearfix"></div>
    <h3><?= isset($attr['label']) ? $attr['label'] : $attr['field']; ?></h3>
    <div class="clearfix">
        <div class="input select">
            <?php
                echo $this->Form->input("$alias.event_method_key", [
                    'label' => __('Add Event'),
                    'type' => 'select',
                    'options' => $eventSelectOptions,
                    'onchange' => "$('#reload').val('addEvent').click();"
                ]);
            ?>
        </div>
        <div class="table-responsive">
            <table class="table table-curved">
                <thead>
                    <th><?= $this->Label->get('general.name'); ?></th>
                    <th><?= __('Description') ?></th>
                    <th></th>
                </thead>
                <?php if (!empty($data->workflow_rule_events)) : ?>
                    <tbody>
                        <?php foreach ($data->workflow_rule_events as $key => $obj) : ?>
                            <?php
                                $prefix = "$alias.workflow_rule_events.$key";
                                $eventKey = $obj['event_key'];
                            ?>
                            <tr class="checked">
                                <td>
                                    <?= $eventOptions[$eventKey]['text']; ?>
                                    <?= $this->Form->hidden("$prefix.event_key", ['value' => $eventKey]); ?>
                                </td>
                                <td>
                                    <?= $eventOptions[$eventKey]['description']; ?>
                                </td>
                                <td>
                                    <a class="btn btn-dropdown action-toggle btn-single-action" title="<?= $this->Label->get('general.delete.label'); ?>" href="#" onclick="jsTable.doRemove(this);$('#reload').click();return false;"><i class="fa fa-trash"></i> <span><?= __('Delete')?></span></a>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                <?php endif ?>
            </table>
        </div>
    </div>
<?php endif ?>
