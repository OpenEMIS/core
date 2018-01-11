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

        $staffId = is_null($model->request->data($model->aliasField('staff_id'))) ? 0 : $model->request->data($model->aliasField('staff_id'));
        $secondaryStaffId = is_null($model->request->data($model->aliasField('secondary_staff_id'))) ? 0 : $model->request->data($model->aliasField('secondary_staff_id'));
        $model->fields['students']['visible'] = false;
        $model->fields['staff_id']['options'] = $model->getStaffOptions($institutionId, 'add', $selectedAcademicPeriodId, $secondaryStaffId);
        $model->fields['staff_id']['onChangeReload'] = true;
        $model->fields['staff_id']['select'] = false;

        $model->fields['secondary_staff_id']['options'] = $model->getStaffOptions($institutionId, 'add', $selectedAcademicPeriodId, $staffId);
        $model->fields['secondary_staff_id']['onChangeReload'] = true;
        $model->fields['secondary_staff_id']['select'] = false;

        $model->fields['total_male_students']['visible'] = false;
        $model->fields['total_female_students']['visible'] = false;  
        $model->setFieldOrder([
            'academic_period_id', 'name', 'institution_shift_id', 'staff_id', 'secondary_staff_id', 'multi_grade_field'
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
}
