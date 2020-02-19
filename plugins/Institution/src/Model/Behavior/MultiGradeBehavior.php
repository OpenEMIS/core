<?php
namespace Institution\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\I18n\Time;

use ControllerAction\Model\Traits\EventTrait;

class MultiGradeBehavior extends Behavior
{
    use EventTrait;

    public function implementedEvents()
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
        if (array_key_exists($model->alias(), $request->data)) {
            $modelData = $request->data[$model->alias()];
            $selectedAcademicPeriodId = $modelData['academic_period_id'];
        }

        $gradeOptions = $model->Institutions->InstitutionGrades->getGradeOptions($institutionId, $selectedAcademicPeriodId, false);
        $model->field('multi_grade_field', [
            'type' => 'element',
            'data' => $gradeOptions,
            'model' => $model->alias(),
            'field' => 'multi_grade_field',
            'element' => 'Institution.Classes/multi_grade',
        ]);

        $staffId = is_null($model->request->data($model->aliasField('staff_id'))) ? [] : [$model->request->data($model->aliasField('staff_id'))];
        $secondaryStaffIds = is_null($model->request->data($model->aliasField('classes_secondary_staff'))['_ids']) ? [] : $model->request->data($model->aliasField('classes_secondary_staff'))['_ids'];

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
            'academic_period_id', 'name', 'institution_shift_id', 'staff_id', 'classes_secondary_staff', 'capacity', 'multi_grade_field'
        ]);
    }

    public function addBeforePatch(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra)
    {
        $model = $this->_table;
        $request = $this->_table->request;

        $education_grades = $request->data($model->aliasField('education_grades'));

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

        $requestData[$model->alias()]['secondary_staff'] = $requestData[$model->alias()]['classes_secondary_staff'];

        if (!$hasSelection) {
            /*
             * set institution_id to empty to trigger validation error in ControllerActionComponent
             */
            $requestData[$model->alias()]['education_grades'] = '';
            $errorMessage = 'Institution.'.$model->aliasField('noGrade');
            $requestData['errorMessage'] = $errorMessage;
        }
        $model->fields['multi_grade_field']['selected'] = $selected;
    }

    public function addAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
    {
        $model = $this->_table;
        $errors = $entity->errors();
        if (!empty($errors)) {
            if (isset($requestData['errorMessage']) && !empty($requestData['errorMessage'])) {
                $model->Alert->error($requestData['errorMessage'], ['reset'=>true]);
            }
        }
    }

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
