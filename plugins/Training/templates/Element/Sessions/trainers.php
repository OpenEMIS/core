<?php
$tableClass = 'table-in-view';
$tableHeaders = isset($attr['tableHeaders']) ? $attr['tableHeaders'] : [];
$tableCells = isset($attr['tableCells']) ? $attr['tableCells'] : [];
$trainerTypeOptions = isset($attr['trainerTypeOptions']) ? $attr['trainerTypeOptions'] : [];
?>

<?php if ($ControllerAction['action'] == 'edit' || $ControllerAction['action'] == 'add') : ?>
    <?php $this->Form->create($entity); ?>
    <?php $this->Form->unlockField('trainer_id'); ?>
    <?php $alias = $ControllerAction['table']->getAlias(); ?>
    <?php $requestData = $this->request->getData($alias); ?>
    <?php $tableClass = 'table-responsive'; ?>
    <div class="clearfix"></div>
    <hr>
    <h3><?= __('Trainers') ?></h3>
    <?php
    $url = $this->Url->build([
        'plugin' => $this->request->getParam('plugin'),
        'controller' => $this->request->getParam('controller'),
        'action' => $this->request->getParam('action'),
        'ajaxTrainerAutocomplete'
    ]);

    // POCOR-3556 add type fields
    echo $this->Form->input("$alias.type", [
        'label' => __('Type'),
        'type' => 'select',
        'options' => $trainerTypeOptions,
        'onchange' => "$('#reload').val('type').click();"
    ]);


    $trainerType = (isset($requestData['type'])) ? $requestData['type'] : 'Staff';
    // End POCOR-3556

    echo $this->Form->input("$alias.trainer_search", [
        'label' => __('Add Trainer'),
        'type' => 'text',
        'class' => 'autocomplete',
        'value' => '',
        'autocomplete-url' => $url,
        'autocomplete-no-results' => __('No Trainer found.'),
        'autocomplete-class' => 'error-message',
        'autocomplete-target' => 'trainer_id',
        'autocomplete-submit' => "$('#reload').val('addTrainer').click();",
        'autocomplete-before-search' => 'Autocomplete.extra["type"] = "' . $trainerType . '"'
    ]);
    echo $this->Form->hidden("$alias.trainer_id", ['autocomplete-value' => 'trainer_id']);
    ?>
    <div class="clearfix"></div>
    <hr>
<?php endif ?>

<div class="<?= $tableClass; ?>" autocomplete-ref="trainer_id">
    <table class="table">
        <thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
        <tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
    </table>
</div>
