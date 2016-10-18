<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\I18n\Time;
use App\Model\Table\ControllerActionTable;

class IndividualPromotionTable extends ControllerActionTable
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

        // $Navigation->substituteCrumb('Transfers', 'TransferRequests', ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'IndividualPromotion']);
        // $Navigation->addCrumb(ucfirst($this->ControllerAction->action()));
    // }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        // $validator
        //     ->notEmpty('student_status_id')
        //     ->notEmpty('academic_period_id')
        //     ->notEmpty('education_grade_id');

        return $validator;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $Students = TableRegistry::get('Institution.StudentUser');
        $url['action'] = $Students->alias();
        $url[0] = 'view';
        $url[1] = $this->Session->read('Student.Students.id');
        $this->redirectUrl = $url;
    }

    public function indexBeforeAction(Event $event, arrayObject $extra)
    {
        return $this->controller->redirect($this->redirectUrl);
    }

    public function viewBeforeAction(Event $event, arrayObject $extra)
    {
        return $this->controller->redirect($this->redirectUrl);
    }

    public function addBeforeAction(Event $event, arrayObject $extra)
    {
        if (isset($extra['toolbarButtons']['back'])) {
            $extra['toolbarButtons']['back']['url'] = $this->redirectUrl;
        }

        $this->institutionId = $this->Session->read('Institution.Institutions.id');
        $this->id = $this->Session->read($this->registryAlias().'.id');
        $this->statuses = $this->StudentStatuses->findCodeList();

        $Students = TableRegistry::get('Institution.Students');
        $this->studentData = $Students->get($this->id);

        $this->request->data[$this->alias()]['institution_id'] = $this->institutionId;
        $this->request->data[$this->alias()]['student_id'] = $this->studentData->student_id;

        $this->fields = [];
        $this->addSections();
        $this->field('student_id');
        $this->field('from_academic_period_id');
        $this->field('from_education_grade_id');
        $this->field('academic_period_id');
        $this->field('education_grade_id');
        $this->field('student_status_id');
        $this->field('effective_date');

        $this->setFieldOrder([
            'student_id',
            'existing_information_header', 'from_academic_period_id', 'from_education_grade_id',
            'new_information_header', 'student_status_id', 'academic_period_id', 'education_grade_id', 'effective_date']);
    }

    private function addSections()
    {
        $this->field('existing_information_header', ['type' => 'section', 'title' => __('Promote From')]);
        $this->field('new_information_header', ['type' => 'section', 'title' => __('Promote To')]);
    }

    public function onGetFormButtons(Event $event, ArrayObject $buttons)
    {
        $buttons[1]['url'] = $this->redirectUrl;
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

    public function onUpdateFieldStudentStatusId(Event $event, array $attr, $action, Request $request) {
        $studentStatusesList = $this->StudentStatuses->find('list')->toArray();
        $statusCodes = $this->StudentStatuses->findCodeList();
        $statusOptions = [];

        $educationGradeId = $this->studentData->education_grade_id;
        $nextGrades = $this->EducationGrades->getNextAvailableEducationGrades($educationGradeId, false);

        if (count($nextGrades) != 0) {
            $statusOptions[$statusCodes['PROMOTED']] = __($studentStatusesList[$statusCodes['PROMOTED']]);
        }

        $statusOptions[$statusCodes['REPEATED']] = __($studentStatusesList[$statusCodes['REPEATED']]);

        $attr['options'] = $statusOptions;
        $attr['onChangeReload'] = true;
        return $attr;
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        $fromPeriodId = $this->studentData->academic_period_id;
        $fromPeriod = $this->AcademicPeriods->get($fromPeriodId);

        // only current and later academic periods will be shown
        $condition = [$this->AcademicPeriods->aliasField('order').' <= ' => $fromPeriod->order];
        $periodOptions = $this->AcademicPeriods->getYearList(['conditions' => $condition, 'isEditable' => true]);

        $attr['type'] = 'select';
        $attr['options'] = $periodOptions;
        $attr['onChangeReload'] = true;
        return $attr;
    }

    public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, Request $request)
    {
        $studentStatusId = (!empty($request->data[$this->alias()]['student_status_id']))? $request->data[$this->alias()]['student_status_id']: '';
        $toAcademicPeriodId = (!empty($request->data[$this->alias()]['academic_period_id']))? $request->data[$this->alias()]['academic_period_id']: '';

        if (!empty($studentStatusId) && !empty($toAcademicPeriodId)) {
            $InstitutionGrades = $this->Institutions->InstitutionGrades;
            $fromGradeId = $this->studentData->education_grade_id;
            $today = date('Y-m-d');

            // list of grades available in the institution
            $listOfInstitutionGrades = $InstitutionGrades
                ->find('list', [
                    'keyField' => 'education_grade_id',
                    'valueField' => 'education_grade.programme_grade_name'])
                ->contain(['EducationGrades.EducationProgrammes'])
                ->where([
                    $InstitutionGrades->aliasField('institution_id') => $this->institutionId,
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

            // PROMOTED status
            if ($studentStatusId == $this->statuses['PROMOTED']) {
                // list of grades available to promote to
                $listOfGrades = $this->EducationGrades->getNextAvailableEducationGrades($fromGradeId);

            // REPEATED status
            } else if ($studentStatusId == $this->statuses['REPEATED'])  {
                $fromAcademicPeriodId = $this->studentData->academic_period_id;

                $gradeData = $this->EducationGrades->get($fromGradeId);
                $programmeId = $gradeData->education_programme_id;
                $gradeOrder = $gradeData->order;

                // list of grades available to repeat
                $query = $this->EducationGrades
                    ->find('list', [
                        'keyField' => 'id',
                        'valueField' => 'programme_grade_name'
                    ])
                    ->where([$this->EducationGrades->aliasField('education_programme_id') => $programmeId]);

                if ($toAcademicPeriodId == $fromAcademicPeriodId) {
                    // if same year is chosen, only show lower grades
                    $query->where([$this->EducationGrades->aliasField('order').' < ' => $gradeOrder]);
                } else {
                    // if other year is chosen, show current and lower grades
                    $query->where([$this->EducationGrades->aliasField('order').' <= ' => $gradeOrder]);
                }

                $listOfGrades = $query->toArray();
            }

            // Only display the options that are available in the institution and also linked to the current programme
            $options = array_intersect_key($listOfInstitutionGrades, $listOfGrades);

            if (count($options) == 0) {
                $attr['select'] = false;
                $options = [0 => $this->getMessage($this->aliasField('noAvailableGrades'))];
            }
        }

        $attr['type'] = 'select';
        $attr['options'] = !empty($options)? $options: [];
        return $attr;
    }

    public function onUpdateFieldEffectiveDate(Event $event, array $attr, $action, Request $request)
    {
        $toAcademicPeriodId = (!empty($request->data[$this->alias()]['academic_period_id']))? $request->data[$this->alias()]['academic_period_id']: '';
        $fromAcademicPeriodId = $this->studentData->academic_period_id;

        if (!empty($toAcademicPeriodId)) {
            $toPeriodData = $this->AcademicPeriods->get($toAcademicPeriodId);
            $startDate = $toPeriodData->start_date->format('d-m-Y');
            $endDate = $toPeriodData->end_date->format('d-m-Y');

            if ($toAcademicPeriodId == $fromAcademicPeriodId) {
                $attr['type'] = 'date';
                $attr['date_options'] = ['startDate' => $startDate, 'endDate' => $endDate];

            } else {
                $attr['type'] = 'readonly';
                $attr['attr']['value'] = $startDate;
            }
        } else {
            $attr['type'] = 'date';
        }

        return $attr;
    }

    public function addBeforeSave(Event $event, $entity, $requestData, $extra)
    {
        $process = function ($model, $entity) use ($requestData, $extra) {
            $studentData = $this->studentData;
            $studentStatuses = $this->statuses;
            $statusToUpdate = $requestData[$this->alias()]['student_status_id'];
            $effectiveDate = Time::parse($requestData[$this->alias()]['effective_date']);

            $fromAcademicPeriodId = $studentData->academic_period_id;
            $toAcademicPeriodId = $requestData[$this->alias()]['academic_period_id'];
            $toPeriodData = $this->AcademicPeriods->get($toAcademicPeriodId);

            // insert new record for student
            $entity->student_status_id = $studentStatuses['CURRENT'];
            $entity->end_date = $toPeriodData->end_date->format('Y-m-d');
            $entity->end_year = $toPeriodData->end_date->format('Y');

            if ($toAcademicPeriodId == $fromAcademicPeriodId) {
                // if student is promoted/demoted in the middle of the academic period
                $entity->start_date = $effectiveDate;
                $entity->start_year = $effectiveDate->year;
            } else {
                $entity->start_date = $toPeriodData->start_date->format('Y-m-d');
                $entity->start_year = $toPeriodData->start_date->format('Y');
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

            $existingStudentEntity->student_status_id = $statusToUpdate;

            if ($toAcademicPeriodId == $fromAcademicPeriodId) {
                // if student is promoted/demoted in the middle of the academic period
                $beforeEffectiveDate = Time::parse($requestData[$this->alias()]['effective_date'])->modify('-1 day');
                $existingStudentEntity->end_date = $beforeEffectiveDate;
                $existingStudentEntity->end_year = $beforeEffectiveDate->year;
            }
            // End

            if ($statusToUpdate == $studentStatuses['PROMOTED']) {
                $successMessage = $this->aliasField('success');
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

            return $this->controller->redirect($this->redirectUrl);
        };

        return $process;
    }
}