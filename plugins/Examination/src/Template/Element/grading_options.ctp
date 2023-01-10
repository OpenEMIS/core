<?php use Cake\Utility\Inflector;?>

<?php if ($action == 'add' || $action == 'edit') : ?>
    <style>
        table .error-message-in-table {
            min-width: 100px;
            width: 100%;
        }
        table th label.table-header-label {
          background-color: transparent;
          border: medium none;
          margin: 0;
          padding: 0;
        }
    </style>

    <div class="input clearfix">
        <div class="clearfix">
        <?php
            echo $this->Form->input('<i class="fa fa-plus"></i> <span>'.__('Add New Option').'</span>', [
                'label' => __('Grading Options'),
                'type' => 'button',
                'class' => 'btn btn-default',
                'aria-expanded' => 'true',
                'onclick' => "$('#reload').val('reload').click();",
                'required' =>'required'
            ]);
            $this->Form->unlockField('ExaminationGradingTypes.grading_options');
        ?>
        </div>
        <div class="table-wrapper full-width">
            <div class="table-responsive">
                <table class="table table-curved table-input row-align-top">
                    <thead>
                        <tr>
                            <?php foreach ($attr['formFields'] as $formField) : ?>
                                <?php if ($attr['fields'][$formField]['type']!='hidden') : ?>
                                    <?php
                                    $thClass = (isset($attr['fields'][$formField]['required']) && $attr['fields'][$formField]['required']) ? 'required' : '';
                                    ?>
                                    <th class="<?= $thClass ?>"><label class="table-header-label"><?= __(Inflector::humanize($formField)) ?></label></th>
                                    <th></th>
                                <?php endif; ?>
                            <?php endforeach;?>

                            <th class="cell-delete"></th>
                        </tr>
                    </thead>

                    <tbody id='table_grading_options'>

                        <?php
                        if (count($data->grading_options)>0) :
                            // iterate each row
                            foreach ($data->grading_options as $key => $record) :
                                $rowErrors = $record->errors();
                                if ($rowErrors) {
                                    $trClass = 'error';
                                } else {
                                    $trClass = '';
                                }
                        ?>
                        <tr class="<?= $trClass ?>">

                            <?php
                                // iterate each field in a row
                                foreach ($attr['formFields'] as $i):
                                    $field = $attr['fields'][$i];
                                    $fieldErrors = $record->errors($field['field']);
                                    if ($fieldErrors) {
                                        $tdClass = 'error';
                                        $fieldClass = 'form-error';
                                    } else {
                                        $tdClass = '';
                                        $fieldClass = '';
                                    }
                                    $fieldAttributes = isset($field['attr']) ? $field['attr'] : [];
                                    $options = array_merge([
                                                    'label'=>false,
                                                    'name'=>'ExaminationGradingTypes[grading_options]['.$key.']['.$field['field'].']',
                                                    'class'=>$fieldClass,
                                                    'value'=>$record->{$field['field']}
                                                ],
                                                $fieldAttributes);
                            ?>
                                <?php if ($field['type']!='hidden') : ?>

                                    <td class="<?= $tdClass ?>">
                                        <?= $this->HtmlField->{$field['type']}('edit', $record, $field, $options); ?>
                                    </td>

                                    <td class="<?= $tdClass ?>">
                                        <?php if ($fieldErrors) : ?>
                                            <ul class="error-message">
                                            <?php foreach ($fieldErrors as $error) : ?>
                                                <li><?= $error ?></li>
                                            <?php endforeach ?>
                                            </ul>
                                        <?php else: ?>
                                            &nbsp;
                                        <?php endif; ?>
                                    </td>

                                <?php else : ?>
                                    <?= $this->HtmlField->{$field['type']}('edit', $record, $field, $options);?>
                                <?php endif; ?>

                            <?php endforeach;?>

                            <td>
                                <?php
                                    if ($action == 'edit' || $action == 'add') {
                                        if (!is_null($gradingOptions)) {
                                            // check the value of the gradingOptions, if have association will return true, and display 'in use'
                                            if ($gradingOptions[$data->grading_options[$key]['id']]) {
                                                echo __('In use');
                                            } else {
                                                echo $this->Form->input('<i class="fa fa-trash"></i> <span>Delete</span>', [
                                                    'label' => false,
                                                    'type' => 'button',
                                                    'class' => 'btn btn-dropdown action-toggle btn-single-action',
                                                    'title' => "Delete",
                                                    'aria-expanded' => 'true',
                                                    'onclick' => "jsTable.doRemove(this); "
                                                ]);
                                            }
                                        }
                                    }
                                ?>
                            </td>
                        </tr>
                        <?php
                            endforeach;
                        endif;
                        ?>

                    </tbody>

                </table>
            </div>
        </div>
    </div>

<?php else : ?>

    <div class="table-in-view">
        <table class="table">
            <thead>
                <tr>
                    <?php foreach ($attr['formFields'] as $formField) : ?>
                        <th><?= __(Inflector::humanize(str_replace('_id', '', $formField))) ?></th>
                    <?php endforeach;?>
                </tr>
            </thead>
            <tbody>
            <?php
            if (count($data->grading_options)>0) :
                // iterate each row
                foreach ($data->grading_options as $key => $record) :
            ?>
                <tr>

                <?php
                    // iterate each field in a row
                    foreach ($attr['formFields'] as $formField):
                        $field = $attr['fields'][$formField];
                ?>

                    <td><?= $this->HtmlField->{$field['type']}('view', $record, $field, ['label'=>false, 'name'=>'']); ?></td>

                <?php endforeach;?>

                </tr>
            <?php
                endforeach;
            endif;
            ?>
            </tbody>
        </table>
    </div>

<?php endif ?>
