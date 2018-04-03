<?php
namespace StaffAppraisal\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;

class AppraisalFormsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsToMany('AppraisalCriterias', [
            'className' => 'StaffAppraisal.AppraisalCriterias',
            'foreignKey' => 'appraisal_form_id',
            'targetForeignKey' => 'appraisal_criteria_id',
            'joinTable' => 'appraisal_forms_criterias',
            'through' => 'StaffAppraisal.AppraisalFormsCriterias',
            'dependent' => true,
            'cascadeCallbacks' => true,
            'sort' => 'AppraisalFormsCriterias.order'
        ]);
        $this->hasMany('AppraisalPeriods', ['className' => 'StaffAppraisal.AppraisalPeriods', 'foreignKey' => 'appraisal_form_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('AppraisalFormsCriterias', ['className' => 'StaffAppraisal.AppraisalFormsCriterias', 'foreignKey' => 'appraisal_form_id', 'dependent' => true, 'cascadeCallbacks' => true, 'sort' => 'AppraisalFormsCriterias.order']);

        $this->setDeleteStrategy('restrict');
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['AppraisalCriterias.FieldTypes']);
    }

    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('custom_fields', ['type' => 'custom_order_field', 'after' => 'name']);
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('custom_fields', ['type' => 'custom_order_field', 'after' => 'name']);
    }

    public function onGetCustomOrderFieldElement(Event $event, $action, $entity, $attr, $options = [])
    {
        if ($action == 'view') {
            $tableHeaders = [__('Criteria'), __('Field Type'), __('Is Mandatory')];
            $tableCells = [];
            $customFormId = $entity->id;
            $customFields = $entity->appraisal_criterias;

            $sectionName = "";
            $printSection = false;
            foreach ($customFields as $key => $obj) {
                if (!empty($obj['_joinData']['section']) && $obj['_joinData']['section'] != $sectionName) {
                    $sectionName = $obj['_joinData']['section'];
                    $printSection = true;
                }
                if (!empty($sectionName) && $printSection) {
                    $rowData = [];
                    $rowData[] = '<div class="section-header">'.$sectionName.'</div>';
                    $rowData[] = ''; // Field Type
                    $rowData[] = ''; // Is Mandatory
                    $tableCells[] = $rowData;
                    $printSection = false;
                }
                $rowData = [];
                $rowData[] = $obj['name'];
                $rowData[] = $obj['field_type']['name'];
                $rowData[] = $obj['_joinData']['is_mandatory'] ? '<i class="fa fa-check"></i>' : '<i class="fa fa-close"></i>';
                $tableCells[] = $rowData;
            }
            $attr['tableHeaders'] = $tableHeaders;
            $attr['tableCells'] = $tableCells;
        } elseif ($action == 'add' || $action == 'edit') {
            $form = $event->subject()->Form;
            $form->unlockField($this->alias() . '.appraisal_criterias');
            $attr = [];

            // Showing the list of the questions that are already added
            $criteriaList = $this->AppraisalCriterias
                ->find('list')
                ->toArray();
            $attr['customElementLabel'] = 'Add Criterias';
            $criteriaList = ['' => '-- '. __($attr['customElementLabel']) .' --'] + $criteriaList;

            $arrayFields = [];
            if ($this->request->is('get')) {
                $customFields = $entity->appraisal_criterias ? $entity->appraisal_criterias : [];
                foreach ($customFields as $key => $obj) {
                    $arrayFields[] = [
                        'name' => $obj->name,
                        'field_type' => $obj->field_type->name,
                        'appraisal_criteria_id' => $obj->id,
                        'appraisal_form_id' => $entity->id,
                        'field_type_id' => $obj->field_type_id,
                        'is_mandatory' => $obj->_joinData->is_mandatory,
                        'section' => $obj->_joinData->section,
                        'id' => $obj->_joinData->id
                    ];
                }
            } elseif ($this->request->is(['post', 'put'])) {
                $requestData = $this->request->data;
                if (array_key_exists('appraisal_criterias', $requestData[$this->alias()])) {
                    foreach ($requestData[$this->alias()]['appraisal_criterias'] as $key => $obj) {
                        $arrayData = [
                            'name' => $obj['_joinData']['name'],
                            'field_type' => $obj['_joinData']['field_type'],
                            'field_type_id' => $obj['_joinData']['field_type_id'],
                            'appraisal_criteria_id' => $obj['id'],
                            'appraisal_form_id' => $obj['_joinData']['appraisal_form_id'],
                            'is_mandatory' => $obj['_joinData']['is_mandatory'],
                            'section' => $obj['_joinData']['section']
                        ];
                        if (!empty($obj['_joinData']['id'])) {
                            $arrayData['id'] = $obj['_joinData']['id'];
                        }
                        $arrayFields[] = $arrayData;
                    }
                }
                if (array_key_exists('selected_custom_field', $requestData[$this->alias()])) {
                    $fieldId = $requestData[$this->alias()]['selected_custom_field'];
                    if (!empty($fieldId)) {
                        $fieldObj = $this->AppraisalCriterias->get($fieldId, ['contain' => ['FieldTypes']]);
                        $arrayFields[] = [
                            'name' => $fieldObj->name,
                            'field_type' => $fieldObj->field_type->name,
                            'field_type_id' => $fieldObj->field_type_id,
                            'appraisal_criteria_id' => $fieldObj->id,
                            'appraisal_form_id' => $entity->id,
                            'is_mandatory' => 0,
                            'section' => $entity->section
                        ];
                    }
                }
            }

            $tableHeaders = [__('Criteria') , __('Field Type'), __('Is Mandatory'), ''];
            $tableCells = [];

            $cellCount = 0;
            $order = 0;
            $sectionName = "";
            $printSection = false;

            foreach ($arrayFields as $key => $obj) {
                $fieldPrefix = $this->alias() . '.appraisal_criterias.' . $cellCount++;
                $joinDataPrefix = $fieldPrefix . '._joinData';
                $customFieldName = $obj['name'];
                $customFieldType = $obj['field_type'];
                $customFieldTypeId = $obj['field_type_id'];
                $customFieldId = $obj['appraisal_criteria_id'];
                $customFormId = $obj['appraisal_form_id'];
                $customSection = "";
                if (!empty($obj['section'])) {
                    $customSection = $obj['section'];
                }
                if ($sectionName != $customSection) {
                    $sectionName = $customSection;
                    $printSection = true;
                }
                $cellData = "";
                $cellData .= $form->hidden($fieldPrefix.".id", ['value' => $customFieldId]);
                $cellData .= $form->hidden($joinDataPrefix.".name", ['value' => $customFieldName]);
                $cellData .= $form->hidden($joinDataPrefix.".field_type", ['value' => $customFieldType]);
                $cellData .= $form->hidden($joinDataPrefix.".field_type_id", ['value' => $customFieldTypeId]);
                $cellData .= $form->hidden($joinDataPrefix.".appraisal_form_id", ['value' => $customFormId]);
                $cellData .= $form->hidden($joinDataPrefix.".appraisal_criteria_id", ['value' => $customFieldId]);
                $cellData .= $form->hidden($joinDataPrefix.".order", ['value' => ++$order, 'class' => 'order']);
                $cellData .= $form->hidden($joinDataPrefix.".section", ['value' => $customSection, 'class' => 'section']);
                $form->unlockField($joinDataPrefix.".order");
                $form->unlockField($joinDataPrefix.".section");

                if (isset($obj['id'])) {
                    $cellData .= $form->hidden($joinDataPrefix.".id", ['value' => $obj['id']]);
                }
                if (!empty($sectionName) && ($printSection)) {
                    $rowData = [];
                    $rowData[] = '<div class="section-header">'.$sectionName.'</div>';
                    $rowData[] = ''; // Field Type
                    $rowData[] = ''; // Is Mandatory
                    $rowData[] = '<button onclick="jsTable.doRemove(this);CustomForm.updateSection();" aria-expanded="true" type="button" class="btn btn-dropdown action-toggle btn-single-action"><i class="fa fa-trash"></i>&nbsp;<span>'.__('Delete').'</span></button>';
                    $rowData[] = [$event->subject()->renderElement('OpenEmis.reorder', ['attr' => '']), ['class' => 'sorter rowlink-skip']];
                    $printSection = false;
                    $tableCells[] = $rowData;
                }
                $rowData = [];
                $rowData[] = $customFieldName.$cellData;
                $rowData[] = $customFieldType;
                $rowData[] = $form->checkbox("$joinDataPrefix.is_mandatory", ['checked' => $obj['is_mandatory'], 'class' => 'no-selection-label', 'kd-checkbox-radio' => '']);
                $rowData[] = '<button onclick="jsTable.doRemove(this); $(\'#reload\').click();" aria-expanded="true" type="button" class="btn btn-dropdown action-toggle btn-single-action"><i class="fa fa-trash"></i>&nbsp;<span>'.__('Delete').'</span></button>';
                $rowData[] = [$event->subject()->renderElement('OpenEmis.reorder', ['attr' => '']), ['class' => 'sorter rowlink-skip']];
                $tableCells[] = $rowData;

                unset($criteriaList[$obj['appraisal_criteria_id']]);
            }

            $attr['tableHeaders'] = $tableHeaders;
            $attr['tableCells'] = $tableCells;
            $attr['reorder'] = true;

            ksort($criteriaList);
            $attr['options'] = $criteriaList;
            $attr['chosenSelectInput'] = [
                'model' => $this->alias(),
                'field' => 'selected_custom_field',
                'multiple' => false,
                'options' => $criteriaList,
                'label' => 'Add Criterias'
            ];
        }

        return $event->subject()->renderElement('StaffAppraisal.form_criterias', ['attr' => $attr]);
    }

    public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        $extra['excludedModels'] = [
            $this->AppraisalCriterias->alias()
        ];
    }
}
