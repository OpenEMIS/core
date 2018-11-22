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

        $this->addBehavior('Historial.Historial', [
            'historialUrl' => [
                'plugin' => 'Directory',
                'controller' => 'Directories',
                'action' => 'HistorialStaffPositions'
            ],
            'model' => 'Historial.HistorialStaffPositions'
        ]);

        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Historial.index.beforeQuery'] = 'indexHistorialBeforeQuery';
        return $events;
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra) {
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

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
        $query
            ->select([
                $this->aliasField('id'),
                $this->aliasField('start_date'),
                $this->aliasField('end_date'),
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
                'is_historial' => 0
            ])
            ->leftJoinWith($this->Institutions->alias())
            ->leftJoinWith($this->InstitutionPositions->alias())
            ->leftJoinWith($this->StaffTypes->alias())
            ->leftJoinWith($this->Users->alias())
            ->leftJoinWith($this->StaffStatuses->alias())
        ;

    }

    public function indexHistorialBeforeQuery(Event $event, Query $historialQuery, $table)
    {
        $session = $this->request->session();

        if ($session->check('Directory.Directories.id')) {
            $userId = $session->read('Directory.Directories.id');

            $historialQuery
                ->select([
                    'id' => $table->aliasField('id'),
                    'start_date' => $table->aliasField('start_date'),
                    'end_date' => $table->aliasField('end_date'),
                    'institution_id' => '(null)',
                    'institution_code' => '(null)',
                    'institution_name' => $table->aliasField('institution_name'),
                    'position_id' => '(null)',
                    'position_name' => $table->aliasField('institution_position_name'),
                    'staff_position_title_id' => '(null)',
                    'staff_type_id' => 'StaffTypes.id',
                    'staff_type_name' => 'StaffTypes.name',
                    'staff_status_id' => 'StaffStatuses.id',
                    'staff_status_name' => 'StaffStatuses.name',
                    'is_historial' => 1
                ])
                ->leftJoinWith('StaffTypes')
                ->leftJoinWith('StaffStatuses')
                ->where([
                    $table->aliasField('staff_id') => $userId
                ])
            ;
        }
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
        pr($entity);
        die;
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        if (array_key_exists('view', $buttons)) {
            $institutionId = $entity->_matchingData['Institutions']->id;
            // $institutionId = $entity->institution->id;
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
        return $buttons;
    }

    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra) {
        $options = ['type' => 'staff'];
        $tabElements = $this->controller->getCareerTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }

    public function onGetInstitutionId(Event $event, Entity $entity) {
        
        if ($entity->is_historial) {
            return $entity->_matchingData['Institutions']->name;
        } else {
            return $entity->_matchingData['Institutions']->code_name;
        }
    }

    public function onGetInstitutionPositionId(Event $event, Entity $entity)
    {
        if ($entity->is_historial) {
            return $entity->_matchingData['InstitutionPositions']->position_no;
        } else {
            return $entity->_matchingData['InstitutionPositions']->name;
        }
    }

    public function onGetStaffTypeId(Event $event, Entity $entity)
    {
        return $entity->_matchingData['StaffTypes']->name;
    }

    public function onGetStaffStatusId(Event $event, Entity $entity)
    {
        return $entity->_matchingData['StaffStatuses']->name;
    }
}