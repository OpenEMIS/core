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
use App\Model\Table\ControllerActionTable;

class StudentWithdrawTable extends ControllerActionTable
{
    // Workflow Steps - category
    const TO_DO = 1;
    const IN_PROGRESS = 2;
    const DONE = 3;

    private $workflowEvents = [
        [
            'value' => 'Workflow.onApproval',
            'text' => 'Approval of Withdrawal Request',
            'description' => 'Performing this action will apply the proposed changes to the student record.',
            'method' => 'OnApproval'
        ],
        [
            'value' => 'Workflow.onCancel',
            'text' => 'Cancellation of Withdrawal Request',
            'description' => 'Performing this action will set student back to enrolled status',
            'method' => 'onCancel'
        ]
    ];

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
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);

        $this->addBehavior('Workflow.Workflow');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Dashboard' => ['index']
        ]);

        $this->toggle('add', false);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
        $events['Workflow.getEvents'] = 'getWorkflowEvents';
        $events['Model.Students.afterDelete'] = 'studentsAfterDelete';

        foreach ($this->workflowEvents as $event) {
            $events[$event['value']] = $event['method'];
        }
        return $events;
    }

    public function getWorkflowEvents(Event $event, ArrayObject $eventsObject)
    {
        foreach ($this->workflowEvents as $key => $attr) {
            $attr['text'] = __($attr['text']);
            $attr['description'] = __($attr['description']);
            $eventsObject[] = $attr;
        }
    }

    public function studentsAfterDelete(Event $event, Entity $student)
    {
        $this->removePendingWithdraw($student->student_id, $student->institution_id);
    }

    protected function removePendingWithdraw($studentId, $institutionId)
    {
        //could not include grade / academic period because not always valid. (promotion/graduation/repeat and withdraw can be done on different grade / academic period)
        $pendingStatus = TableRegistry::get('Workflow.WorkflowModels')->getWorkflowStatusSteps('Institution.StudentWithdraw', 'PENDING');

        $conditions = [
            'student_id' => $studentId,
            'institution_id' => $institutionId,
            'status_id IN ' => $pendingStatus //pending status_id
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

    public function onApproval(Event $event, $id, Entity $workflowTransitionEntity)
    {
        $entity = $this->get($id);

        $Students = TableRegistry::get('Institution.Students');
        $StudentStatuses = TableRegistry::get('Student.StudentStatuses');
        $statuses = $StudentStatuses->findCodeList();
        $institutionId = $entity->institution_id;
        $studentId = $entity->student_id;
        $periodId = $entity->academic_period_id;
        $gradeId = $entity->education_grade_id;

        $existingStudentEntity = $Students->find()->where([
            $Students->aliasField('institution_id') => $institutionId,
            $Students->aliasField('student_id') => $studentId,
            $Students->aliasField('academic_period_id') => $periodId,
            $Students->aliasField('education_grade_id') => $gradeId,
            $Students->aliasField('student_status_id') => $statuses['CURRENT']
        ])
        ->first();

        if ($existingStudentEntity) {
            $existingStudentEntity->student_status_id = $statuses['WITHDRAWN'];
            $Students->save($existingStudentEntity);
        }
    }

    public function onCancel(Event $event, $id, Entity $workflowTransitionEntity)
    {
        $entity = $this->get($id);

        $Students = TableRegistry::get('Institution.Students');
        $StudentStatuses = TableRegistry::get('Student.StudentStatuses');
        $statuses = $StudentStatuses->findCodeList();
        $institutionId = $entity->institution_id;
        $studentId = $entity->student_id;
        $periodId = $entity->academic_period_id;
        $gradeId = $entity->education_grade_id;

        $existingStudentEntity = $Students->find()->where([
            $Students->aliasField('institution_id') => $institutionId,
            $Students->aliasField('student_id') => $studentId,
            $Students->aliasField('academic_period_id') => $periodId,
            $Students->aliasField('education_grade_id') => $gradeId,
            $Students->aliasField('student_status_id') => $statuses['WITHDRAWN']
        ])
        ->first();

        if ($existingStudentEntity) {
            $existingStudentEntity->student_status_id = $statuses['CURRENT'];
            $Students->save($existingStudentEntity);
        }
    }

    public function editOnInitialize(Event $event, Entity $entity)
    {
        $this->request->data[$this->alias()]['status_id'] = $entity->status_id;
        $this->request->data[$this->alias()]['effective_date'] = $entity->start_date;
    }

    public function afterAction($event, ArrayObject $extra)
    {
        $this->field('effective_date', ['visible' => ['edit' => true, 'index' => false, 'view' => true]]);
        $this->field('comment', ['visible' => ['index' => false, 'edit' => true, 'view' => true]]);
        $this->field('student_id');
        $this->field('status_id');
        $this->field('institution_id', ['visible' => ['index' => false, 'edit' => true, 'view' => 'true']]);
        $this->field('academic_period_id', ['type' => 'readonly']);
        $this->field('education_grade_id');
        $this->field('created', ['visible' => ['index' => false, 'edit' => true, 'view' => true]]);

        $this->setFieldOrder([
            'created', 'status_id', 'student_id',
            'institution_id', 'academic_period_id', 'education_grade_id',
            'effective_date', 'student_withdraw_reason_id', 'comment'
        ]);

        $toolbarButtons = $extra['toolbarButtons'];

        if ($this->action == 'index') {
            $attr = [
                'class' => 'btn btn-xs btn-default',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'title' => __('Back')
            ];
            $toolbarButtons['back']['label'] = '<i class="fa kd-back"></i>';
            $toolbarButtons['back']['attr']['title'] = __('Back');
            $toolbarButtons['back']['url']['plugin'] = 'Institution';
            $toolbarButtons['back']['url']['controller'] = 'Institutions';
            $toolbarButtons['back']['url']['action'] = 'Students';
            $toolbarButtons['back']['url'][0] = 'index';
            $toolbarButtons['back']['attr'] = $attr;
        }
        if ($this->action == 'edit') {
            $toolbarButtons['back']['url'][0] = 'index';
            if ($toolbarButtons['back']['url']['controller']=='Dashboard') {
                $toolbarButtons['back']['url']['action']= 'index';
                unset($toolbarButtons['back']['url'][0]);
            }
            unset($toolbarButtons['back']['url'][1]);
        }
    }

    public function editAfterAction($event, Entity $entity)
    {
        $this->field('effective_date', ['attr' => ['entity' => $entity]]);
        $this->field('student_id', ['type' => 'readonly', 'attr' => ['value' => $this->Users->get($entity->student_id)->name_with_id]]);
        $this->field('institution_id', ['type' => 'readonly', 'attr' => ['value' => $this->Institutions->get($entity->institution_id)->code_name]]);
        $this->field('academic_period_id', ['type' => 'readonly', 'attr' => ['value' => $this->AcademicPeriods->get($entity->academic_period_id)->name]]);
        $this->field('education_grade_id', ['type' => 'readonly', 'attr' => ['value' => $this->EducationGrades->get($entity->education_grade_id)->programme_grade_name]]);
        $this->field('student_withdraw_reason_id', ['type' => 'select']);
        $this->field('created', ['type' => 'disabled', 'attr' => ['value' => $this->formatDate($entity->created)]]);
        $this->setFieldOrder([
            'created', 'status_id', 'student_id',
            'institution_id', 'academic_period_id', 'education_grade_id',
            'effective_date', 'student_withdraw_reason_id', 'comment',
        ]);

        $urlParams = $this->url('edit');
        if ($urlParams['controller'] == 'Dashboard') {
            $this->Navigation->addCrumb('Withdraw Approvals', $urlParams);
        }
    }

    public function viewAfterAction($event, Entity $entity)
    {
        $this->request->data[$this->alias()]['status_id'] = $entity->status_id;
        $this->field('student_withdraw_reason_id', ['type' => 'readonly', 'attr' => ['value' => $this->StudentWithdrawReasons->get($entity->student_withdraw_reason_id)->name]]);
        $this->setFieldOrder([
            'created', 'status_id', 'student_id',
            'institution_id', 'academic_period_id', 'education_grade_id',
            'effective_date', 'student_withdraw_reason_id', 'comment'
        ]);
    }

    public function onUpdateFieldEffectiveDate(Event $event, array $attr, $action, $request)
    {
        if ($action == 'edit') {
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
        $session = $controller->request->session();

        $userId = $session->read('Auth.User.id');
        $Statuses = $this->Statuses;
        $doneStatus = self::DONE;

        $query
            ->select([
                $this->aliasField('id'),
                $this->aliasField('institution_id'),
                $this->aliasField('modified'),
                $this->aliasField('created'),
                $this->Statuses->aliasField('name'),
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
            ->matching($this->Statuses->alias(), function ($q) use ($Statuses, $doneStatus) {
                return $q->where([$Statuses->aliasField('category <> ') => $doneStatus]);
            })
            ->where([$this->aliasField('assignee_id') => $userId])
            ->order([$this->aliasField('created') => 'DESC'])
            ->formatResults(function (ResultSetInterface $results) {
                return $results->map(function ($row) {
                    $url = [
                        'plugin' => 'Institution',
                        'controller' => 'Institutions',
                        'action' => 'StudentWithdraw',
                        'view',
                        $this->paramsEncode(['id' => $row->id]),
                        'institution_id' => $row->institution_id
                    ];

                    if (is_null($row->modified)) {
                        $receivedDate = $this->formatDate($row->created);
                    } else {
                        $receivedDate = $this->formatDate($row->modified);
                    }
                    $row['url'] = $url;
                    $row['status'] = __($row->_matchingData['Statuses']->name);
                    $row['request_title'] = sprintf(__('Withdraw request of %s'), $row->user->name_with_id);
                    $row['institution'] = $row->institution->code_name;
                    $row['received_date'] = $receivedDate;
                    $row['requester'] = $row->created_user->name_with_id;

                    return $row;
                });
            });

        return $query;
    }
}
