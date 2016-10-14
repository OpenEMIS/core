<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class SinglePromotionTable extends ControllerActionTable
{
    public function initialize(array $config) {
        $this->table('institution_students');
        parent::initialize($config);
        $this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->addBehavior('OpenEmis.Section');
    }

    public function validationDefault(Validator $validator) {
        $validator = parent::validationDefault($validator);

        return $validator
            ->notBlank('academic_period_id')
            ->allowEmpty('education_grade_id');
    }

    // public function implementedEvents() {
    //     $events = parent::implementedEvents();
    //     $events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
    //     $events['Model.Navigation.breadcrumb'] = 'onGetBreadcrumb';
    //     return $events;
    // }

    // public function onGetBreadcrumb(Event $event, Request $request, Component $Navigation, $persona=false) {
    //     $url = ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Students'];
    //     $Navigation->substituteCrumb('Promotion', 'Students', $url);
    //     $Navigation->addCrumb('Promotion');
    // }

    public function addBeforeAction(Event $event, arrayObject $extra)
    {
        if (isset($extra['toolbarButtons']['back'])) {
            $Students = TableRegistry::get('Institution.StudentUser');
            $extra['toolbarButtons']['back']['url']['action'] = $Students->alias();
            $extra['toolbarButtons']['back']['url'][0] = 'view';
            $extra['toolbarButtons']['back']['url'][1] = $this->Session->read('Student.Students.id');
        }

        $this->institutionId = $this->Session->read('Institution.Institutions.id');
        $this->id = $this->Session->read($this->registryAlias().'.id');
        $this->statuses = $this->StudentStatuses->findCodeList();

        $Students = TableRegistry::get('Institution.Students');
        $this->studentData = $Students->get($this->id);

        $this->request->data[$this->alias()]['institution_id'] = $this->institutionId;
        $this->request->data[$this->alias()]['student_id'] = $this->studentData->student_id;
    }

    private function addSections()
    {
        $this->field('existing_information_header', ['type' => 'section', 'title' => __('Promote From')]);
        $this->field('new_information_header', ['type' => 'section', 'title' => __('Promote To')]);
    }

    public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->fields = [];
        $this->addSections();
        $this->field('student_id');
        $this->field('from_academic_period_id');
        $this->field('from_education_grade_id');
        $this->field('academic_period_id');
        $this->field('education_grade_id');
        $this->field('student_status_id', ['entity' => $entity]);

        $this->setFieldOrder([
            'student_id',
            'existing_information_header', 'from_academic_period_id', 'from_education_grade_id',
            'new_information_header', 'academic_period_id', 'student_status_id', 'education_grade_id']);
    }

    public function onUpdateFieldStudentId(Event $event, array $attr, $action, Request $request)
    {
        $studentId = $this->studentData->student_id;

        $attr['type'] = 'readonly';
        $attr['attr']['value'] = $this->Users->get($studentId)->name_with_id;
        return $attr;
    }

    public function onUpdateFieldFromAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        $academicPeriodId = $this->studentData->academic_period_id;

        $attr['type'] = 'readonly';
        $attr['attr']['value'] = $this->AcademicPeriods->get($academicPeriodId)->name;
        return $attr;
    }

    public function onUpdateFieldFromEducationGradeId(Event $event, array $attr, $action, Request $request)
    {
        $educationGradeId = $this->studentData->education_grade_id;

        $attr['type'] = 'readonly';
        $attr['attr']['value'] = $this->EducationGrades->get($educationGradeId)->programme_grade_name;
        return $attr;
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        $currentPeriodId = $this->studentData->academic_period_id;
        $currentPeriod = $this->AcademicPeriods->get($currentPeriodId);

        $condition = [$this->AcademicPeriods->aliasField('order').' <= ' => $currentPeriod->order];
        $periodOptions = $this->AcademicPeriods->getYearList(['conditions' => $condition, 'isEditable' => true]);

        $attr['type'] = 'select';
        $attr['options'] = $periodOptions;
        return $attr;
    }

    public function onUpdateFieldStudentStatusId(Event $event, array $attr, $action, Request $request) {
        $studentStatusesList = $this->StudentStatuses->find('list')->toArray();
        $statusCodes = $this->StudentStatuses->findCodeList();
        $statusOptions = [];

        $educationGradeId = $this->studentData->education_grade_id;
        $nextGrades = $this->EducationGrades->getNextAvailableEducationGrades($educationGradeId, false);

        // If there is no more next grade in the same education programme then the student may be graduated
        // if (count($nextGrades) == 0) {
        //     $statusOptions[$statusCodes['GRADUATED']] = __($studentStatusesList[$statusCodes['GRADUATED']]);
        // } else {
        //     $statusOptions[$statusCodes['PROMOTED']] = __($studentStatusesList[$statusCodes['PROMOTED']]);
        // }

        if (count($nextGrades) != 0) {
            $statusOptions[$statusCodes['PROMOTED']] = __($studentStatusesList[$statusCodes['PROMOTED']]);
        }

        $statusOptions[$statusCodes['REPEATED']] = __($studentStatusesList[$statusCodes['REPEATED']]);

        $attr['options'] = $statusOptions;
        $attr['onChangeReload'] = true;
        return $attr;
    }

    public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, Request $request)
    {
        $studentStatusId = (!empty($request->data[$this->alias()]['student_status_id']))? $request->data[$this->alias()]['student_status_id']: '';
        $currentGradeId = $this->studentData->education_grade_id;

        if (!empty($studentStatusId)) {
            $statuses = $this->statuses;

            if (!in_array($studentStatusId, [$statuses['REPEATED']])) {

                $institutionId = $this->institutionId;

                // list of grades available to promote to
                // $listOfGrades = $this->EducationGrades->getNextAvailableEducationGrades($currentGradeId);

                $programmeId = $this->EducationGrades->get($currentGradeId)->education_programme_id;

                $listOfGrades = $this->EducationGrades->find('list', [
                    'keyField' => 'id',
                    'valueField' => 'programme_grade_name'
                ])
                ->where([
                    $this->EducationGrades->aliasField('education_programme_id') => $programmeId,
                    // $this->aliasField('order').' > ' => $order
                ])
                ->toArray();

                // list of grades available in the institution
                $today = date('Y-m-d');
                $InstitutionGrades = $this->Institutions->InstitutionGrades;
                $listOfInstitutionGrades = $InstitutionGrades
                    ->find('list', [
                        'keyField' => 'education_grade_id',
                        'valueField' => 'education_grade.programme_grade_name'])
                    ->contain(['EducationGrades.EducationProgrammes'])
                    ->where([
                        $InstitutionGrades->aliasField('institution_id') => $institutionId,
                        'OR' => [
                            [
                                $InstitutionGrades->aliasField('end_date IS NULL'),
                                $InstitutionGrades->aliasField('start_date <= ') => $today
                            ],
                            [
                                $InstitutionGrades->aliasField('end_date IS NOT NULL'),
                                $InstitutionGrades->aliasField('start_date <= ') => $today,
                                $InstitutionGrades->aliasField('end_date >= ') => $today
                            ]
                        ]
                    ])
                    ->order(['EducationProgrammes.order', 'EducationGrades.order'])
                    ->toArray();

                // Only display the options that are available in the institution and also linked to the current programme
                $options = array_intersect_key($listOfInstitutionGrades, $listOfGrades);

                if (count($options) == 0) {
                    $attr['select'] = false;
                    $options = [0 => $this->getMessage($this->aliasField('noAvailableGrades'))];
                }
                $attr['type'] = 'select';
                $attr['options'] = $options;

            } else {
                $gradeData = $this->EducationGrades->get($currentGradeId);
                $gradeName = (!empty($gradeData))? $gradeData->programme_grade_name: '';

                $attr['type'] = 'readonly';
                $attr['attr']['value'] = $gradeName;
            }

        }
        else {
            $attr['type'] = 'readonly';
            $attr['attr']['value'] = '';
        }

        return $attr;
    }

    public function addBeforeSave(Event $event, $entity, $requestData, $extra)
    {
        $process = function ($model, $entity) use ($requestData, $extra) {
            $Students = TableRegistry::get('Institution.StudentUser');
            $url['action'] = $Students->alias();
            $url[0] = 'view';
            $url[1] = $this->Session->read('Student.Students.id');

            $studentData = $this->studentData;
            $studentStatuses = $this->statuses;
            $statusToUpdate = $requestData[$this->alias()]['student_status_id'];
            $selectedAcademicPeriodId = $requestData[$this->alias()]['academic_period_id'];
            $currentAcademicPeriodId = $this->AcademicPeriods->getCurrent();
            $today = date('Y-m-d');
            $yesterday = date('Y-m-d', strtotime('yesterday'));

            // insert new record for student
            if ($statusToUpdate == $studentStatuses['REPEATED']) {
                $entity->education_grade_id = $studentData->education_grade_id;
            }

            $entity->student_status_id = $studentStatuses['CURRENT'];

            // if student is promoted/demoted in the middle of the academic period
            if ($selectedAcademicPeriodId == $currentAcademicPeriodId) {
                $currentPeriod = $this->AcademicPeriods->get($currentAcademicPeriodId);
                $entity->start_date = $today;
                $entity->start_year = $today->format('Y');
                $entity->end_date = $nextPeriod->end_date->format('Y-m-d');
                $entity->end_year = $nextPeriod->end_date->format('Y');

            } else {
                $nextPeriod = $this->AcademicPeriods->get($selectedAcademicPeriodId);
                $entity->start_date = $nextPeriod->start_date->format('Y-m-d');
                $entity->start_year = $nextPeriod->start_date->format('Y');
                $entity->end_date = $nextPeriod->end_date->format('Y-m-d');
                $entity->end_year = $nextPeriod->end_date->format('Y');
            }
            // End

            // Update old record
            $existingStudentEntity = $this->find()
                ->where([
                    $this->aliasField('institution_id') => $this->institutionId,
                    $this->aliasField('student_id') => $studentData->student_id,
                    $this->aliasField('academic_period_id') => $studentData->academic_period_id,
                    $this->aliasField('education_grade_id') => $studentData->education_grade_id,
                    $this->aliasField('student_status_id') => $studentStatuses['CURRENT']
                ])
                ->first();

            if ($selectedAcademicPeriodId == $currentAcademicPeriodId) {

            }

            $existingStudentEntity->student_status_id = $statusToUpdate;
            // End

            if ($statusToUpdate == $studentStatuses['PROMOTED']) {
                $successMessage = $this->aliasField('success');
            // } else if ($statusToUpdate == $studentStatuses['GRADUATED']) {
            //     $successMessage = $this->aliasField('successGraduated');
            } else {
                $successMessage = $this->aliasField('successOthers');
            }

            if ($this->save($existingStudentEntity)) {
                if ($this->save($entity)) {
                    $this->Alert->success($successMessage, ['reset' => true]);
                } else {
                    $this->log($entity->errors(), 'debug');
                }
            } else {
                $message = 'failed to update student status';
                $this->Alert->error($this->aliasField('savingPromotionError'), ['reset' => true]);
                $this->log($message, 'debug');
            }

            return $this->controller->redirect($url);
        };

        return $process;
    }
}