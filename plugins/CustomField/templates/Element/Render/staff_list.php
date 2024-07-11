<?php
$fieldPrefix = $ControllerAction['table']->getAlias() . '.institution_student_surveys.' . $attr['customField']->id;
$classOptions = isset($attr['attr']['classOptions']) ? $attr['attr']['classOptions'] : [];
$tableHeaders = isset($attr['tableHeaders']) ? $attr['tableHeaders'] : [];
$tableCells = isset($attr['tableCells']) ? $attr['tableCells'] : [];
?>
<?php if ($ControllerAction['action'] == 'view') : ?>
    <?php
    $fieldId = isset($attr['customField']->id) ? $attr['customField']->id : 0;

    $url = [
        'plugin' => $this->request->getParam('plugin'),
        'controller' => $this->request->getParam('controller'),
        'action' => $this->request->getParam('action')
    ];
    if (!empty($this->request->getParam('pass'))) {
        $url = array_merge($url, $this->request->getParam('pass'));
    }

    $dataNamedGroup = [];
    if (!empty($this->request->getQuery())) {
        foreach ($this->request->getQuery() as $key => $value) {
            if (in_array($key, ['field_id', 'class_id'])) continue;
            echo $this->Form->hidden($key, [
                'value' => $value,
                'data-named-key' => $key
            ]);
            $dataNamedGroup[] = $key;
        }
    }

    // Survey Question Id
    $url['field_id'] = $fieldId;
    // End

    $baseUrl = $this->Url->build($url);
    $template = $this->ControllerAction->getFormTemplate();
    $this->Form->templates($template);
    ?>

    <div class="clearfix"></div>
    <div class="table-wrapper">
        <div class="table-in-view">
            <table class="table">
                <thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
                <tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
            </table>
        </div>
    </div>
<?php elseif ($ControllerAction['action'] == 'add' || $ControllerAction['action'] == 'edit') : ?>
    <div class="clearfix"></div>
    <hr>
    <h3><?= $attr['attr']['label']; ?></h3>
    <div class="clearfix">
    </div>
    <div class="table-wrapper">
        <div class="table-responsive">
            <table class="table table-curved">
                <thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
                <tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
            </table>
        </div>
    </div>
<?php endif ?>