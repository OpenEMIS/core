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
                <th><?= __('OpenEMIS ID') ?></th>
                <th><?= __('Name') ?></th>
            </thead>
            <tbody>
                <?php foreach ($data['students'] as $i => $item) :?>
                    <tr>
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

<?php elseif ($ControllerAction['action'] == 'edit' || $ControllerAction['action'] == 'add') : ?>
    <?php $tableClass = 'table-responsive'; ?>
    <div class="clearfix"></div>
    <hr>
    <h3><?= __('Students') ?></h3>
    <?php
        $url = $this->Url->build([
            'plugin' => $this->request->params['plugin'],
            'controller' => $this->request->params['controller'],
            'action' => $this->request->params['action'],
            'ajaxStudentAutocomplete',
            $examCentreId
        ]);
        $alias = $ControllerAction['table']->alias();

        echo $this->Form->input("$alias.student_search", [
            'label' => __('Add Student'),
            'type' => 'text',
            'class' => 'autocomplete',
            'value' => '',
            'autocomplete-url' => $url,
            'autocomplete-no-results' => __('No Students found.'),
            'autocomplete-class' => 'error-message',
            'autocomplete-target' => 'student_id',
            'autocomplete-submit' => "$('#reload').val('addStudents').click();"
        ]);
        echo $this->Form->hidden("$alias.student_id", ['autocomplete-value' => 'student_id']);
    ?>
    <div class="clearfix"></div>
    <hr>

    <div class="<?= $tableClass; ?>" autocomplete-ref="student_id">
        <table class="table">
            <thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
            <tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
        </table>
    </div>
<?php endif ?>

