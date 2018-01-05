<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use App\Model\Table\AppTable;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\Network\Request;
use Cake\Datasource\ResultSetInterface;

class StudentWithdrawTable extends AppTable
{
    const NEW_REQUEST = 0;
    const APPROVED = 1;
    const REJECTED = 2;

    public function initialize(array $config)
    {
        $this->table('institution_student_withdraw');
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('StudentWithdrawReasons', ['className' => 'Student.StudentWithdrawReasons', 'foreignKey' => 'student_withdraw_reason_id']);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Dashboard' => ['index']
        ]);
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->addBehavior('Workflow.Workflow');
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
        $events['Model.Students.afterDelete'] = 'studentsAfterDelete';
        return $events;
    }

    public function studentsAfterDelete(Event $event, Entity $student)
    {
        $this->removePendingWithdraw($student->student_id, $student->institution_id);
    }

    protected function removePendingWithdraw($studentId, $institutionId)
    {
        //could not include grade / academic period because not always valid. (promotion/graduation/repeat and withdraw can be done on different grade / academic period)
        $conditions = [
            'student_id' => $studentId,
            'institution_id' => $institutionId,
            'status_id' => 0, //pending status_id
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

    public function editOnInitialize(Event $event, Entity $entity)
    {
        $this->request->data[$this->alias()]['status_id'] = $entity->status_id;
        $this->request->data[$this->alias()]['effective_date'] = $entity->start_date;
    }

    public function afterAction($event)
    {
        $this->ControllerAction->field('effective_date', ['visible' => ['edit' => true, 'index' => false, 'view' => true]]);
        $this->ControllerAction->field('comment', ['visible' => ['index' => false, 'edit' => true, 'view' => true]]);
        $this->ControllerAction->field('student_id');
        $this->ControllerAction->field('status_id');
        $this->ControllerAction->field('institution_id', ['visible' => ['index' => false, 'edit' => true, 'view' => 'true']]);
        $this->ControllerAction->field('academic_period_id', ['type' => 'readonly']);
        $this->ControllerAction->field('education_grade_id');
        $this->ControllerAction->field('comment');
        $this->ControllerAction->field('created', ['visible' => ['index' => false, 'edit' => true, 'view' => true]]);
    }

    public function editAfterAction($event, Entity $entity)
    {
        $this->ControllerAction->field('effective_date', ['attr' => ['entity' => $entity]]);
        $this->ControllerAction->field('student_id', ['type' => 'readonly', 'attr' => ['value' => $this->Users->get($entity->student_id)->name_with_id]]);
        $this->ControllerAction->field('institution_id', ['type' => 'readonly', 'attr' => ['value' => $this->Institutions->get($entity->institution_id)->code_name]]);
        $this->ControllerAction->field('academic_period_id', ['type' => 'readonly', 'attr' => ['value' => $this->AcademicPeriods->get($entity->academic_period_id)->name]]);
        $this->ControllerAction->field('education_grade_id', ['type' => 'readonly', 'attr' => ['value' => $this->EducationGrades->get($entity->education_grade_id)->programme_grade_name]]);
        $this->ControllerAction->field('student_withdraw_reason_id', ['type' => 'readonly', 'attr' => ['value' => $this->StudentWithdrawReasons->get($entity->student_withdraw_reason_id)->name]]);
        $this->ControllerAction->field('created', ['type' => 'disabled', 'attr' => ['value' => $this->formatDate($entity->created)]]);
        $this->ControllerAction->setFieldOrder([
            'created', 'status_id', 'student_id',
            'institution_id', 'academic_period_id', 'education_grade_id',
            'effective_date', 'student_withdraw_reason_id', 'comment',
        ]);

        $urlParams = $this->ControllerAction->url('edit');
        if ($urlParams['controller'] == 'Dashboard') {
            $this->Navigation->addCrumb('Withdraw Approvals', $urlParams);
        }
    }

    public function viewAfterAction($event, Entity $entity)
    {
        $this->request->data[$this->alias()]['status_id'] = $entity->status_id;
        $this->ControllerAction->field('student_withdraw_reason_id', ['type' => 'readonly', 'attr' => ['value' => $this->StudentWithdrawReasons->get($entity->student_withdraw_reason_id)->name]]);
        $this->ControllerAction->setFieldOrder([
            'created', 'status_id', 'student_id',
            'institution_id', 'academic_period_id', 'education_grade_id',
            'effective_date', 'student_withdraw_reason_id', 'comment'
        ]);
    }

    public function onGetFormButtons(Event $event, ArrayObject $buttons)
    {
        if ($this->action == 'edit') {
            // If the status_id is new application then display the approve and reject button,
            // if not remove the button just in case the user gets to access the edit page
            if ($this->request->data[$this->alias()]['status_id'] == self::NEW_REQUEST && ($this->AccessControl->check(['Institutions', $this->alias(), 'edit']))) {
                $buttons[0] = [
                    'name' => '<i class="fa fa-check"></i> ' . __('Approve'),
                    'attr' => ['class' => 'btn btn-default', 'div' => false, 'name' => 'submit', 'value' => 'save']
                ];

                $buttons[1] = [
                    'name' => '<i class="fa fa-close"></i> ' . __('Reject'),
                    'attr' => ['class' => 'btn btn-outline btn-cancel', 'div' => false, 'name' => 'submit', 'value' => 'reject']
                ];
            } else {
                unset($buttons[0]);
                unset($buttons[1]);
            }
        }
    }

    public function onGetStudentId(Event $event, Entity $entity)
    {
        $urlParams = $this->ControllerAction->url('index');
        if ($entity->status_id == self::NEW_REQUEST) {
            if ($this->AccessControl->check(['Institutions', $this->alias(), 'edit'])) {
                return $event->subject()->Html->link($entity->user->name, [
                    'plugin' => $urlParams['plugin'],
                    'controller' => $urlParams['controller'],
                    'action' => $urlParams['action'],
                    '0' => 'edit',
                    '1' => $this->paramsEncode(['id' => $entity->id])
                ]);
            }
        }
    }

    public function onUpdateFieldStatus(Event $event, array $attr, $action, $request)
    {
        if ($action == 'edit') {
            $status_id = $request->data[$this->alias()]['status_id'];
            $attr['type'] = 'readonly';
            if ($status_id == self::NEW_REQUEST) {
                $attr['attr']['value'] = __('New');
            } else if ($status_id == self::APPROVED) {
                $attr['attr']['value'] = __('Approved');
            } else if ($status_id == self::REJECTED) {
                $attr['attr']['value'] = __('Rejected');
            }
            return $attr;
        }
    }

    public function onUpdateFieldComment(Event $event, array $attr, $action, $request)
    {
        if ($action == 'edit') {
            if ($request->data[$this->alias()]['status_id'] != self::NEW_REQUEST || !($this->AccessControl->check(['Institutions', $this->alias(), 'edit']))) {
                $attr['type'] = 'readonly';
            }
            return $attr;
        }
    }

    public function onUpdateFieldEffectiveDate(Event $event, array $attr, $action, $request)
    {
        if ($action == 'edit') {
            if ($request->data[$this->alias()]['status_id'] != self::NEW_REQUEST || !($this->AccessControl->check(['Institutions', $this->alias(), 'edit']))) {
                $attr['type'] = 'readonly';
            }

            $entity = $attr['attr']['entity'];
            $studentId = $entity->student_id;

            $StudentStatuses = TableRegistry::get('Student.StudentStatuses');
            $enrolledStatus = $StudentStatuses->getIdByCode('CURRENT');

            $Students = TableRegistry::get('Institution.Students');
            $StudentsData = $Students
                ->find()
                ->where([$Students->aliasField('student_id') => $studentId, $Students->aliasField('student_status_id') => $enrolledStatus])
                ->first();

            if (!empty($StudentsData)) {
                $enrolledDate = $StudentsData->start_date->format('d-m-Y');
                $attr['date_options'] = ['startDate' => $enrolledDate];
            }

            return $attr;
        }
    }

    public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel)
    {
        if ($action == 'index') {
            $toolbarButtons['back']['label'] = '<i class="fa kd-back"></i>';
            $toolbarButtons['back']['attr']['title'] = __('Back');
            $toolbarButtons['back']['url']['plugin'] = 'Institution';
            $toolbarButtons['back']['url']['controller'] = 'Institutions';
            $toolbarButtons['back']['url']['action'] = 'Students';
            $toolbarButtons['back']['url'][0] = 'index';
            $toolbarButtons['back']['attr'] = $attr;
        }
        if ($action == 'edit') {
            $toolbarButtons['back']['url'][0] = 'index';
            if ($toolbarButtons['back']['url']['controller']=='Dashboard') {
                $toolbarButtons['back']['url']['action']= 'index';
                unset($toolbarButtons['back']['url'][0]);
            }
            unset($toolbarButtons['back']['url'][1]);
        } else if ($action == 'view') {
            if ($this->request->data[$this->alias()]['status_id'] != self::NEW_REQUEST && isset($toolbarButtons['edit'])) {
                unset($toolbarButtons['edit']);
            }
        }
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        $newItem = [];
        $status_id = $this->get($entity->id)->status_id;
        if ($status_id == self::NEW_REQUEST) {
            if (isset($buttons['view'])) {
                $newItem['view'] = $buttons['view'];
            }
            if ($this->AccessControl->check(['Institutions', $this->alias(), 'edit'])) {
                if (isset($buttons['edit'])) {
                    $newItem['edit'] = $buttons['edit'];
                }
            }
        } else {
            if (isset($buttons['view'])) {
                $newItem['view'] = $buttons['view'];
            }
        }
        $buttons = $newItem;
        return $buttons;
    }

    public function editBeforeSave(Event $event, Entity $entity, ArrayObject $data)
    {
        $process = function ($model, $entity) use ($data) {
            if (!empty($entity->errors())) {
                 return false;
            }

            $entity->comment = $data[$this->alias()]['comment'];
            $effectiveDate = strtotime($data[$this->alias()]['effective_date']);
            $Students = TableRegistry::get('Institution.Students');
            $StudentStatuses = TableRegistry::get('Student.StudentStatuses');
            $statuses = $StudentStatuses->findCodeList();

            $institutionId = $entity->institution_id;
            $studentId = $entity->student_id;
            $periodId = $entity->academic_period_id;
            $gradeId = $entity->education_grade_id;

            $conditions = [
                'institution_id' => $institutionId,
                'student_id' => $studentId,
                'academic_period_id' => $periodId,
                'education_grade_id' => $gradeId,
                'student_status_id' => $statuses['WITHDRAWN']
            ];

            $newData = $conditions;

            // If the student is not already drop out
            if (!$Students->exists($conditions)) {
                // Change the status_id of the student in the school
                // Update only enrolled statuses student
                $existingStudentEntity = $Students->find()->where([
                        $Students->aliasField('institution_id') => $institutionId,
                        $Students->aliasField('student_id') => $studentId,
                        $Students->aliasField('academic_period_id') => $periodId,
                        $Students->aliasField('education_grade_id') => $gradeId,
                        $Students->aliasField('student_status_id') => $statuses['CURRENT']
                    ])
                    ->first();

                if (!empty($existingStudentEntity)) {
                    // approval should not proceed
                    $existingStudentEntity->student_status_id = $statuses['WITHDRAWN'];
                    $existingStudentEntity->end_date = $effectiveDate;
                    $result = $Students->save($existingStudentEntity);

                    if ($result) {
                        $entity->status_id = self::APPROVED;
                        $entity->effective_date = date('Y-m-d', $effectiveDate);
                        if ($this->save($entity)) {
                            return true;
                        }
                    }
                }
            }
            return false;
        };
        return $process;
    }

    public function editAfterSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $this->Alert->success('StudentWithdraw.approve', ['reset' => true]);
        // To redirect back to the student withdraw if it is not access from the workbench
        $urlParams = $this->ControllerAction->url('index');
        $plugin = false;
        $controller = 'Dashboard';
        $action = 'index';
        if ($urlParams['controller'] == 'Institutions') {
            $plugin = 'Institution';
            $controller = 'Institutions';
            $action = 'StudentWithdraw';
        }
        $event->stopPropagation();
        return $this->controller->redirect(['plugin' => $plugin, 'controller' => $controller, 'action' => $action]);
    }

    public function editOnReject(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $this->updateAll(
            ['status_id' => self::REJECTED, 'comment' => $data[$this->alias()]['comment'], 'effective_date' => strtotime($data[$this->alias()]['effective_date'])],
            ['id' => $entity->id]);

        $this->Alert->success('StudentWithdraw.reject');

        // To redirect back to the student admission if it is not access from the workbench
        $urlParams = $this->ControllerAction->url('index');
        $plugin = false;
        $controller = 'Dashboard';
        $action = 'index';
        if ($urlParams['controller'] == 'Institutions') {
            $plugin = 'Institution';
            $controller = 'Institutions';
            $action = $this->alias();
        }

        $event->stopPropagation();
        return $this->controller->redirect(['plugin' => $plugin, 'controller' => $controller, 'action' => $action]);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        $validator->add('effective_date', 'ruleDateAfterEnrollment', [
                    'rule' => ['dateAfterEnrollment'],
                    'provider' => 'table'
                    ]);
        return $validator;
    }

    public function findWorkbench(Query $query, array $options)
    {
        $controller = $options['_controller'];
        $controller->loadComponent('AccessControl');

        $session = $controller->request->session();
        $AccessControl = $controller->AccessControl;

        $isAdmin = $session->read('Auth.User.super_admin');
        $userId = $session->read('Auth.User.id');

        $where = [$this->aliasField('status_id') => self::NEW_REQUEST];

        if (!$isAdmin) {
            if ($AccessControl->check(['Institutions', $this->alias(), 'edit'])) {
                $SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
                $institutionIds = $SecurityGroupUsers->getInstitutionsByUser($userId);

                if (empty($institutionIds)) {
                    // return empty list if the user does not have access to any schools
                    return $query->where([$this->aliasField('id') => -1]);
                } else {
                    $where[$this->aliasField('institution_id') . ' IN '] = $institutionIds;
                }
            } else {
                // return empty list if the user does not permission to approve Student Admission
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
                        'action' => $this->alias(),
                        'edit',
                        $this->paramsEncode(['id' => $row->id])
                    ];

                    if (is_null($row->modified)) {
                        $receivedDate = $this->formatDate($row->created);
                    } else {
                        $receivedDate = $this->formatDate($row->modified);
                    }

                    $row['url'] = $url;
                    $row['status_id'] = __('Pending For Approval');
                    $row['request_title'] = sprintf(__('Withdraw request of %s'), $row->user->name_with_id);
                    $row['institution'] = $row->institution->code_name;
                    $row['received_date'] = $receivedDate;
                    $row['requester'] = $row->created_user->name_with_id;

                    return $row;
                });
            });

        return $query;
    }

    public function getPendingRecords($institutionId = null)
    {
        $count = $this
            ->find()
            ->where([
                $this->aliasField('status_id') => self::NEW_REQUEST,
                $this->aliasField('institution_id') => $institutionId,
            ])
            ->count()
        ;

        return $count;
    }
}
