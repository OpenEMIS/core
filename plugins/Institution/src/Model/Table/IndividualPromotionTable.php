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
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
        $this->addBehavior('OpenEmis.Section');
    }

    public function implementedEvents() {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.reconfirm'] = 'reconfirm';
        return $events;
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        $validator
            ->add('effective_date', 'ruleDateWithinAcademicPeriod', [
                'rule' => ['checkDateWithinAcademicPeriod', 'academic_period_id'],
                'provider' => 'table',
                'on' => function ($context) {
                    $fromAcademicPeriodId = $context['data']['from_academic_period_id'];
                    $toAcademicPeriodId = $context['data']['academic_period_id'];
                    return $fromAcademicPeriodId == $toAcademicPeriodId;
                }
            ]);
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
        $this->institutionId = $this->Session->read('Institution.Institutions.id');
        $this->id = $this->Session->read($this->registryAlias().'.id');
        $this->statuses = $this->StudentStatuses->findCodeList();
        $this->studentData = $this->get($this->id);

        $StudentAdmissionTable = TableRegistry::get('Institution.StudentAdmission');

        // check transfer requests
        $conditions = [
         'student_id' => $this->studentData->student_id,
         'status' => $StudentAdmissionTable::NEW_REQUEST,
         'new_education_grade_id' => $this->studentData->education_grade_id,
         'previous_institution_id' => $this->studentData->institution_id,
         'type' => $StudentAdmissionTable::TRANSFER,
        ];

        $transferCount = $StudentAdmissionTable->find()
         ->where($conditions)
         ->count();

        // check dropout requests
        $StudentDropoutTable = TableRegistry::get('Institution.StudentDropout');
        $conditions = [
         'student_id' => $this->studentData->student_id,
         'status' => $StudentDropoutTable::NEW_REQUEST,
         'education_grade_id' => $this->studentData->education_grade_id,
         'institution_id' => $this->studentData->institution_id,
         'academic_period_id' => $this->studentData->academic_period_id,
        ];

        $dropoutCount = $StudentDropoutTable->find()
         ->where($conditions)
         ->count();

        if ($transferCount || $dropoutCount) {
            $this->Session->write($this->registryAlias().'.pendingRequest', true);
            return $this->controller->redirect($this->redirectUrl);
        }

        if (isset($extra['toolbarButtons']['back'])) {
            $extra['toolbarButtons']['back']['url'] = $this->redirectUrl;
        }

        // populate request data for readonly fields
        $this->request->data[$this->alias()]['institution_id'] = $this->institutionId;
        $this->request->data[$this->alias()]['student_id'] = $this->studentData->student_id;
        $this->request->data[$this->alias()]['from_academic_period_id'] = $this->studentData->academic_period_id;
        $this->request->data[$this->alias()]['from_education_grade_id'] = $this->studentData->education_grade_id;

        $this->fields = [];
        $this->addSections();
        $this->field('student_id');
        $this->field('from_academic_period_id');
        $this->field('from_education_grade_id');
        $this->field('student_status_id');
        $this->field('academic_period_id');
        $this->field('education_grade_id');
        $this->field('institution_class_id');
        $this->field('effective_date');

        $this->setFieldOrder([
            'student_id',
            'existing_information_header', 'from_academic_period_id', 'from_education_grade_id',
            'new_information_header', 'student_status_id', 'academic_period_id', 'education_grade_id', 'institution_class_id', 'effective_date']);
    }

    private function addSections()
    {
        $this->field('existing_information_header', ['type' => 'section', 'title' => __('Promote From')]);
        $this->field('new_information_header', ['type' => 'section', 'title' => __('Promote To')]);
    }

    public function onGetFormButtons(Event $event, ArrayObject $buttons)
    {
        switch ($this->action) {
            case 'add':
                $buttons[0]['name'] = '<i class="fa fa-check"></i> ' . __('Next');
                $buttons[1]['url'] = $this->redirectUrl;
                break;

            case 'reconfirm':
                $buttons[0]['name'] = '<i class="fa fa-check"></i> ' . __('Confirm');
                $buttons[1]['url'] = $this->url('add');
                break;

            default:
                # code...
                break;
        }
    }

    public function onUpdateFieldStudentId(Event $event, array $attr, $action, Request $request)
    {
        switch ($action) {
            case 'reconfirm':
                $sessionKey = $this->registryAlias() . '.confirm';
                if ($this->Session->check($sessionKey)) {
                    $currentData = $this->Session->read($sessionKey);
                }

                $studentId = $currentData->student_id;
                break;

            default:
                $studentId = $this->studentData->student_id;
                break;
        }


        $attr['type'] = 'readonly';
        $attr['attr']['value'] = $this->Users->get($studentId)->name_with_id;
        return $attr;
    }

    public function onUpdateFieldFromAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        switch ($action) {
            case 'reconfirm':
                $sessionKey = $this->registryAlias() . '.confirm';
                if ($this->Session->check($sessionKey)) {
                    $currentData = $this->Session->read($sessionKey);
                }

                $academicPeriodId = $currentData->from_academic_period_id;
                break;

            default:
                $academicPeriodId = $this->studentData->academic_period_id;
                break;
        }

        $attr['type'] = 'readonly';
        $attr['attr']['value'] = $this->AcademicPeriods->get($academicPeriodId)->name;
        return $attr;
    }

    public function onUpdateFieldFromEducationGradeId(Event $event, array $attr, $action, Request $request)
    {
        switch ($action) {
            case 'reconfirm':
                $sessionKey = $this->registryAlias() . '.confirm';
                if ($this->Session->check($sessionKey)) {
                    $currentData = $this->Session->read($sessionKey);
                }

                $educationGradeId = $currentData->from_education_grade_id;
                break;

            default:
                $educationGradeId = $this->studentData->education_grade_id;
                break;
        }

        $attr['type'] = 'readonly';
        $attr['attr']['value'] = $this->EducationGrades->get($educationGradeId)->programme_grade_name;
        return $attr;
    }

    public function onUpdateFieldStudentStatusId(Event $event, array $attr, $action, Request $request)
    {
        $studentStatusesList = $this->StudentStatuses->find('list')->toArray();

        switch ($action) {
            case 'reconfirm':
                $sessionKey = $this->registryAlias() . '.confirm';
                if ($this->Session->check($sessionKey)) {
                    $currentData = $this->Session->read($sessionKey);
                }

                $attr['type'] = 'readonly';
                $attr['attr']['value'] = __($studentStatusesList[$currentData->student_status_id]);
                break;

            default:
                $statusOptions = [];
                $statusCodes = $this->StudentStatuses->findCodeList();
                $educationGradeId = $this->studentData->education_grade_id;
                $nextGrades = $this->EducationGrades->getNextAvailableEducationGrades($educationGradeId, false);

                if (count($nextGrades) != 0) {
                    $statusOptions[$statusCodes['PROMOTED']] = __($studentStatusesList[$statusCodes['PROMOTED']]);
                }

                $statusOptions[$statusCodes['REPEATED']] = __($studentStatusesList[$statusCodes['REPEATED']]);

                $attr['options'] = $statusOptions;
                $attr['onChangeReload'] = true;
                break;
        }

        return $attr;
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        switch ($action) {
            case 'reconfirm':
                $sessionKey = $this->registryAlias() . '.confirm';
                if ($this->Session->check($sessionKey)) {
                    $currentData = $this->Session->read($sessionKey);
                }

                $attr['type'] = 'readonly';
                $attr['attr']['value'] = $this->AcademicPeriods->get($currentData->academic_period_id)->name;
                break;

            default:
                $fromPeriodId = $this->studentData->academic_period_id;
                $fromPeriod = $this->AcademicPeriods->get($fromPeriodId);

                // only current and later academic periods will be shown
                $condition = [$this->AcademicPeriods->aliasField('order').' <= ' => $fromPeriod->order];
                $periodOptions = $this->AcademicPeriods->getYearList(['conditions' => $condition, 'isEditable' => true]);

                $attr['type'] = 'select';
                $attr['options'] = $periodOptions;
                $attr['onChangeReload'] = true;
                break;
        }

        return $attr;
    }

    public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, Request $request)
    {
        switch ($action) {
            case 'reconfirm':
                $sessionKey = $this->registryAlias() . '.confirm';
                if ($this->Session->check($sessionKey)) {
                    $currentData = $this->Session->read($sessionKey);
                }

                $attr['type'] = 'readonly';
                $attr['attr']['value'] = $this->EducationGrades->get($currentData->education_grade_id)->programme_grade_name;
                break;

            default:
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
                $attr['onChangeReload'] = true;
                break;
        }

        return $attr;
    }

    public function onUpdateFieldInstitutionClassId(Event $event, array $attr, $action, Request $request)
    {
        switch ($action) {
            case 'reconfirm':
                $sessionKey = $this->registryAlias() . '.confirm';
                if ($this->Session->check($sessionKey)) {
                    $currentData = $this->Session->read($sessionKey);
                }

                if (!empty($currentData->institution_class_id)) {
                    $attr['type'] = 'readonly';
                    $attr['attr']['value'] = $this->InstitutionClasses->get($currentData->institution_class_id)->name;
                } else {
                    $attr['type'] = 'hidden';
                }
                break;

            default:
                $fromAcademicPeriodId = $this->studentData->academic_period_id;
                $toAcademicPeriodId = (!empty($request->data[$this->alias()]['academic_period_id']))? $request->data[$this->alias()]['academic_period_id']: '';
                $toGrade = (!empty($request->data[$this->alias()]['education_grade_id']))? $request->data[$this->alias()]['education_grade_id']: '';

                if (!empty($toGrade) && !empty($toAcademicPeriodId) && $toAcademicPeriodId == $fromAcademicPeriodId) {
                    $InstitutionClass = $this->InstitutionClasses;
                    $classOptions = $InstitutionClass
                        ->find('list')
                        ->matching('ClassGrades')
                        ->where([$InstitutionClass->aliasField('institution_id') => $this->institutionId,
                            $InstitutionClass->aliasField('academic_period_id') => $toAcademicPeriodId,
                            'ClassGrades.education_grade_id' => $toGrade])
                        ->order($InstitutionClass->aliasField('name'))
                        ->toArray();

                    $attr['type'] = 'select';
                    $attr['options'] = $classOptions;

                } else {
                    $attr['type'] = 'hidden';
                }
                break;
        }

        return $attr;
    }

    public function onUpdateFieldEffectiveDate(Event $event, array $attr, $action, Request $request)
    {
        switch ($action) {
            case 'reconfirm':
                $sessionKey = $this->registryAlias() . '.confirm';
                if ($this->Session->check($sessionKey)) {
                    $currentData = $this->Session->read($sessionKey);
                }

                $attr['type'] = 'readonly';
                $attr['attr']['value'] = $currentData->effective_date;
                break;

            default:
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
                break;
        }


        return $attr;
    }

    public function addBeforeSave(Event $event, $entity, $requestData, $extra)
    {
        $process = function($model, $entity) use ($event, $requestData) {
            if (empty($entity->errors())) {
                $this->Session->write($this->registryAlias().'.confirm', $entity);
                $event->stopPropagation();
                return $this->controller->redirect($this->url('reconfirm'));
            }
        };

        return $process;
    }

    public function reconfirm(Event $event, ArrayObject $extra)
    {
        $sessionKey = $this->registryAlias() . '.confirm';
        if ($this->Session->check($sessionKey)) {
            $currentEntity = $this->Session->read($sessionKey);
        } else {
            $this->Alert->warning('general.notExists');
            return $this->controller->redirect($this->url('add'));
        }

        $Students = TableRegistry::get('Institution.StudentUser');
        $url['action'] = $Students->alias();
        $url[0] = 'view';
        $url[1] = $this->Session->read('Student.Students.id');

        $extra['config']['form'] = true;
        $extra['elements']['edit'] = ['name' => 'OpenEmis.ControllerAction/edit'];

        $this->Alert->info('general.reconfirm', ['reset' => true]);

        $this->fields = [];
        $this->addSections();
        $this->field('student_id');
        $this->field('from_academic_period_id');
        $this->field('from_education_grade_id');
        $this->field('student_status_id');
        $this->field('academic_period_id');
        $this->field('education_grade_id');
        $this->field('effective_date');
        $this->field('institution_class_id');

        $this->setFieldOrder([
            'student_id',
            'existing_information_header', 'from_academic_period_id', 'from_education_grade_id',
            'new_information_header', 'student_status_id', 'academic_period_id', 'education_grade_id', 'institution_class_id', 'effective_date']);

        if ($currentEntity && !empty($currentEntity)) {
            if ($this->request->is(['post', 'put'])) {
                $saveSuccess = $this->savePromotion($currentEntity);
                if ($saveSuccess) {
                    $this->Alert->success($this->aliasField('success'), ['reset' => true]);
                    $this->Session->delete($this->registryAlias());
                    $event->stopPropagation();
                    return $this->controller->redirect($url);

                } else {
                    $this->Alert->error($this->aliasField('savingPromotionError'), ['reset' => true]);
                }
            }
            $this->controller->set('data', $currentEntity);
        } else {
            $this->Alert->warning('general.notExists');
            return $this->controller->redirect($this->url('add'));
        }

        return $currentEntity;
    }

    public function savePromotion(Entity $entity)
    {
        $studentStatuses = $this->StudentStatuses->findCodeList();
        $id = $this->Session->read($this->registryAlias().'.id');
        $studentData = $this->get($id);

        $fromAcademicPeriodId = $studentData->academic_period_id;
        $toAcademicPeriodId = $entity->academic_period_id;
        $toPeriodData = $this->AcademicPeriods->get($toAcademicPeriodId);
        $statusToUpdate = $entity->student_status_id;
        $effectiveDate = Time::parse($entity->effective_date);

        // InstitutionStudents: Insert new record
        $studentObj = [];
        $studentObj['student_status_id'] = $studentStatuses['CURRENT'];
        $studentObj['student_id'] = $entity->student_id;
        $studentObj['education_grade_id'] = $entity->education_grade_id;
        $studentObj['academic_period_id'] = $entity->academic_period_id;
        $studentObj['end_date'] = $toPeriodData->end_date;
        $studentObj['end_year']= $toPeriodData->end_year;
        $studentObj['institution_id'] = $entity->institution_id;

        if ($toAcademicPeriodId == $fromAcademicPeriodId) {
            // if student is promoted/demoted in the middle of the academic period
            $studentObj['start_date'] = $effectiveDate;
            $studentObj['start_year'] = $effectiveDate->year;
        } else {
            $studentObj['start_date'] = $toPeriodData->start_date;
            $studentObj['start_year'] = $toPeriodData->start_year;
        }

        $newInstitutionStudent = $this->newEntity($studentObj);
        // End

        // InstitutionStudents: Update old record
        $existingInstitutionStudent = $this->find()
            ->where([
                $this->aliasField('institution_id') => $studentData->institution_id,
                $this->aliasField('student_id') => $studentData->student_id,
                $this->aliasField('academic_period_id') => $studentData->academic_period_id,
                $this->aliasField('education_grade_id') => $studentData->education_grade_id,
                $this->aliasField('student_status_id') => $studentStatuses['CURRENT']
            ])
            ->first();

        $existingInstitutionStudent->student_status_id = $statusToUpdate;

        if ($toAcademicPeriodId == $fromAcademicPeriodId) {
            // if student is promoted/demoted in the middle of the academic period
            $beforeEffectiveDate = Time::parse($entity->effective_date)->modify('-1 day');
            $existingInstitutionStudent->end_date = $beforeEffectiveDate;
            $existingInstitutionStudent->end_year = $beforeEffectiveDate->year;
        }
        // End

        // InstitutionClassStudents: Insert and update records
        $classId = $entity->institution_class_id;

        if (!empty($classId)) {
            $newClassStudent = [];
            $newClassStudent['student_id'] = $entity->student_id;
            $newClassStudent['education_grade_id'] = $entity->education_grade_id;
            $newClassStudent['institution_class_id'] = $entity->institution_class_id;
            $newClassStudent['student_status_id'] = $studentStatuses['CURRENT'];
            $newClassStudent['institution_id'] = $entity->institution_id;
            $newClassStudent['academic_period_id'] = $entity->academic_period_id;

            $InstitutionClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
            $existingClassStudent = $InstitutionClassStudents->find()
            ->where([
                $InstitutionClassStudents->aliasField('institution_id') => $studentData->institution_id,
                $InstitutionClassStudents->aliasField('student_id') => $studentData->student_id,
                $InstitutionClassStudents->aliasField('academic_period_id') => $studentData->academic_period_id,
                $InstitutionClassStudents->aliasField('education_grade_id') => $studentData->education_grade_id,
                $InstitutionClassStudents->aliasField('student_status_id') => $studentStatuses['CURRENT']
            ])
            ->first();

            if (!empty($existingClassStudent)) {
                $existingClassStudent->student_status_id = $statusToUpdate;
            }
        }
        // End

        if ($this->save($existingInstitutionStudent)) {
            if ($this->save($newInstitutionStudent)) {
                if (!empty($classId)) {
                    if (!empty($existingClassStudent)) {
                        $InstitutionClassStudents->save($existingClassStudent);
                    }
                    $InstitutionClassStudents->autoInsertClassStudent($newClassStudent);
                }
                return true;

            } else {
                $this->log($newInstitutionStudent->errors, 'debug');
            }
        } else {
            $message = 'failed to update student status';
            $this->log($message, 'debug');
        }

        return false;
    }
}