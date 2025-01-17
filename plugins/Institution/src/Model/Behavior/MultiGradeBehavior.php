<?php
namespace Institution\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\I18n\Time;

use ControllerAction\Model\Traits\EventTrait;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;
use Cake\Log\Log;

class MultiGradeBehavior extends Behavior
{
    use EventTrait;

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.add.afterSave'] = ['callable' => 'addAfterSave', 'priority' => 9];
        $events['ControllerAction.Model.add.beforePatch'] = ['callable' => 'addBeforePatch', 'priority' => 9];
        // set priority to 100 so that this will be called after model's addBeforeAction
        $events['ControllerAction.Model.add.beforeAction'] = ['callable' => 'addBeforeAction', 'priority' => 100];
        return $events;
    }

    public function buildValidator(Event $event, Validator $validator, $name)
    {
        $validator->notEmpty('education_grades');
    }

/******************************************************************************************************************
**
** add action methods
**
******************************************************************************************************************/
    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        /**
         * add form setup
         */
        $model = $this->_table;
        $request = $model->request;
        $institutionId = $extra['institution_id'];
        $selectedAcademicPeriodId = $extra['selectedAcademicPeriodId'];
        $selectedEducationGradeId = $extra['selectedEducationGradeId'];

        /**
         * PHPOE-2090, check if selected academic_period_id changes
         */
        if (array_key_exists($model->getAlias(), $request->getData())) {
            $modelData = $request->getData($model->getAlias());
            $selectedAcademicPeriodId = $modelData['academic_period_id'];
        }

        $gradeOptions = $model->Institutions->InstitutionGrades->getGradeOptions($institutionId, $selectedAcademicPeriodId, false);
        $model->field('multi_grade_field', [
            'type' => 'element',
            'data' => $gradeOptions,
            'model' => $model->getAlias(),
            'field' => 'multi_grade_field',
            'element' => 'Institution.Classes/multi_grade',
        ]);

        $staffId = is_null($model->request->getData($model->aliasField('staff_id'))) ? [] : [$model->request->getData($model->aliasField('staff_id'))];
        $secondaryStaffIds = is_null($model->request->getData($model->aliasField('classes_secondary_staff'))['_ids']) ? [] : $model->request->getData($model->aliasField('classes_secondary_staff'))['_ids'];

        $staffOptions = $model->getStaffOptions($institutionId, 'add', $selectedAcademicPeriodId, $secondaryStaffIds);
        $secondaryStaffOptions = $model->getStaffOptions($institutionId, 'add', $selectedAcademicPeriodId, $staffId);
        $secondaryPlaceholderText = '';
        if (array_key_exists(0, $secondaryStaffOptions)) {
            $secondaryPlaceholderText = $secondaryStaffOptions[0];
            unset($secondaryStaffOptions[0]);
        }

        $model->fields['students']['visible'] = false;

        $model->fields['staff_id']['options'] = $staffOptions;
        $model->fields['staff_id']['onChangeReload'] = true;
        $model->fields['staff_id']['select'] = false;

        $model->fields['classes_secondary_staff']['options'] = $secondaryStaffOptions;
        $model->fields['classes_secondary_staff']['onChangeReload'] = true;
        $model->fields['classes_secondary_staff']['select'] = false;
        $model->fields['classes_secondary_staff']['type'] = 'chosenSelect';
        $model->fields['classes_secondary_staff']['placeholder'] = $secondaryPlaceholderText;

        $model->fields['total_male_students']['visible'] = false;
        $model->fields['total_female_students']['visible'] = false;
        $model->setFieldOrder([
            'academic_period_id', 'name', 'institution_shift_id', 'institution_unit_id','institution_course_id','staff_id', 'classes_secondary_staff', 'capacity', 'multi_grade_field'
        ]);
    }

    public function addBeforePatch(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra)
    {
        $model = $this->_table;
        $request = $this->_table->request;

        $education_grades = $request->getData($model->aliasField('education_grades'));

        $selected = [];
        $hasSelection = false; //to handle submitted value which is different when converted to form helper.
        if (isset($education_grades) && count($education_grades)>0) {
            foreach ($education_grades['_ids'] as $key => $row) {
                if ($row) { //if has value, it means selected.
                    $selected[] = $row;
                    $hasSelection = true;
                }
            }
        }

        $requestData[$model->getAlias()]['secondary_staff'] = $requestData[$model->getAlias()]['classes_secondary_staff'];

        if (!$hasSelection) {
            /*
             * set institution_id to empty to trigger validation error in ControllerActionComponent
             */
            $requestData[$model->getAlias()]['education_grades'] = '';
            $errorMessage = 'Institution.'.$model->aliasField('noGrade');
            $requestData['errorMessage'] = $errorMessage;
        }
        $model->fields['multi_grade_field']['selected'] = $selected;
    }

    public function addAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
    {
        $model = $this->_table;
        $errors = $entity->getErrors();
        if (!empty($errors)) {
            if (isset($requestData['errorMessage']) && !empty($requestData['errorMessage'])) {
                $model->Alert->error($requestData['errorMessage'], ['reset'=>true]);
            }
        }
        $alias = $model->getAlias();
        $data = $requestData[$alias];
        $classData=$this->_table->find('all', [
            'order' => ['InstitutionClasses.id' => 'DESC']
        ])->first();
        $cv = self::saveCustomFieldsForMultiGrade($data['custom_field_values'], $classData->id, $classData->created_user_id);

    }

    public static function saveCustomFieldsForMultiGrade($customFields, $classId, $createdUserId): array
    {
        $cv = [];

        if (!empty($customFields)) {
            $customFieldValuesTable =
                TableRegistry::getTableLocator()->get('InstitutionCustomField.InstitutionClassesCustomFieldValues');

            // Delete existing custom fields for this class
            $customFieldValuesTable->deleteAll(
                [$customFieldValuesTable->aliasField('institution_class_id') => $classId]
            );

            $relevantFields = [
                "text" => "text_value",
                "number" => "number_value",
                "dropdown" => "number_value",
                "checkbox" => "number_value",
                "decimal" => "decimal_value",
                "textarea" => "textarea_value",
                "time" => "time_value",
                "date" => "date_value",
                "file" => "file"
            ];

            // Iterate over each custom field
            foreach ($customFields as $field) {
                $key = strtolower($field['field_type']);

                // Special handling for CHECKBOX fields
                if ($key === 'checkbox' && !empty($field['number_value']) && is_array($field['number_value'])) {
                    foreach ($field['number_value'] as $optionId => $isChecked) {
                        if ($isChecked) {  // Save only selected (checked) options
                            $fieldData = [
                                'id' => Text::uuid(),
                                'institution_class_id' => $classId,
                                'created_user_id' => $createdUserId,
                                'created' => date('Y-m-d H:i:s'),
                                'institution_custom_field_id' => $field['institution_custom_field_id'],
                                'number_value' => $optionId  // Store each selected option as a separate entry
                            ];

                            $fieldEntity = $customFieldValuesTable->newEntity($fieldData);
                            try {
                                $cv[] = $customFieldValuesTable->saveOrFail($fieldEntity);
                            } catch (\Exception $e) {
                                Log::error('Error saving checkbox field: ' . $e->getMessage());
                            }
                        }
                    }
                } else {
                    // General handling for other field types (TEXT, NUMBER, DROPDOWN, etc.)
                    $fieldData = [
                        'id' => Text::uuid(),
                        'institution_class_id' => $classId,
                        'created_user_id' => $createdUserId,
                        'created' => date('Y-m-d H:i:s'),
                        'institution_custom_field_id' => $field['institution_custom_field_id']
                    ];

                    $hasValue = false;

                    if (array_key_exists($key, $relevantFields)) {
                        $fieldname = $relevantFields[$key];
                        $value = $field[$fieldname] ?? null;

                        if (!empty($value)) {
                            $fieldData[$fieldname] = $value;
                            $hasValue = true;
                        }
                    }

                    if ($hasValue) {
                        $fieldEntity = $customFieldValuesTable->newEntity($fieldData);
                        try {
                            $cv[] = $customFieldValuesTable->saveOrFail($fieldEntity);
                        } catch (\Exception $e) {
                            Log::error('Error saving custom field: ' . $e->getMessage());
                        }
                    }
                }
            }
        }

        return $cv;
    }
    //POCOR-8538 end

    public function afterSaveCommit(Event $event, Entity $entity)
    {
        if ($entity->has('secondary_staff') && !empty($entity->secondary_staff['_ids'])) {
            $secondaryStaffIds = $entity->secondary_staff['_ids'];
            $classId = $entity->id;
            $ClassesSecondaryStaff = $this->_table->ClassesSecondaryStaff;
            $secondaryStaffData = [];

            if (!empty($secondaryStaffIds)) {
                foreach ($secondaryStaffIds as $secondaryStaffId) {
                    $secondaryStaffData[] = [
                        'secondary_staff_id' => $secondaryStaffId,
                        'institution_class_id' => $classId
                    ];
                }

                $secondaryStaffEntities = $ClassesSecondaryStaff->newEntities($secondaryStaffData);
                $ClassesSecondaryStaff->saveMany($secondaryStaffEntities);
            }
        }

    }
}
