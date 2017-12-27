<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Datasource\ResultSetInterface;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\Network\Request;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\I18n\Time;
use Cake\I18n\Date;
use Cake\Utility\Inflector;
use Cake\Utility\Hash;
use App\Model\Table\ControllerActionTable;

use App\Model\Traits\MessagesTrait;

class StudentAdmissionTable extends ControllerActionTable
{
    // Workflow Steps - category
    const TO_DO = 1;
    const IN_PROGRESS = 2;
    const DONE = 3;

    use MessagesTrait;

    public function initialize(array $config)
    {
        $this->table('institution_student_admission');

        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users', 'foreignKey' => 'assignee_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);

        $this->addBehavior('Workflow.Workflow');
        $this->addBehavior('Institution.InstitutionWorkflowAccessControl');
        $this->addBehavior('User.AdvancedNameSearch');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Dashboard' => ['index'],
            'Students' => ['index', 'add']
        ]);

        $this->toggle('add', false);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        $validator
            ->add('start_date', 'ruleCompareDate', [
                'rule' => ['compareDate', 'end_date', false]
            ])
            ->add('end_date', [
            ])
            ->add('student_status_id', [
            ])
            ->add('academic_period_id', [
            ])
            ->allowEmpty('student_name')
            ->add('student_name', 'ruleCheckPendingAdmissionExist', [
                'rule' => ['checkPendingAdmissionExist'],
                'on' => 'create'
            ])
            ->add('student_name', 'ruleStudentNotEnrolledInAnyInstitutionAndSameEducationSystem', [
                'rule' => ['studentNotEnrolledInAnyInstitutionAndSameEducationSystem', []],
                'on' => 'create',
                'last' => true
            ])
            ->add('student_name', 'ruleStudentNotCompletedGrade', [
                'rule' => ['studentNotCompletedGrade', []],
                'on' => 'create',
                'last' => true
            ])
            ->add('student_name', 'ruleCheckAdmissionAgeWithEducationCycleGrade', [
                'rule' => ['checkAdmissionAgeWithEducationCycleGrade'],
                'on' => 'create'
            ])
            ->add('gender_id', 'ruleCompareStudentGenderWithInstitution', [
                'rule' => ['compareStudentGenderWithInstitution']
            ])
            ->add('institution_id', 'ruleCompareStudentGenderWithInstitution', [
                'rule' => ['compareStudentGenderWithInstitution']
            ])
            ->allowEmpty('class')
            ->add('class', 'ruleClassMaxLimit', [
                'rule' => ['checkInstitutionClassMaxLimit'],
                'on' => 'create'
            ])
            ->add('start_date', 'ruleCheckProgrammeEndDateAgainstStudentStartDate', [
                'rule' => ['checkProgrammeEndDateAgainstStudentStartDate', 'start_date']
            ])
            ->add('education_grade_id', 'ruleCheckProgrammeEndDate', [
                'rule' => ['checkProgrammeEndDate', 'education_grade_id']
            ])
            ;
        return $validator;
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.Students.afterSave'] = 'studentsAfterSave';
        $events['Model.Students.afterDelete'] = 'studentsAfterDelete';
        return $events;
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        //this is meant to force gender_id validation
        if ($data->offsetExists('student_id')) {
            $studentId = $data['student_id'];

            if (!$data->offsetExists('gender_id')) {
                $query = $this->Users->get($studentId);
                $data['gender_id'] = $query->gender_id;
            }
        }
    }

    public function studentsAfterSave(Event $event, $student)
    {
        $StudentStatuses = TableRegistry::get('Student.StudentStatuses');
        $statusList = $StudentStatuses->findCodeList();
        $Enrolled = $statusList['CURRENT'];
        $Promoted = $statusList['PROMOTED'];
        $Graduated = $statusList['GRADUATED'];
        $Withdraw = $statusList['WITHDRAWN'];

        if ($student->isNew()) { // add
            if ($student->student_status_id == $Enrolled) {
                // the logic below is to set all pending admission applications to rejected status once the student is successfully enrolled in a school
                $educationSystemId = $this->EducationGrades->getEducationSystemId($student->education_grade_id);
                $educationGradesToUpdate = $this->EducationGrades->getEducationGradesBySystem($educationSystemId);

                $conditions = [
                    'student_id' => $student->student_id,
                    'status' => 0, // pending status
                    'education_grade_id IN' => $educationGradesToUpdate
                ];

                // set to rejected status
                $this->updateAll(['status' => 2], $conditions);
            }
        } else { // edit
            // to cater logic if during undo promoted / graduate (without immediate enrolled record), there is still pending admission / transfer
            if ($student->dirty('student_status_id')) {
                $oldStatus = $student->getOriginal('student_status_id');
                $newStatus = $student->student_status_id;
                $UndoPromotion = $oldStatus == $Promoted && $newStatus == $Enrolled;
                $UndoGraduation = $oldStatus == $Graduated && $newStatus == $Enrolled;
                $UndoWithdraw = $oldStatus == $Withdraw && $newStatus == $Enrolled;

                if ($UndoPromotion || $UndoGraduation || $UndoWithdraw) {
                    $this->removePendingAdmission($student->student_id, $student->institution_id);
                }
            }
        }
    }

    public function studentsAfterDelete(Event $event, Entity $student)
    {
        // check for enrolled status and delete admission record
        $this->removePendingAdmission($student->student_id, $student->institution_id);
    }

    protected function removePendingAdmission($studentId, $institutionId)
    {
        $StudentStatuses = TableRegistry::get('Student.StudentStatuses');
        $statusList = $StudentStatuses->findCodeList();

        //remove pending transfer request.
        //could not include grade / academic period because not always valid. (promotion/graduation/repeat and transfer/admission can be done on different grade / academic period)
        $conditions = [
            'student_id' => $studentId,
            'previous_institution_id' => $institutionId,
            'status' => 0, //pending status
            'type' => 2 //transfer
        ];

        $entity = $this
                ->find()
                ->where(
                    $conditions
                )
                ->first();

        if (!empty($entity)) {
            $this->delete($entity);
        }

        //remove pending admission request.
        //no institution_id because in the pending admission, the value will be (0)
        $conditions = [
            'student_id' => $studentId,
            'status' => 0, //pending status
            'type' => 1 //admission
        ];

        $entity = $this
                ->find()
                ->where(
                    $conditions
                )
                ->first();

        if (!empty($entity)) {
            $this->delete($entity);
        }
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $toolbarButtons = $extra['toolbarButtons'];
        $session = $this->request->session();
        $institutionId = !empty($this->request->param('institutionId')) ? $this->ControllerAction->paramsDecode($this->request->param('institutionId'))['id'] : $session->read('Institution.Institutions.id');

        if ($this->action == 'index') {
            $toolbarButtons['back']['label'] = '<i class="fa kd-back"></i>';
            $toolbarButtons['back']['attr'] = [
                'title' => __('Back'),
                'class' => 'btn btn-xs btn-default',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false
            ];
            $toolbarButtons['back']['url'] = [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'institutionId' => $this->paramsEncode(['id' => $institutionId]),
                'action' => 'Students',
                0 => 'index'
            ];

        } elseif ($this->action == 'edit') {
            $toolbarButtons['back']['url'][0] = 'index';
            if ($toolbarButtons['back']['url']['controller'] == 'Dashboard') {
                $toolbarButtons['back']['url']['action'] = 'index';
                unset($toolbarButtons['back']['url'][0]);
            }
            unset($toolbarButtons['back']['url'][1]);
        }
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('comment', ['type' => 'hidden']);
        $this->field('start_date', ['type' => 'hidden']);
        $this->field('end_date', ['type' => 'hidden']);
        $this->setFieldOrder(['status_id', 'assignee_id', 'student_id', 'academic_period_id', 'education_grade_id', 'institution_class_id']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        // search
        $search = $this->getSearchKey();
        if (!empty($search)) {
            $nameConditions = $this->getNameSearchConditions(['alias' => 'Users', 'searchTerm' => $search]);
            $extra['OR'] = $nameConditions; // to be merged with auto_search 'OR' conditions
        }
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('student_id', ['type' => 'readonly', 'attr' => ['value' => $this->Users->get($entity->student_id)->name_with_id]]);
        $this->field('institution_id', ['type' => 'readonly', 'attr' => ['value' => $this->Institutions->get($entity->institution_id)->code_name]]);
        $this->field('academic_period_id', ['type' => 'readonly', 'attr' => ['value' => $this->AcademicPeriods->get($entity->academic_period_id)->name]]);
        $this->field('education_grade_id', ['type' => 'readonly', 'attr' => ['value' => $this->EducationGrades->get($entity->education_grade_id)->programme_grade_name]]);
        $this->field('institution_class_id', ['entity' => $entity]);
        $this->field('end_date', ['entity' => $entity]);
        $this->setFieldOrder(['student_id', 'academic_period_id', 'education_grade_id', 'institution_class_id', 'start_date', 'end_date', 'comment']);

        $urlParams = $this->url('edit');
        if ($urlParams['controller'] == 'Dashboard') {
            $this->Navigation->addCrumb('Student Admission', $urlParams);
        }
    }

    public function viewAfterAction(Event $event, Entity $entity)
    {
        $this->setFieldOrder(['status_id', 'assignee_id', 'student_id', 'academic_period_id', 'education_grade_id', 'institution_class_id', 'start_date', 'end_date', 'comment']);
    }

    public function onGetStudentId(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('user')) {
            $value = $entity->user->name_with_id;
        }
        return $value;
    }

    public function onUpdateFieldEndDate(Event $event, array $attr, $action, $request)
    {
        if ($action == 'edit') {
            $endDate = $attr['entity']->end_date;
            $attr['type'] = 'readonly';
            $attr['value'] = $endDate->format('d-m-Y');
            $attr['attr']['value'] = $endDate->format('d-m-Y');
            return $attr;
        }
    }

    public function onUpdateFieldInstitutionClassId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'edit') {
            $entity = $attr['entity'];
            $Classes = TableRegistry::get('Institution.InstitutionClasses');

            $options = $Classes->find('list')
                ->innerJoinWith('ClassGrades')
                ->where([
                    $Classes->aliasField('institution_id') => $entity->institution_id,
                    $Classes->aliasField('academic_period_id') => $entity->academic_period_id,
                    'ClassGrades.education_grade_id' => $entity->education_grade_id
                ])
                ->toArray();

            $attr['type'] = 'select';
            $attr['options'] = $options;
            return $attr;
        }
    }

    public function editBeforeSave(Event $event, Entity $entity, ArrayObject $data) // admission approval
    {
        $errors = $entity->errors();

        if (empty($errors)) {
            $Students = TableRegistry::get('Institution.Students');

            $newSchoolId = $entity->institution_id;
            $studentId = $entity->student_id;
            $gradeId = $entity->education_grade_id;
            $newSystemId = TableRegistry::get('Education.EducationGrades')->getEducationSystemId($gradeId);

            $validateEnrolledInAnyInstitutionResult = $Students->validateEnrolledInAnyInstitution($studentId, $newSystemId, ['targetInstitutionId' => $newSchoolId]);
            if (!empty($validateEnrolledInAnyInstitutionResult)) {
                $this->Alert->error($validateEnrolledInAnyInstitutionResult, ['type' => 'message']);
            } else if ($Students->completedGrade($gradeId, $studentId)) {
                $this->Alert->error('Institution.Students.student_name.ruleStudentNotCompletedGrade');
            } else { // if not exists

                $entity->status = self::APPROVED;
                if (!$this->save($entity, ['validate' => false])) {
                    $this->Alert->error('general.edit.failed');
                    $this->log($entity->errors(), 'debug');
                } else {
                    $this->Alert->success('StudentAdmission.approve');
                }
            }

            // To redirect back to the student admission if it is not access from the workbench
            $urlParams = $this->ControllerAction->url('index');
            $plugin = false;
            $controller = 'Dashboard';
            $action = 'index';
            if ($urlParams['controller'] == 'Institutions') {
                $plugin = 'Institution';
                $controller = 'Institutions';
                $action = 'StudentAdmission';
            }

            $event->stopPropagation();
            return $this->controller->redirect(['plugin' => $plugin, 'controller' => $controller, 'action' => $action]);
        } else {
            // required for validation to work
            $process = function($model, $entity) {
                return false;
            };

            return $process;
        }
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $processStudent = false;
        $errors = $entity->errors();

        //this logic is meant for auto approve admission and add student into the institution when the creator has 'Student Admission -> Execute' permission
        if ($entity->isNew()) {
            //check for super admin
            $superAdmin = Hash::get($_SESSION['Auth'], 'User.super_admin');

            if (!$superAdmin) {
                $processStudent = Hash::check($_SESSION['Permissions'], 'Institutions.StudentAdmission.execute');
            } else {
                $processStudent = true;
            }
        } else {
            if ($entity->dirty('status') && $entity->status == self::APPROVED) { // if the status has been changed from Pending to Approved
                $processStudent = true;
            }
        }

        if ($processStudent && empty($errors)) {
            $EducationGradesTable = TableRegistry::get('Education.EducationGrades');

            $educationSystemId = $EducationGradesTable->getEducationSystemId($entity->education_grade_id);
            $educationGradesToUpdate = $EducationGradesTable->getEducationGradesBySystem($educationSystemId);

            $conditions = [
                'student_id' => $entity->student_id,
                'status' => self::NEW_REQUEST,
                'education_grade_id IN' => $educationGradesToUpdate
            ];

            // Reject all other new pending admission / transfer application entry of the
            // same student for the same academic period
            $this->updateAll(
                ['status' => self::REJECTED],
                [$conditions]
            );

            $selectedClassId = $entity->institution_class_id;
            $Students = TableRegistry::get('Institution.Students');
            $StudentStatuses = TableRegistry::get('Student.StudentStatuses');
            $statuses = $StudentStatuses->findCodeList();

            $newSchoolId = $entity->institution_id;
            $studentId = $entity->student_id;
            $periodId = $entity->academic_period_id;
            $gradeId = $entity->education_grade_id;

            // add the student to the new school
            $entityData = [
                'institution_id' => $newSchoolId,
                'student_id' => $studentId,
                'academic_period_id' => $periodId,
                'education_grade_id' => $gradeId,
                'student_status_id' => $statuses['CURRENT']
            ];

            if ($entity->start_date instanceof Date) {
                $entityData['start_date'] = $entity->start_date->format('Y-m-d');
            } else {
                $entityData['start_date'] = $entity->start_date;
            }
            $entityData['end_date'] = $entity->end_date->format('Y-m-d');

            if (!is_null($selectedClassId)) {
                $entityData['class'] = $selectedClassId;
            }

            $newEntity = $Students->newEntity($entityData);

            if ($Students->save($newEntity)) {
                // once student is added to institution successfully, the admission status will be set to Approved
                $entity->status = self::APPROVED;
            } else {
                return false; // to stop admission record from saving
            }
        }
    }

    public function findWorkbench(Query $query, array $options)
    {
        $controller = $options['_controller'];
        $controller->loadComponent('AccessControl');

        $session = $controller->request->session();
        $AccessControl = $controller->AccessControl;

        $isAdmin = $session->read('Auth.User.super_admin');
        $userId = $session->read('Auth.User.id');

        $where = [
            $this->aliasField('status') => self::NEW_REQUEST,
            $this->aliasField('type') => self::ADMISSION
        ];

        if (!$isAdmin) {
            if ($AccessControl->check(['Institutions', 'StudentAdmission', 'edit'])) {
                $SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
                $institutionIds = $SecurityGroupUsers->getInstitutionsByUser($userId);

                if (empty($institutionIds)) {
                    // return empty list if the user does not have access to any schools
                    return $query->where([$this->aliasField('id') => -1]);
                } else {
                    $where[$this->aliasField('institution_id') . ' IN '] = $institutionIds;
                }
            } else {
            //  // return empty list if the user does not permission to approve Student Admission
                return $query->where([$this->aliasField('id') => -1]);
            }
        }

        $query
            ->select([
                $this->aliasField('id'),
                $this->aliasField('institution_id'),
                $this->aliasField('modified'),
                $this->aliasField('created'),
                $this->Users->aliasField('openemis_no'),
                $this->Users->aliasField('first_name'),
                $this->Users->aliasField('middle_name'),
                $this->Users->aliasField('third_name'),
                $this->Users->aliasField('last_name'),
                $this->Users->aliasField('preferred_name'),
                $this->Institutions->aliasField('code'),
                $this->Institutions->aliasField('name'),
                $this->CreatedUser->aliasField('openemis_no'),
                $this->CreatedUser->aliasField('first_name'),
                $this->CreatedUser->aliasField('middle_name'),
                $this->CreatedUser->aliasField('third_name'),
                $this->CreatedUser->aliasField('last_name'),
                $this->CreatedUser->aliasField('preferred_name')
            ])
            ->contain([$this->Users->alias(), $this->Institutions->alias(), $this->CreatedUser->alias()])
            ->where($where)
            ->order([$this->aliasField('created') => 'DESC'])
            ->formatResults(function (ResultSetInterface $results) {
                return $results->map(function ($row) {
                    $url = [
                        'plugin' => false,
                        'controller' => 'Dashboard',
                        'action' => 'StudentAdmission',
                        'edit',
                        $this->paramsEncode(['id' => $row->id])
                    ];

                    if (is_null($row->modified)) {
                        $receivedDate = $this->formatDate($row->created);
                    } else {
                        $receivedDate = $this->formatDate($row->modified);
                    }

                    $row['url'] = $url;
                    $row['status'] = __('Pending For Approval');
                    $row['request_title'] = sprintf(__('Admission of student %s'), $row->user->name_with_id);
                    $row['institution'] = $row->institution->code_name;
                    $row['received_date'] = $receivedDate;
                    $row['requester'] = $row->created_user->name_with_id;

                    return $row;
                });
            });

        return $query;
    }
}
