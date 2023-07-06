<?= $this->Html->script('OpenEmis.../plugins/tableCheckable/jquery.tableCheckable', ['block' => true]) ?>
<?php
$alias = $ControllerAction['table']->alias();
$checkboxOptions = ['class' => 'no-selection-label', 'kd-checkbox-radio' => ''];
?>
<?php if ($action == 'add') :?>

    <div class="input clearfix required">
        <label><?= isset($attr['label']) ? __($attr['label']) : __($attr['field']) ?></label>
        <div class="table-wrapper">
            <div class="table-in-view">
                <table class="table table-checkable">
                    <thead>
                        <tr>
                            <th class="checkbox-column"><input type="checkbox" class="no-selection-label", kd-checkbox-radio=""/></th>
                            <th><?= __('Education Subject Code') ?></th>
                            <th><?= __('Education Subject Grade') ?></th>
                            </tr>
                    </thead>

                    <tbody>
               
                    <?php if (isset($attr['data']) && count($attr['data'])>0) : ?>
                        <!-- <?php foreach ($attr['data'] as $i => $obj) : ?>
                            <tr>
                            <td class="checkbox-column">
                                    <?php
                                        echo $this->Form->checkbox("$fieldPrefix.selected", $checkboxOptions);
                                        echo $this->Form->hidden("$fieldPrefix.student_id", ['value' => $obj->student_id]);

                                    ?>
                                </td> -->
                    <?php else : ?>
                        <tr><td colspan="5"><?= __('There are no subjects selected') ?></td></tr>
                    <?php endif ?>

                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif ?>
