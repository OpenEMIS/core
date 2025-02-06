<?php
namespace StaffAppraisal\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;
use Cake\Validation\Validator;

class AppraisalFormsTable extends ControllerActionTable
{
    // Added
    const FIELD_TYPE_SCORE = "SCORE";

    public function initialize(array $config): void
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
        $this->hasMany('StaffAppraisals', ['className' => 'Institution.StaffAppraisals', 'foreignKey' => 'appraisal_form_id', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->hasMany('AppraisalFormsCriteriasScores', ['className' => 'StaffAppraisal.AppraisalFormsCriteriasScores', 'foreignKey' => 'appraisal_form_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        if ($this->behaviors()->has('Reorder')) {
            $reorderBehavior = $this->behaviors()->get('Reorder');
            $reorderBehavior->setConfig('filter', 'parent_id');
        }
        $this->setDeleteStrategy('restrict');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);

        $validator
            ->notEmptyString('code')
            ->notEmptyString('name')
            ->add('appraisal_criterias', 'checkAppraisalFormSection', [
                'rule' => function ($value, $context) {
                    $hasSection = false;
                    $missingSections = [];

                    foreach ($value as $key => $criteria) {
                        $joinData = $criteria['_joinData'] ?? [];
                        $sectionExists = !empty($joinData['section']);

                        if (!$sectionExists) {
                            $missingSections[] = $key;
                        }

                        if (!$hasSection && $sectionExists) {
                            $hasSection = true;
                        }

                        // Inconsistent section assignment check
                        if (($hasSection && !$sectionExists) || (!$hasSection && $sectionExists)) {
                            return false;
                        }
                    }

                    // If needed, add errors to the context for use elsewhere
                    if (!empty($missingSections)) {
                        $context['errors']['appraisal_criterias_section_error'] = $missingSections;
                    }

                    return true;
                },
                'message' => 'Inconsistent section assignment in appraisal criterias.',
            ]);

        return $validator;
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
            $form = $event->getSubject()->Form;
            $form->unlockField($this->getAlias() . '.appraisal_criterias');
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
                $requestData = $this->request->getData();
                if (array_key_exists('appraisal_criterias', $requestData[$this->getAlias()])) {
                    foreach ($requestData[$this->getAlias()]['appraisal_criterias'] as $key => $obj) {
                        if (array_key_exists('appraisal_criterias_section_error', $requestData[$this->getAlias()]) && array_key_exists($key, $requestData[$this->getAlias()]['appraisal_criterias_section_error'])) {

                            $message = 'Criteria have to be in a section';
                            $tooltipMessage = '&nbsp&nbsp;<i class="fa fa-exclamation-circle fa-lg fa-right table-tooltip icon-red" data-placement="right" data-toggle="tooltip" data-animation="false" data-container="body" title="" data-html="true" data-original-title="' . $message . '"></i>';

                            $arrayData = [
                            'name' => $obj['_joinData']['name'],
                            'field_type' => $obj['_joinData']['field_type'],
                            'field_type_id' => $obj['_joinData']['field_type_id'],
                            'appraisal_criteria_id' => $obj['id'],
                            'appraisal_form_id' => $obj['_joinData']['appraisal_form_id'],
                            'is_mandatory' => $obj['_joinData']['is_mandatory'],
                            'section' => $obj['_joinData']['section'],
                            'tooltip' => $tooltipMessage
                            ];
                            if (!empty($obj['_joinData']['id'])) {
                                $arrayData['id'] = $obj['_joinData']['id'];
                            }
                        }else {
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
                        }

                        $arrayFields[] = $arrayData;
                    }
                }
                if (array_key_exists('selected_custom_field', $requestData[$this->getAlias()])) {
                    $fieldId = $requestData[$this->getAlias()]['selected_custom_field'];
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
                $fieldPrefix = $this->getAlias() . '.appraisal_criterias.' . $cellCount++;
                $joinDataPrefix = $fieldPrefix . '._joinData';
                $customFieldName = $obj['name'];
                $customFieldType = $obj['field_type'];
                $customFieldTypeId = $obj['field_type_id'];
                $customFieldId = $obj['appraisal_criteria_id'];
                $customFormId = $obj['appraisal_form_id'];
                $customTooltip = "";
                if (isset($obj['tooltip'])) {
                    $customTooltip = $obj['tooltip'];
                }
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
                    $rowData[] = [$event->getSubject()->renderElement('OpenEmis.reorder', ['attr' => '']), ['class' => 'sorter rowlink-skip']];
                    $printSection = false;
                    $tableCells[] = $rowData;
                }
                $rowData = [];
                $rowData[] = $customFieldName.$cellData.$customTooltip;
                $rowData[] = $customFieldType;

                if(isset($obj['field_type']) && !is_null($obj['field_type']) && strtoupper($obj['field_type']) != self::FIELD_TYPE_SCORE) {
                    $rowData[] = $form->checkbox("$joinDataPrefix.is_mandatory", ['checked' => $obj['is_mandatory'], 'class' => 'no-selection-label', 'kd-checkbox-radio' => '']);
                }else {
                    $rowData[] = $form->hidden("$joinDataPrefix.is_mandatory", ['value' => 0]);
                }

                $rowData[] = '<button onclick="jsTable.doRemove(this); $(\'#reload\').click();" aria-expanded="true" type="button" class="btn btn-dropdown action-toggle btn-single-action"><i class="fa fa-trash"></i>&nbsp;<span>'.__('Delete').'</span></button>';
                $rowData[] = [$event->getSubject()->renderElement('OpenEmis.reorder', ['attr' => '']), ['class' => 'sorter rowlink-skip']];
                $tableCells[] = $rowData;

                unset($criteriaList[$obj['appraisal_criteria_id']]);
            }

            $attr['tableHeaders'] = $tableHeaders;
            $attr['tableCells'] = $tableCells;
            $attr['reorder'] = true;

            ksort($criteriaList);
            $attr['options'] = $criteriaList;
            $attr['chosenSelectInput'] = [
                'model' => $this->getAlias(),
                'field' => 'selected_custom_field',
                'multiple' => false,
                'options' => $criteriaList,
                'label' => 'Add Criterias'
            ];
        }

        return $event->getSubject()->renderElement('StaffAppraisal.form_criterias', ['attr' => $attr]);
    }

    public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        $extra['excludedModels'] = [
            $this->AppraisalCriterias->getAlias()
        ];
    }

    public function addAfterSave(Event $event, Entity $entity, ArrayObject $requestData)
    {
        $appraisalScore = $this->AppraisalCriterias->AppraisalScores;
        $appraisalScore->dispatchEvent('Model.Appraisal.add.afterSave', [$entity, $requestData, $this->getAlias()], $appraisalScore);
    }

    /*public function editBeforeSave(Event $event, $entity, $requestData, $extra)
    {
        echo "<pre>"; print_r($requestData); die;
        $appraisalScore = $this->AppraisalCriterias->AppraisalScores;
        $appraisalScore->dispatchEvent('Model.Appraisal.edit.beforeSave', [$entity, $requestData, $this->getAlias()], $appraisalScore);
    }*/

    /**
     * POCOR-8848
     * This method is triggered before saving the Form.
     * It processes the new sections added in the request and ensures they are properly added 
     * to the existing appraisal criterias while avoiding duplicates.
     * @param array $requestData The request data containing appraisal form details
     * @param array $extra Additional data passed to the event
     */
    public function editBeforeSave(Event $event, $entity, $requestData, $extra)
    {
        // Extract appraisal criterias and new sections data
        $appraisalCriterias = $requestData['AppraisalForms']['appraisal_criterias'] ?? [];
        $newSections = $requestData['AppraisalFormssectionTxt'] ?? null;
        if (!is_array($newSections)) {
            $newSections = [$newSections];
        }
        $existingSections = [];
        foreach ($appraisalCriterias as $criteria) {
            $joinData = $criteria['_joinData'] ?? [];
            if (!empty($joinData['section'])) {
                $existingSections[] = $joinData['section'];
            }
        }
        foreach ($newSections as $newSection) {
            if ($newSection && !in_array($newSection, $existingSections, true)) {
                $newSectionData = [
                    'id' => null, 
                    '_joinData' => [
                        'name' => $newSection,
                        'field_type' => null,
                        'field_type_id' => null,
                        'appraisal_form_id' => $entity->id,
                        'appraisal_criteria_id' => null,
                        'order' => count($appraisalCriterias) + 1, 
                        'section' => $newSection,
                        'is_mandatory' => 0,
                    ],
                ];
                $appraisalCriterias[] = $newSectionData;
            }
        }
        usort($appraisalCriterias, function ($a, $b) {
            $orderA = $a['_joinData']['order'] ?? 0;
            $orderB = $b['_joinData']['order'] ?? 0;
            return $orderA <=> $orderB;
        });
        $requestData['AppraisalForms']['appraisal_criterias'] = $appraisalCriterias;
        $appraisalScore = $this->AppraisalCriterias->AppraisalScores;
        $appraisalScore->dispatchEvent('Model.Appraisal.edit.beforeSave', [$entity, $requestData, $this->getAlias()], $appraisalScore);
    }

    // Start POCOR-5188
    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $is_manual_exist = $this->getManualUrl('Administration','Forms','Staff Appraisals');
        if(!empty($is_manual_exist)){
            $btnAttr = [
                'class' => 'btn btn-xs btn-default icon-big',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'target'=>'_blank'
            ];

            $helpBtn['url'] = $is_manual_exist['url'];
            $helpBtn['type'] = 'button';
            $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
            $helpBtn['attr'] = $btnAttr;
            $helpBtn['attr']['title'] = __('Help');
            $extra['toolbarButtons']['help'] = $helpBtn;
        }
    }// End POCOR-5188

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'modified':
                return __('Modified');
            case 'modified_user_id':
                return __('Modified By');
            case 'created':
                return __('Created');
            case 'created_user_id':
                return __('Created By');
            case 'visible':
                return __('Visible');
            case 'name':
                return __('Name');
            case 'code':
                return __('Code');
            case 'national_code':
                return __('National Code');
            case 'editable':
                return __('Editable');
            case 'default':
                return __('Default');
            default:
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
    
    //POCOR-8620 -- Start
    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function beforeDelete(Event $event, Entity $entity)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }
    //POCOR-8620 -- End
}
