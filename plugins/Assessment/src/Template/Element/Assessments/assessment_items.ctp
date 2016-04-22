<?php use Cake\Utility\Inflector;?>

<?php if ($action == 'add' || $action == 'edit') : ?>
    <style>
        /*table.table-body-scrollable {
            border-spacing: 0;
            border: 1px solid #CCC !important;
            -webkit-box-shadow: inset 0 -5px 10px rgba(0, 0, 0, 0.08);
            -moz-box-shadow: inset 0 -5px 10px rgba(0, 0, 0, 0.08);
            box-shadow: inset 0 -5px 10px rgba(0, 0, 0, 0.08);
            -webkit-border-radius: 5px;
            -moz-border-radius: 5px;
            border-radius: 5px;

        }
        table.table-body-scrollable thead,
        table.table-body-scrollable tbody {
            display: block;
        }
        table.table-body-scrollable tbody {
            height: 250px;
            -webkit-overflow-y: scroll;
            -ms-overflow-y: scroll;
            overflow-y: scroll;
        }*/


        table.table-body-scrollable  {
            width: 100%;
            border-spacing: 0;

            border: 1px solid #CCC !important;
            -webkit-box-shadow: inset 0 -5px 10px rgba(0, 0, 0, 0.08);
            -moz-box-shadow: inset 0 -5px 10px rgba(0, 0, 0, 0.08);
            box-shadow: inset 0 -5px 10px rgba(0, 0, 0, 0.08);
            -webkit-border-radius: 5px;
            -moz-border-radius: 5px;
            border-radius: 5px;
        }

        /*table.table-body-scrollable thead,
        table.table-body-scrollable tbody,
        table.table-body-scrollable tr,
        table.table-body-scrollable th,
        table.table-body-scrollable td { display: block; }

        table.table-body-scrollable thead tr {*/
            /* fallback */
            /*width: 97%;*/
            /* minus scroll bar width */
            /*width: -webkit-calc(100% - 16px);
            width:    -moz-calc(100% - 16px);
            width:         calc(100% - 16px);
        }

        table.table-body-scrollable tr:after {*/  /* clearing float */
            /*content: ' ';
            display: block;
            visibility: hidden;
            clear: both;
        }

        table.table-body-scrollable tbody {
            height: 100px;
            overflow-y: auto;
            overflow-x: hidden;
        }

        table.table-body-scrollable tbody td,
        table.table-body-scrollable thead th {
            width: 19%;*/  /* 19% is less than (100% / 5 cols) = 20% */
            /*float: left;
        }*/

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

    <div class="clearfix"></div>
    <div class="table-wrapper">
        <div class="table-responsive">
            <!-- <table class="table"> -->
            <table class="table table-body-scrollable">
                <thead>
                    <tr>
                        <?php foreach ($attr['formFields'] as $formField) : ?>
                            <?php if ($attr['fields'][$formField]['type']!='hidden') : ?>
                            <?php
                                $associated = explode('.', $formField);
                                if (count($associated)>1) {
                                    $header = Inflector::humanize(str_replace('_id', '', $associated[1]));
                                } else {
                                    $header = Inflector::humanize(str_replace('_id', '', $formField));
                                }
                                $thClass = (isset($attr['fields'][$formField]['required']) && $attr['fields'][$formField]['required']) ? 'required' : '';
                            ?>
                            <th class="<?= $thClass ?>"><label class="table-header-label"><?= $header ?></label></th>
                                <?php if ($attr['fields'][$formField]['type']!='readonly') : ?>
                                <th></th>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endforeach;?>
                    </tr>
                </thead>

                <tbody id="table_assessment_items">
                    
                    <?php //pr(json_encode($assessmentItemsData)); ?>

                    <div class="hidden" ng-init='errors.AssessmentItems = <?= json_encode($assessmentItemsErrors)?>'></div>
                    <div class="hidden" ng-init='assessmentItems = <?= json_encode($assessmentItemsData)?>'></div>
                    <tr ng:repeat="(key, item) in onChangeTargets.assessment_items">

                        <?php if ($action == 'edit'):?>
                        <input type="hidden" id="assessmentitems-id" name="Assessments[assessment_items][{{key}}][id]" value="{{assessmentItems[key].id}}"/>
                        <input type="hidden" id="assessmentitems-assessment-id" name="Assessments[assessment_items][{{key}}][assessment_id]" value="{{assessmentItems[key].assessment_id}}"/>
                        <?php endif;?>

                        <td>{{item.education_subject.code}} - {{item.education_subject.name}}</td>
                        <input type="hidden" id="assessmentitems-education-subject-id" name="Assessments[assessment_items][{{key}}][education_subject_id]" value="{{item.education_subject.id}}"/>
                        <td>
                            <div class="input-select-wrapper">
                                <select id="assessmentitems-{{key}}-assessment-grading-type-id" kd-selected-value="{{assessmentItems[key].assessment_grading_type_id}}" name="Assessments[assessment_items][{{key}}][assessment_grading_type_id]">
                                
                                    <option value=""><?= __('-- Select --')?></option>
                                    <option ng:repeat="option in onChangeTargets.assessment_grading_type_id" value="{{option.id}}" ng-selected="selectedOption('assessmentitems-{{key}}-assessment-grading-type-id', option.id)">     
                                        {{option.name}}
                                    </option>

                                </select>
                            </div>
                        </td>
                        <td>
                            <div class="input error">
                                <div class="error-message">{{errors.AssessmentItems[key].assessment_grading_type_id}}</div>
                            </div>
                        </td>
                        <td>
                            <input type="text" id="assessmentitems-weight" class="form-error" name="Assessments[assessment_items][{{key}}][weight]" value="{{assessmentItems[key].weight}}"/>
                        </td>
                        <td>
                            <div class="input error">
                                <div class="error-message">{{errors.AssessmentItems[key].weight}}</div>
                            </div>
                        </td>
                    </tr>

                </tbody>

            </table>
        </div>
    </div>

<?php else : ?>

        <div class="table-in-view">
            <table class="table">
                <thead>
                    <tr>
                        <?php foreach ($attr['formFields'] as $formField) : ?>
                            <?php
                                $associated = explode('.', $formField);
                                if (count($associated)>1) {
                                    $header = Inflector::humanize(str_replace('_id', '', $associated[1]));
                                } else {
                                    $header = Inflector::humanize(str_replace('_id', '', $formField));
                                }
                            ?>
                            <th><?= $header ?></th>
                        <?php endforeach;?>
                    </tr>
                </thead>
                <tbody>
                <?php 
                if (count($data->assessment_items)>0) :
                    // iterate each row
                    foreach ($data->assessment_items as $key => $item) :
                ?>
                    <tr>

                    <?php 
                        // iterate each field in a row
                        foreach ($attr['formFields'] as $formField):
                            $field = $attr['fields'][$formField];
                            $associated = explode('.', $formField);
                            if (count($associated)>1) {
                                $record = $item->$associated[0];
                            } else {
                                $record = $item;
                            }
                            if ($field['field']=='education_subject_id') {
                                $field['value'] = $record->education_subject_name;
                            }
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
