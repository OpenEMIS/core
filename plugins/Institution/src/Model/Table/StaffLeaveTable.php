<?php
namespace Institution\Model\Table;

use ArrayObject;
use DatePeriod;
use DateInterval;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\Datasource\ResultSetInterface;

use Workflow\Model\Table\WorkflowStepsTable as WorkflowSteps;
use App\Model\Table\ControllerActionTable;

class StaffLeaveTable extends ControllerActionTable
{
    // Workflow Steps - category
    const TO_DO = 1;
    const IN_PROGRESS = 2;
    const DONE = 3;

    public function initialize(array $config)
    {
        $this->table('institution_staff_leave');
        parent::initialize($config);

        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('StaffLeaveTypes', ['className' => 'Staff.StaffLeaveTypes']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->addBehavior('ControllerAction.FileUpload', [
            // 'name' => 'file_name',
            // 'content' => 'file_content',
            'size' => '10MB',
            'contentEditable' => true,
            'allowable_file_types' => 'all',
            'useDefaultName' => true
        ]);
        $this->addBehavior('Workflow.Workflow');
        $this->addBehavior('Import.ImportLink', ['import_model' => 'ImportStaffLeave']);
        $this->addBehavior('Institution.InstitutionWorkflowAccessControl');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Dashboard' => ['index']
        ]);

        // POCOR-4047 to get staff profile data
        $this->addBehavior('Institution.StaffProfile');
        $this->fullDayOptions = $this->getSelectOptions('general.yesno');
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('date_to', 'ruleCompareDateReverse', [
                'rule' => ['compareDateReverse', 'date_from', true]
            ])
            ->allowEmpty('file_content');
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.InstitutionStaff.afterDelete'] = 'institutionStaffAfterDelete';
        return $events;
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $dateFrom = date_create($entity->date_from);
        $dateTo = date_create($entity->date_to);
        $dateFromMonth = date('m', strtotime($entity->date_from));
        $dateToMonth = date('m', strtotime($entity->dateTo));

        $staffId = $entity->staff_id;
        $institutionId = $entity->institution_id;
        $academicPeriodId = $entity->academic_period_id;

        $exisitingLeaveRecords = $this
            ->find()
            ->select([
                $this->aliasField('date_from'),
                $this->aliasField('date_to')
            ])
            ->where([
                $this->aliasField('staff_id') => $staffId,
                $this->aliasField('institution_id') => $institutionId,
                $this->aliasField('academic_period_id') => $academicPeriodId,
            ])
            ->toArray();

        $workingDaysOfWeek = $this->AcademicPeriods->getWorkingDaysOfWeek();

        // get date period between 2 date
        $startDate = $dateFrom;
        $endDate = $dateTo;
        $endDate = $endDate->modify('+1 day');
        $interval = new DateInterval('P1D');
        $datePeriod = new DatePeriod($startDate, $interval, $endDate);

        //Leave period applied must not overlap any exisiting leave records
        $count = 0;
        $overlap = false;
        foreach ($datePeriod as $key => $date) {
            $dayText = $date->format('l');
            if (in_array($dayText, $workingDaysOfWeek)) {
                $count++;
                foreach ($exisitingLeaveRecords as $key => $value) {
                    $dateFromStr = $value->date_from->format("Y-m-d");
                    $dateToStr = $value->date_to->format("Y-m-d");
                    $comparisonDateStr = $date->format("Y-m-d");
                    $overlap = $this->checkDateInRange($dateFromStr, $dateToStr, $comparisonDateStr);
                    if ($overlap) {
                        break;
                    }
                }
            }
            if ($overlap) {
                break;
            }
        }

        if ($overlap) {
            // Error message to tell that leave period applied has overlapped exisiting leave records.
            $this->Alert->error('AlertRules.StaffLeave.leavePeriodOverlap', ['reset' => true]);
            return false;
        } else {
            //The number of leave days calculation only includes working day
            $entity->number_of_days = $count;
        }
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        if (in_array($this->action, ['view', 'edit', 'delete'])) {
            $modelAlias = 'Staff Leave';
            $userType = 'StaffUser';
            $this->controller->changeUserHeader($this, $modelAlias, $userType);
        }

        $this->field('number_of_days', [
            'visible' => ['index' => true, 'view' => true, 'edit' => false, 'add' => false]
        ]);
        $this->field('file_name', [
            'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]
        ]);
        $this->field('file_content', [
            'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]
        ]);
        $this->field('full_day', [
            'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]
        ]);
        $this->field('academic_period_id', [
            'visible' => ['index' => false, 'view' => false, 'edit' => true, 'add' => true]
        ]);
        $this->field('start_time', [
            'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]
        ]);
        $this->field('end_time', [
            'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]
        ]);
        $this->field('time', [
            'visible' => ['index' => true, 'view' => false, 'edit' => false, 'add' => false]
        ]);
        $this->field('staff_id', ['type' => 'hidden']);

        $this->setFieldOrder(['staff_leave_type_id', 'date_from', 'date_to', 'time', 'start_time', 'full_day', 'end_time', 'number_of_days', 'comments', 'file_name', 'file_content']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $userId = $this->getUserId();
        $query->where([
            $this->aliasField('staff_id') => $userId
        ]);
    }

    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $this->setupTabElements();
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('staff_leave_type_id');
        $this->field('assignee_id', ['entity' => $entity]); //send entity information
        
        // after $this->field(), field ordering will mess up, so need to reset the field order
        $this->setFieldOrder(['staff_leave_type_id', 'academic_period_id','date_from', 'date_to', 'full_day', 'start_time', 'end_time','number_of_days', 'comments', 'file_name', 'file_content', 'assignee_id']);
    }

    public function onGetTime(Event $event, Entity $entity) {
        if($entity->full_day == 0){
            $start_time = $this->formatTime($entity->start_time);
            $end_time = $this->formatTime($entity->end_time);
            return $start_time .' - '. $end_time;
        }
    }

    public function onGetFullDay(Event $event, Entity $entity)
    {
        return $this->fullDayOptions[$entity->full_day];
    }

    public function onUpdateFieldFileName(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'view') {
            $attr['type'] = 'hidden';
        } else if ($action == 'add' || $action == 'edit') {
            $attr['type'] = 'hidden';
        }

        return $attr;
    }

    public function onUpdateFieldStaffId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $userId = $this->getUserId();

            $attr['value'] = $userId;
        }

        return $attr;
    }

    public function onUpdateFieldStaffLeaveTypeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $attr['type'] = 'select';
            $attr['onChangeReload'] = true;
        }

        return $attr;
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $periodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
            $attr['type'] = 'select';
            $attr['options'] = $periodOptions;
        }
        return $attr;
    }

    public function onUpdateFieldFullDay(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            // $attr['type'] = 'select';
            $attr['select'] = false;
            $attr['options'] = $this->fullDayOptions;
            $attr['onChangeReload'] = true;
        }
        return $attr;
    }

    public function onUpdateFieldStartTime(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            if (isset($request->data[$this->alias()]['full_day'])) {
                if ($request->data[$this->alias()]['full_day'] == 1) {
                    $attr['type'] = 'hidden';
                }
            } else {
                $attr['type'] = 'hidden';
            }
            return $attr;
        }
    }

    public function onUpdateFieldEndTime(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            if (isset($request->data[$this->alias()]['full_day'])) {
                if ($request->data[$this->alias()]['full_day'] == 1) {
                    $attr['type'] = 'hidden';
                }
            } else {
                $attr['type'] = 'hidden';
            }
            return $attr;
        }
    }

    private function setupTabElements()
    {
        $options['type'] = 'staff';
        $userId = $this->getUserId();
        if (!is_null($userId)) {
            $options['user_id'] = $userId;
        }

        $tabElements = $this->controller->getCareerTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }

    public function getUserId()
    {
        $session = $this->request->session();
        if ($session->check('Staff.Staff.id')) {
            $userId = $session->read('Staff.Staff.id');
            return $userId;
        }

        return null;
    }

    public function institutionStaffAfterDelete(Event $event, Entity $institutionStaffEntity)
    {
        $staffLeaveData = $this->find()
            ->where([
                $this->aliasField('staff_id') => $institutionStaffEntity->staff_id,
                $this->aliasField('institution_id') => $institutionStaffEntity->institution_id,
            ])
            ->toArray();

        foreach ($staffLeaveData as $key => $staffLeaveEntity) {
            $this->delete($staffLeaveEntity);
        }
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
                $this->aliasField('status_id'),
                $this->aliasField('staff_id'),
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
                $this->StaffLeaveTypes->aliasField('name'),
                $this->Institutions->aliasField('code'),
                $this->Institutions->aliasField('name'),
                $this->CreatedUser->aliasField('openemis_no'),
                $this->CreatedUser->aliasField('first_name'),
                $this->CreatedUser->aliasField('middle_name'),
                $this->CreatedUser->aliasField('third_name'),
                $this->CreatedUser->aliasField('last_name'),
                $this->CreatedUser->aliasField('preferred_name')
            ])
            ->contain([$this->Users->alias(), $this->StaffLeaveTypes->alias(), $this->Institutions->alias(), $this->CreatedUser->alias()])
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
                        'action' => 'StaffLeave',
                        'view',
                        $this->paramsEncode(['id' => $row->id]),
                        'user_id' => $row->staff_id,
                        'institution_id' => $row->institution_id
                    ];

                    if (is_null($row->modified)) {
                        $receivedDate = $this->formatDate($row->created);
                    } else {
                        $receivedDate = $this->formatDate($row->modified);
                    }

                    $row['url'] = $url;
                    $row['status'] = __($row->_matchingData['Statuses']->name);
                    $row['request_title'] = sprintf(__('%s of %s'), $row->staff_leave_type->name, $row->user->name_with_id);
                    $row['institution'] = $row->institution->code_name;
                    $row['received_date'] = $receivedDate;
                    $row['requester'] = $row->created_user->name_with_id;

                    return $row;
                });
            });

        return $query;
    }

    public function getModelAlertData($threshold)
    {
        $thresholdArray = json_decode($threshold, true);

        $conditions = [
            1 => ('DATEDIFF(' . $this->aliasField('date_to') . ', NOW())' . ' BETWEEN 0 AND ' . $thresholdArray['value']), // before
            // 2 => ('DATEDIFF(NOW(), ' . $this->aliasField('date_to') . ')' . ' BETWEEN 0 AND ' . $thresholdArray['value']), // after
        ];

        // will do the comparison with threshold when retrieving the absence data
        $licenseData = $this->find()
            ->select([
                'StaffLeaveTypes.name',
                'date_from',
                'date_to',
                'Institutions.id',
                'Institutions.name',
                'Institutions.code',
                'Institutions.address',
                'Institutions.postal_code',
                'Institutions.contact_person',
                'Institutions.telephone',
                'Institutions.fax',
                'Institutions.email',
                'Institutions.website',
                'Users.id',
                'Users.openemis_no',
                'Users.first_name',
                'Users.middle_name',
                'Users.third_name',
                'Users.last_name',
                'Users.preferred_name',
                'Users.email',
                'Users.address',
                'Users.postal_code',
                'Users.date_of_birth'
            ])
            ->contain(['Statuses', 'Users', 'Institutions', 'StaffLeaveTypes', 'Assignees'])
            ->where([
                $this->aliasField('staff_leave_type_id') => $thresholdArray['staff_leave_type'],
                $this->aliasField('date_to') . ' IS NOT NULL',
                $conditions[$thresholdArray['condition']]
            ])
            ->hydrate(false)
            ;

        return $licenseData->toArray();
    }

    private function checkDateInRange($start_date, $end_date, $comparison_date)
    {
        return (($comparison_date >= $start_date) && ($comparison_date <= $end_date));
    }
}
