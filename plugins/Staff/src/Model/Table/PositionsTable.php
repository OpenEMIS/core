<?php
namespace Staff\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
use Cake\ORM\TableRegistry;

use App\Model\Traits\MessagesTrait;
use App\Model\Table\ControllerActionTable;

class PositionsTable extends ControllerActionTable {
    use MessagesTrait;

    public function initialize(array $config) {
        $this->table('institution_staff');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('StaffTypes', ['className' => 'Staff.StaffTypes']);
        $this->belongsTo('StaffStatuses', ['className' => 'Staff.StaffStatuses']);
        $this->belongsTo('InstitutionPositions', ['className' => 'Institution.InstitutionPositions', 'foreignKey' => 'institution_position_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('SecurityGroupUsers', ['className' => 'Security.SecurityGroupUsers']);

        $this->addBehavior('Historical.Historical', [
            'historicalUrl' => [
                'plugin' => 'Directory',
                'controller' => 'Directories',
                'action' => 'HistoricalStaffPositions'
            ],
            'originUrl' => [
                'action' => 'StaffPosition',
                'type' => 'staff'
            ],
            'model' => 'Historical.HistoricalStaffPositions',
            'allowedController' => ['Directories']
        ]);

        $this->addBehavior('Excel', [
            'pages' => ['index'],
            'autoFields' => false,
            'auto_contain' => false
        ]);

        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Behavior.Historical.index.beforeQuery'] = 'indexHistoricalBeforeQuery';
        return $events;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        if ($this->controller->name !== 'Directories') {
            $this->removeBehavior('Excel');

            if (array_key_exists('export', $extra['toolbarButtons'])) {
                unset($extra['toolbarButtons']['export']);
            }
        }
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $this->dispatchEvent('Excel.Historical.beforeQuery', [$query, $settings], $this);
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $newFields = [];

        $newFields[] = [
            'key' => 'InstitutionStaffPositions.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('OpenEMIS ID')
        ];

        $newFields[] = [
            'key' => 'InstitutionStaffPositions.name',
            'field' => 'name',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'InstitutionStaffPositions.institution',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'InstitutionStaffPositions.position',
            'field' => 'position_name',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'InstitutionStaffPositions.staff_type_id',
            'field' => 'staff_type_id',
            'type' => 'integer',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'InstitutionStaffPositions.start_date',
            'field' => 'start_date',
            'type' => 'date',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'InstitutionStaffPositions.end_date',
            'field' => 'end_date',
            'type' => 'date',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'InstitutionStaffPositions.staff_status_id',
            'field' => 'staff_status_id',
            'type' => 'integer',
            'label' => ''
        ];

        $fields->exchangeArray($newFields);
    }

    public function onExcelGetName(Event $event, Entity $entity)
    {
        $rowEntity = $this->getFieldEntity($entity->is_historical, $entity->id, 'user');
        return $rowEntity->name;
    }

    public function onExcelGetOpenemisNo(Event $event, Entity $entity)
    {
        $rowEntity = $this->getFieldEntity($entity->is_historical, $entity->id, 'user');
        return $rowEntity->openemis_no;
    }

    public function onExcelGetInstitutionName(Event $event, Entity $entity)
    {
        $rowEntity = $this->getFieldEntity($entity->is_historical, $entity->id, 'institution');
        return $rowEntity->code_name;
    }

    public function onExcelGetPositionName(Event $event, Entity $entity)
    {
        $rowEntity = $this->getFieldEntity($entity->is_historical, $entity->id, 'institution_position');
        if ($entity->is_historical) {
            return $rowEntity->position_no;
        } else {
            return $rowEntity->name;
        }
    }

    public function onExcelGetStaffTypeId(Event $event, Entity $entity)
    {
        $rowEntity = $this->getFieldEntity($entity->is_historical, $entity->id, 'staff_type');
        return $rowEntity->name;
    }

    public function onExcelGetStaffStatusId(Event $event, Entity $entity)
    {
        $rowEntity = $this->getFieldEntity($entity->is_historical, $entity->id, 'staff_status');
        return $rowEntity->name;
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra) 
    {
        $this->fields['start_year']['visible'] = false;
        $this->fields['end_year']['visible'] = false;
        $this->fields['FTE']['visible'] = false;
        $this->fields['security_group_user_id']['visible'] = false;

        $this->setFieldOrder([
            'institution_id',
            'institution_position_id',
            'staff_type_id',
            'start_date',
            'end_date',
            'staff_status_id'
        ]);
    }

    public function indexHistoricalBeforeQuery(Event $event, Query $mainQuery, Query $historicalQuery, ArrayObject $selectList, ArrayObject $defaultOrder, ArrayObject $extra)
    {
        $session = $this->request->session();

        switch ($this->controller->name) {
            case 'Directories':
                $sessionKey = 'Directory.Directories.id';
                $userId = $session->read($sessionKey);
                break;
            case 'Staff':
                $sessionKey = 'Staff.Staff.id';
                $userId = $session->read($sessionKey);
                break;
            case 'Profiles':
                $userId = $this->Auth->user('id');
                break;
            default:
                $userId = null;
                break;
        }

        if (!is_null($userId)) {
            $extra['auto_contain'] = false;

            $select = [
                $this->aliasField('id'),
                $this->aliasField('is_historical'),
                $this->aliasField('start_date'),
                $this->aliasField('end_date')
            ];
            $selectList->exchangeArray($select);

            $order = ['start_date' => 'DESC'];
            $defaultOrder->exchangeArray($order);

            $mainQuery
                ->select([
                    'id' => $this->aliasField('id'),
                    'start_date' => $this->aliasField('start_date'),
                    'end_date' => $this->aliasField('end_date'),
                    $this->aliasField('institution_id'),
                    $this->Institutions->aliasField('id'),
                    $this->Institutions->aliasField('code'),
                    $this->Institutions->aliasField('name'),
                    $this->InstitutionPositions->aliasField('id'),
                    $this->InstitutionPositions->aliasField('position_no'),
                    $this->InstitutionPositions->aliasField('staff_position_title_id'),
                    $this->StaffTypes->aliasField('id'),
                    $this->StaffTypes->aliasField('name'),
                    $this->StaffStatuses->aliasField('id'),
                    $this->StaffStatuses->aliasField('name'),
                    $this->Users->aliasField('id'),
                    $this->Users->aliasField('first_name'),
                    $this->Users->aliasField('middle_name'),
                    $this->Users->aliasField('third_name'),
                    $this->Users->aliasField('last_name'),
                    $this->Users->aliasField('preferred_name'),
                    $this->Users->aliasField('openemis_no'),
                    'is_historical' => 0
                ], true)
                ->contain([
                    $this->Institutions->alias(),
                    $this->InstitutionPositions->alias(),
                    $this->StaffTypes->alias(),
                    $this->Users->alias(),
                    $this->StaffStatuses->alias()
                ])
                ->where([
                    $this->aliasField('staff_id') => $userId
                ]);

            $HistoricalTable = $historicalQuery->repository();
            $historicalQuery
                ->select([
                    'id' => $HistoricalTable->aliasField('id'),
                    'start_date' => $HistoricalTable->aliasField('start_date'),
                    'end_date' => $HistoricalTable->aliasField('end_date'),
                    'position_institution_id' => '(null)',
                    'institution_id' => 'Institutions.id',
                    'institution_code' => 'Institutions.code',
                    'institution_name' => 'Institutions.name',
                    'position_id' => '(null)',
                    'position_name' => 'StaffPositionTitles.name',
                    'staff_position_title_id' => '(null)',
                    'staff_type_id' => 'StaffTypes.id',
                    'staff_type_name' => 'StaffTypes.name',
                    'staff_status_id' => 'StaffStatuses.id',
                    'staff_status_name' => 'StaffStatuses.name',
                    'user_id' => 'Users.id',
                    'user_first_name' => 'Users.first_name',
                    'user_middle_name' => 'Users.middle_name',
                    'user_third_name' => 'Users.third_name',
                    'user_last_name' => 'Users.last_name',
                    'user_preferred_name' => 'Users.preferred_name',
                    'user_openemis_no' => 'Users.openemis_no',
                    'is_historical' => 1
                ])
                ->contain([
                    'StaffTypes',
                    'StaffStatuses',
                    'Users',
                    'Institutions',
                    'StaffPositionTitles'
                ])
                ->where([
                    $HistoricalTable->aliasField('staff_id') => $userId
                ]);
        }
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        if (array_key_exists('view', $buttons)) {
            if ($entity->is_historical) {
                $rowEntityId = $this->getFieldEntity($entity->is_historical, $entity->id, 'id');
                $buttons = $this->getHistoricalActionButtons($buttons, $rowEntityId);
            } else {
                $rowEntity = $this->getFieldEntity($entity->is_historical, $entity->id, 'institution');
                $institutionId = $rowEntity->id;
                // $institutionId = $entity->institution->id
                $url = [
                    'plugin' => 'Institution',
                    'controller' => 'Institutions',
                    'action' => 'Staff',
                    'view',
                    $this->paramsEncode(['id' => $entity->id]),
                    'institution_id' => $institutionId,
                ];
                $buttons['view']['url'] = $url;
            }
        }

        return $buttons;
    }

    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra) {
        $options = ['type' => 'staff'];
        $tabElements = $this->controller->getCareerTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }

    public function onGetInstitutionId(Event $event, Entity $entity) 
    {
        $rowEntity = $this->getFieldEntity($entity->is_historical, $entity->id, 'institution');
        return $rowEntity->code_name;
    }

    public function onGetInstitutionPositionId(Event $event, Entity $entity)
    {
        $rowEntity = $this->getFieldEntity($entity->is_historical, $entity->id, 'institution_position');
        if ($entity->is_historical) {
            return $rowEntity->position_no;
        } else {
            return $rowEntity->name;
        }
    }

    public function onGetStaffTypeId(Event $event, Entity $entity)
    {
        $rowEntity = $this->getFieldEntity($entity->is_historical, $entity->id, 'staff_type');
        return $rowEntity->name;
    }

    public function onGetStaffStatusId(Event $event, Entity $entity)
    {
        $rowEntity = $this->getFieldEntity($entity->is_historical, $entity->id, 'staff_status');
        return $rowEntity->name;
    }
}