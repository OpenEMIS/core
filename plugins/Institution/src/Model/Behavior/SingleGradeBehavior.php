<?php
namespace Institution\Model\Behavior;

use ArrayObject;

use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\Event\Event;
use Cake\Utility\Inflector;
use Cake\I18n\Time;

use ControllerAction\Model\Traits\EventTrait;

class SingleGradeBehavior extends Behavior
{
    use EventTrait;

    public function implementedEvents()
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

        if ($request->is(['post']) && array_key_exists($model->alias(), $request->data)) {
            $modelData = $request->data[$model->alias()];
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
        $institutionId = $session->read('Institution.Institutions.id');

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

        $model->field('single_grade_field', [
            'type'      => 'element',
            'element'   => 'Institution.Classes/single_grade',
            'data'      => [    'numberOfClasses'   => $numberOfClasses,
                                // 'staffOptions'      => $model->getStaffOptions($institutionId, 'add', $selectedAcademicPeriodId,0, $institutionShiftId,$homeTeacher),
                                'staffOptions'      => $model->getStaffOptions($institutionId, 'add', $selectedAcademicPeriodId,0),
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
        $model->fields['total_female_students']['visible'] = false;   
        $model->setFieldOrder([
            'academic_period_id', 'education_grade', 'institution_shift_id', 'class_number', 'number_of_classes', 'capacity', 'single_grade_field'
        ]);
    }

    public function addBeforeSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
    {
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
                    if ($class->errors()) {
                        $error = $class->errors();
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
                    $model->log($error, 'debug');
                    /**
                     * unset all field validation except for "name" to trigger validation error in ControllerActionComponent
                     */
                    foreach ($model->fields as $value) {
                        if ($value['field'] != 'name' || $value['field'] != 'staff_id') {
                            $model->validator()->remove($value['field']);
                        }
                    }
                    unset($value);
                    $model->fields['single_grade_field']['data']['classes'] = $classes;
                    $model->request->data['MultiClasses'] = $requestData['MultiClasses'];
                    return false;
                }
            } else {
                $requestData['errorMessage'] = 'Institution.'.$model->alias().'.noGrade';
                return false;
            }
        };

        return $process;
    }

    public function addAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
    {
        $model = $this->_table;
        $errors = $entity->errors();
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
}
