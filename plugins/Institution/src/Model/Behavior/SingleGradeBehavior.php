<?php
namespace Institution\Model\Behavior;

use ArrayObject;

use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\Event\Event;
use Cake\Utility\Inflector;
use Cake\I18n\Time;
use Cake\Utility\Text;//POCOR-8538
use Cake\Log\Log;//POCOR-8538

use ControllerAction\Model\Traits\EventTrait;

class SingleGradeBehavior extends Behavior
{
    use EventTrait;

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.add.afterSave'] = ['callable' => 'addAfterSave', 'priority' => 9];
        $events['ControllerAction.Model.add.beforeSave'] = ['callable' => 'addBeforeSave', 'priority' => 9];
        // set priority to 100 so that this will be called after model's addBeforeAction
        $events['ControllerAction.Model.add.beforeAction'] = ['callable' => 'addBeforeAction', 'priority' => 100];
        return $events;
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
        $institutionShiftId = $extra['institution_shift_id'];

        $numberOfClasses = 1;

        if ($request->is(['post']) && array_key_exists($model->getAlias(), $request->getData())) {
            $modelData = $request->getData()[$model->getAlias()];
            $selectedEducationGradeId = $modelData['education_grade'];
            $numberOfClasses = $modelData['number_of_classes'];
            /**
             * PHPOE-2090, check if selected academic_period_id changes
             */
            $selectedAcademicPeriodId = $modelData['academic_period_id'];
        }

        /**
         * education_grade field setup
         * PHPOE-1867 - Changed the population of grades from InstitutionGradesTable
         */
        $gradeOptions = [];
        if (!empty($selectedAcademicPeriodId)) {
            $gradeOptions = $model->Institutions->InstitutionGrades->getGradeOptions($institutionId, $selectedAcademicPeriodId);
        }

        if (empty($gradeOptions)) {
            $gradeOptions[''] = $model->Alert->getMessage($model->aliasField('education_grade_options_empty'));
            $selectedEducationGradeId = 0;
        }

        $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
        $session = $this->_table->Session;
        // Call a method from another behavior attached to the same table
        $institutionId =  $this->_table->getBehavior('InstitutionTab')->getInstitutionID();
        // $institutionId = $session->read('Institution.Institutions.id');

        $AcademicPeriodTable = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $gradeOptions = [0 => '-- '.__('Select').' --'] + $gradeOptions;
        //echo 'ssssss';print_r($institutionShiftId);die;
        $this->_table->advancedSelectOptions($gradeOptions, $selectedEducationGradeId, [
            'message' => '{{label}} - ' . $this->_table->getMessage($this->_table->aliasField('expiredGrade')),
            'callable' => function ($id) use ($InstitutionGrades, $institutionId, $AcademicPeriodTable, $selectedAcademicPeriodId) {
                if ($id == 0) {
                    return true;
                }
                $functionQuery = $InstitutionGrades->find();
                $query = $InstitutionGrades->find()
                    ->where([
                        $InstitutionGrades->aliasField('education_grade_id') => $id,
                        $InstitutionGrades->aliasField('institution_id') => $institutionId,

                        'OR' => [
                            [$InstitutionGrades->aliasField('end_date') . ' >= ' => $functionQuery->func()->now('date')],
                            [$InstitutionGrades->aliasField('end_date'). ' IS NULL']
                            ]
                    ]);
                return $query->count();
            }
        ]);

        $model->field('education_grade', [
            'type' => 'select',
            'options' => $gradeOptions,
            'onChangeReload' => true,
            'select' => false
        ]);


        $model->field('number_of_classes', [
            'type' => 'select',
            'options' => $this->numberOfClassesOptions(),
            'onChangeReload' => true,
            'select' => false
        ]);

        $grade = [];
        if ($model->EducationGrades->exists(['id' => $selectedEducationGradeId])) {
            $grade = $model->EducationGrades->get($selectedEducationGradeId)->toArray();
        }

        $staffOptions = $model->getStaffOptions($institutionId, 'add', $selectedAcademicPeriodId);
        $secondaryStaffOptions = $staffOptions;
        $secondaryPlaceholderText = '';
        $homeTeacher = true;
        if (array_key_exists(0, $secondaryStaffOptions)) {
            $secondaryPlaceholderText = $secondaryStaffOptions[0];
            unset($secondaryStaffOptions[0]);
        }

        $unitOptions = $model->getUnitId($institutionId =null,  $selectedAcademicPeriodId=null);
        $courseOptions = $model->getCourseId($institutionId =null,  $selectedAcademicPeriodId=null);

        $unitOptions = [0 => '-- '.__('Select').' --'] + $unitOptions;//POCOR-7336
        $courseOptions = [0 => '-- '.__('Select').' --'] + $courseOptions; //POCOR-7336
        //POCOR-7680 start
        //$institutionId = $session->read('Institution.Institutions.id');
        $institutionId =  $this->_table->getBehavior('InstitutionTab')->getInstitutionID();
        $selectedAcademicPeriodId = $extra['selectedAcademicPeriodId'];
        //POCOR-7680 end

        //POCOR-7803::Start
        $configItems = TableRegistry::get('Configuration.ConfigItems');
        $configItemsData = $configItems->find()->where(['type'=>'Fields for Institutions Classes Details Page'])->toArray();
        foreach($configItemsData as $configItemsData1){
            if(($configItemsData1['code'] == 'class_ins_unit') && ($configItemsData1['value'] == 0)){
                $unitEnable = 0;
            }elseif(($configItemsData1['code'] == 'class_ins_unit') && ($configItemsData1['value'] == 1)){
                $unitEnable = 1;
            }
            if(($configItemsData1['code'] == 'class_ins_course') && ($configItemsData1['value'] == 0)){
                $courseEnable = 0;
            }elseif(($configItemsData1['code'] == 'class_ins_course') && ($configItemsData1['value'] == 1)){
                $courseEnable = 1;
            }
        }
        //POCOR-7803::End

        $LabelTable = TableRegistry::get('Labels');
        $unitname = $LabelTable->find()->where(['module_name' =>'Institutions -> Classes' , 'field_name' =>'Unit'])->first();
        if($unitname != null){
           $unit =  $unitname->name;
        }

        $CourseName = $LabelTable->find()->where(['module_name' =>'Institutions -> Classes' , 'field_name' =>'Course'])->first();
        if($CourseName != null){
           $Courses =  $CourseName->name;
        }
        $model->field('single_grade_field', [
            'type'      => 'element',
            'unitEnable'      => $unitEnable, //POCOR-7803
            'courseEnable'      => $courseEnable, //POCOR-7803
            'unitLabel'      => $unit, //POCOR-8271
            'courseLabel'      => $Courses, //POCOR-8271
            'element'   => 'Institution.Classes/single_grade',
            'data'      => [    'numberOfClasses'   => $numberOfClasses,
                                // 'staffOptions'      => $model->getStaffOptions($institutionId, 'add', $selectedAcademicPeriodId,0, $institutionShiftId,$homeTeacher),
                                'staffOptions'      => $model->getStaffOptions($institutionId, 'add', $selectedAcademicPeriodId,0),
                                'unitOptions'       => $unitOptions,
                                'courseOptions'     => $courseOptions,
                                'existedClasses'    => $model->getExistedClasses($institutionId, $selectedAcademicPeriodId, $selectedEducationGradeId),
                                'grade'             => $grade,
                                'secondaryStaffAttr' => [
                                    'options' => $secondaryStaffOptions,
                                    'fieldName' => '%d.classes_secondary_staff',
                                    'model' => 'MultiClasses',
                                    'placeholder' => $secondaryPlaceholderText,
                                ]
            ]
        ]);

        $model->fields['name']['visible'] = false;
        $model->fields['students']['visible'] = false;
        $model->fields['staff_id']['visible'] = false;
        $model->fields['staff_id']['type'] = 'hidden';
        $model->fields['classes_secondary_staff']['visible'] = false;
        $model->fields['classes_secondary_staff']['type'] = 'hidden';
        $model->fields['total_male_students']['visible'] = false;
        $model->fields['institution_unit_id']['type'] = 'hidden';
        $model->fields['institution_course_id']['visible'] = false;
        $model->fields['total_female_students']['visible'] = false;
        $model->setFieldOrder([
            'academic_period_id', 'education_grade', 'institution_shift_id', 'class_number', 'number_of_classes', 'capacity', 'single_grade_field'
        ]);
    }

    public function addBeforeSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
    {
//        echo "<pre>";
//        print_r(__FUNCTION__);
//        print_r($entity);
//        echo "</pre>";
//        echo "<pre>";
//        print_r(__FILE__);
//        print_r($requestData);
//        echo "</pre>";
        //POCOR-8538 start
        $process = function ($model, $entity) use ($requestData, $extra) {
            $commonData = $requestData['InstitutionClasses'];
            /**
             * PHPOE-2090, check if grade is empty as it is mandatory
             */
            if (!empty($commonData['education_grade'])) {
                foreach ($requestData['MultiClasses'] as $key => $row) {
                    $requestData['MultiClasses'][$key]['institution_shift_id'] = $commonData['institution_shift_id'];
                    $requestData['MultiClasses'][$key]['institution_id'] = $commonData['institution_id'];
                    $requestData['MultiClasses'][$key]['academic_period_id'] = $commonData['academic_period_id'];
                    $requestData['MultiClasses'][$key]['capacity'] = $commonData['capacity'];
                    $requestData['MultiClasses'][$key]['education_grades']['_ids'] = [$commonData['education_grade']];
                    $requestData['MultiClasses'][$key]['secondary_staff'] = $requestData['MultiClasses'][$key]['classes_secondary_staff'];
                }

                $classes = $model->newEntities($requestData['MultiClasses']);
                $error = false;
                foreach ($classes as $key => $class) {
                    if ($class->getErrors()) {
                        $error = $class->getErrors();
                        $requestData['MultiClasses'][$key]['errors'] = $error;
                    }
                }
                /**
                 * attempt to prevent memory leak
                 */
                unset($key);
                unset($class);
                if (!$error) {
                    foreach ($classes as $class) {
                        $savedEntity = $model->save($class);
                        if ($savedEntity->has('secondary_staff') && !empty($savedEntity->secondary_staff['_ids'])) {
                            $secondaryStaffIds = $savedEntity->secondary_staff['_ids'];
                            $classId = $savedEntity->id;
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
                    unset($class);
                    $requestData['errorMessage'] = false;
                    return true;
                } else {
                    $errorMessage='';
                    foreach ($error as $key => $value) {
                        $errorMessage .= Inflector::classify($key);
                    }
                    unset($value);
                    //$model->log($error, 'debug');
                    $model->log(json_encode($error), 'debug');
                    /**
                     * unset all field validation except for "name" to trigger validation error in ControllerActionComponent
                     */
                    foreach ($model->fields as $value) {
                        if ($value['field'] != 'name' || $value['field'] != 'staff_id') {
                            $model->getValidator()->remove($value['field']);
                        }
                    }
                    unset($value);
                    $model->fields['single_grade_field']['data']['classes'] = $classes;
                    //$model->request->data['MultiClasses'] = $requestData['MultiClasses'];
                    $model->request = $model->request->withData('MultiClasses', $requestData['MultiClasses']);//POCOR-8323
                    return false;
                }
            } else {
                $requestData['errorMessage'] = 'Institution.'.$model->getAlias().'.noGrade';
                return false;
            }
        };

        return $process;
    }

    public function addAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
    {
        $model = $this->_table;
        $errors = $entity->getErrors();
        $alias = $model->getAlias();
        $data = $requestData[$alias];
        $classData=$this->_table->find('all', [
            'order' => ['InstitutionClasses.id' => 'DESC']
        ])->first();
//        echo "<pre>";
//        echo __FUNCTION__;
//
//        print_r($requestData);
//        echo "</pre>";
        $cv = self::saveCustomFieldsForSingleGrade($data['custom_field_values'], $classData->id, $classData->created_user_id);
        //POCOR-8538 end
        if (isset($requestData['errorMessage'])) {
            if (!empty($requestData['errorMessage'])) {
                $model->Alert->error($requestData['errorMessage'], ['reset'=>true]);
            } else {
                $entity->clean();
                $model->Alert->success('general.add.success', ['reset'=>true]);
            }
        }
    }

    private function numberOfClassesOptions()
    {
        $total = 10;
        $options = [];
        for ($i=1; $i<=$total; $i++) {
            $options[$i] = $i;
        }

        return $options;
    }
    //POCOR-8538 start
    public static function saveCustomFieldsForSingleGrade($customFields, $classId, $createdUserId): array
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
}
