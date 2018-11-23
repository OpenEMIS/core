<?php
namespace Staff\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\ResultSet;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;
use Cake\Log\Log;

class LeaveTable extends ControllerActionTable
{
    use OptionsTrait;
    public function initialize(array $config)
    {
        $this->table('institution_staff_leave');
        parent::initialize($config);

        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('StaffLeaveTypes', ['className' => 'Staff.StaffLeaveTypes']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);
        $this->addBehavior('ControllerAction.FileUpload', [
            // 'name' => 'file_name',
            // 'content' => 'file_content',
            'size' => '10MB',
            'contentEditable' => true,
            'allowable_file_types' => 'all',
            'useDefaultName' => true
        ]);
        $this->addBehavior('Historial.Historial', [
                'historialUrl' => [
                    'plugin' => 'Directory',
                    'controller' => 'Directories',
                    'action' => 'HistoricalStaffLeave',
                ],
                'model' => 'Staff.HistoricalStaffLeave',
                'allowedController' => ['Directories']
            ]
        );
        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Behavior.Historial.index.beforeQuery'] = 'indexHistorialBeforeQuery';
        return $events;
    }

    public function indexHistorialBeforeQuery(Event $event, Query $mainQuery, Query $historialQuery, ArrayObject $selectList, ArrayObject $defaultOrder, ArrayObject $extra)
    {
        $session = $this->request->session();

        if ($session->check('Directory.Directories.id')) {
            $extra['auto_contain'] = false;
            $userId = $session->read('Directory.Directories.id');

            $select = [
                $this->aliasField('id'),
                $this->aliasField('is_historial'),
                $this->aliasField('date_from'),
                $this->aliasField('date_to')
            ];
            $selectList->exchangeArray($select);

            $order = ['date_from' => 'ASC'];
            $defaultOrder->exchangeArray($order);

            $mainQuery
                ->select([
                    'id' => $this->aliasField('id'),
                    'date_from' => $this->aliasField('date_from'),
                    'date_to' => $this->aliasField('date_to'),
                    'start_time' => $this->aliasField('start_time'),
                    'end_time' => $this->aliasField('end_time'),
                    'full_day' => $this->aliasField('full_day'),
                    'comments' => $this->aliasField('comments'),
                    'staff_id' =>$this->aliasField('staff_id'),
                    'staff_leave_type_id' =>$this->aliasField('staff_leave_type_id'),
                    'assignee_id' =>$this->aliasField('assignee_id'),
                    'academic_period_id' =>$this->aliasField('academic_period_id'),
                    'status_id' => $this->aliasField('status_id'),
                    'number_of_days' => $this->aliasField('number_of_days'),
                    'institution_id' => $this->aliasField('institution_id'),
                    $this->Institutions->aliasField('id'),
                    $this->Institutions->aliasField('code'),
                    $this->Institutions->aliasField('name'),
                    $this->AcademicPeriods->aliasField('name'),
                    $this->StaffLeaveTypes->aliasField('id'),
                    $this->StaffLeaveTypes->aliasField('name'),
                    $this->Statuses->aliasField('id'),
                    $this->Statuses->aliasField('name'),
                    'is_historial' => 0
                ], true)
                ->contain([
                    'Institutions',
                    'AcademicPeriods',
                    'StaffLeaveTypes',
                    'Users',
                    'Statuses'
                ])
                ->where([
                    $this->aliasField('staff_id') => $userId
                ]);

            $HistorialTable = $historialQuery->repository();
            $historialQuery
                ->select([
                    'id' => $HistorialTable->aliasField('id'),
                    'date_from' => $HistorialTable->aliasField('date_from'),
                    'date_to' => $HistorialTable->aliasField('date_to'),
                    'start_time' => $HistorialTable->aliasField('start_time'),
                    'end_time' => $HistorialTable->aliasField('end_time'),
                    'full_day' => $HistorialTable->aliasField('full_day'),
                    'comments' => $HistorialTable->aliasField('comments'),
                    'staff_id' => $HistorialTable->aliasField('staff_id'),
                    'staff_leave_type_id' => $HistorialTable->aliasField('staff_leave_type_id'),
                    'assignee_id' => '(null)',
                    'leave_academic_period_id' => '(null)',
                    'status_id' => '(null)',
                    'number_of_days' => $HistorialTable->aliasField('number_of_days'),
                    'leave_institution_id' => '(null)',
                    'institution_id' => '(null)',
                    'institution_code' => '(null)',
                    'institution_name' => $HistorialTable->aliasField('institution_name'),
                    'academic_period_id' =>  '(null)',
                    'leave_type_id' => 'StaffLeaveTypes.id',
                    'leave_type_name' => 'StaffLeaveTypes.name',
                    'statuses_id' => '(null)',
                    'statuses_name' => '(null)',
                    'is_historial' => 1
                ])
                ->contain([
                    'Users',
                    'StaffLeaveTypes',
                ])
                ->where([
                    $HistorialTable->aliasField('staff_id') => $userId
                ]);
        }
    }

    public function onGetInstitutionId(Event $event, Entity $entity)
    {
        $rowEntity = $this->getFieldEntity($entity->is_historial, $entity->id, 'institution');

        if ($entity->is_historial) {
            return $rowEntity->name;
        } else {
            return $rowEntity->code_name;
        }
    }

    public function onGetStaffLeaveTypeId(Event $event, Entity $entity)
    {
        $rowEntity = $this->getFieldEntity($entity->is_historial, $entity->id, 'staff_leave_type');
        return $rowEntity->name;
    }

    public function onGetDateTo(Event $event, Entity $entity)
    {
        return $entity->date_to;
    }

    public function onGetDateFrom(Event $event, Entity $entity)
    {
        return $entity->date_from;
    }

    public function onGetNumberOfDays(Event $event, Entity $entity)
    {
        $rowEntity = $this->getFieldEntity($entity->is_historial, $entity->id, 'number_of_days');
        return $rowEntity;
    }

    public function onGetComments(Event $event, Entity $entity)
    {
        $rowEntity = $this->getFieldEntity($entity->is_historial, $entity->id, 'comments');
        return $rowEntity;
    }

    public function onGetStartTime(Event $event, Entity $entity)
    {
        $rowEntity = $this->getFieldEntity($entity->is_historial, $entity->id, 'start_time');
        return $rowEntity;
    }

    public function onGetEndTime(Event $event, Entity $entity)
    {
        $rowEntity = $this->getFieldEntity($entity->is_historial, $entity->id, 'end_time');
        return $rowEntity;
    }

    public function onGetFullDay(Event $event, Entity $entity)
    {
        $isFullDay = $this->getFieldEntity($entity->is_historial, $entity->id, 'full_day');
        return $this->getSelectOptions('general.yesno')[$isFullDay];
    }

    public function onGetAcademicPeriodId(Event $event, Entity $entity)
    {
        $rowEntity = $this->getFieldEntity($entity->is_historial, $entity->id, 'academic_period');
        return isset($rowEntity->name) ? $rowEntity->name : null;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', [
            'visible' => ['index' => false, 'view' => true]
        ]);
        $this->field('assignee_id', ['visible' => false]);
        $this->field('status_id', ['visible' => false]);

        $this->setFieldOrder(['institution_id', 'staff_leave_type_id', 'date_from', 'date_to', 'number_of_days', 'comments', 'file_name', 'file_content']);
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
        Log::write('debug', $entity);
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        if (array_key_exists('view', $buttons)) {
            if ($entity->is_historial) {
                $rowEntityId = $this->getFieldEntity($entity->is_historial, $entity->id, 'id');
                $url = [
                    'plugin' => 'Directory',
                    'controller' => 'Directories',
                    'action' => 'HistoricalStaffLeave',
                    'view',
                    $this->paramsEncode(['id' => $rowEntityId])
                ];
            } else {
                $rowEntity = $this->getFieldEntity($entity->is_historial, $entity->id, 'institution');
                $institutionId = $rowEntity->id;
                // $institutionId = $entity->institution->id
                $url = [
                    'plugin' => 'Directory',
                    'controller' => 'Directories',
                    'action' => 'StaffLeave',
                    'view',
                    $this->paramsEncode(['id' => $entity->id]),
                    'institution_id' => $institutionId,
                ];
            }

            $buttons['view']['url'] = $url;
        }

        return $buttons;
    }

    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $options = ['type' => 'staff'];
        $tabElements = $this->controller->getCareerTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }
}
