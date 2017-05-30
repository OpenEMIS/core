<?php
namespace Infrastructure\Model\Table;

use ArrayObject;

use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\Event;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;

class RoomTypesTable extends ControllerActionTable
{
    private $levelOptions = [];
    private $roomLevel = null;
    private $classificationOptions = [];

    public function initialize(array $config)
    {
        $this->table('room_types');
        parent::initialize($config);

        $this->hasMany('InstitutionRooms', ['className' => 'Institution.InstitutionRooms', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->addBehavior('FieldOption.FieldOption');
        $this->addBehavior('Infrastructure.Types');

        $InfrastructureLevels = TableRegistry::get('Infrastructure.InfrastructureLevels');
        $this->levelOptions = $InfrastructureLevels->find('list')->toArray();
        $this->roomLevel = $InfrastructureLevels->getFieldByCode('ROOM', 'id');
        $this->classificationOptions = $this->getSelectOptions($this->aliasField('classifications'));
        $this->setDeleteStrategy('restrict');
    }

    public function onGetInfrastructureLevel(Event $event, Entity $entity)
    {
        return $this->levelOptions[$this->roomLevel];
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $extra['config']['selectedLink'] = ['controller' => 'Infrastructures', 'action' => 'Fields'];
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('infrastructure_level', ['after' => 'national_code']);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function onUpdateFieldInfrastructureLevel(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $attr['type'] = 'readonly';
            $attr['value'] = $this->roomLevel;
            $attr['attr']['value'] = $this->levelOptions[$this->roomLevel];
        }

        return $attr;
    }

    public function onUpdateFieldClassification(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $attr['options'] = $this->classificationOptions;
        } elseif ($action == 'edit') {
            $entity = $attr['entity'];

            $attr['type'] = 'readonly';
            $attr['value'] = $entity->classification;
            $attr['attr']['value'] = $this->classificationOptions[$entity->classification];
        }

        return $attr;
    }

    private function setupFields(Entity $entity)
    {
        $this->field('infrastructure_level', ['type' => 'select']);
        $this->field('classification', ['type' => 'select', 'after' => 'default', 'entity' => $entity]);
    }

    public function getClassificationTypes($roomTypeId)
    {
        if ($roomTypeId > 0) {
            $classificationTypeId = $this->get($roomTypeId)->classification;

            return $classificationTypeId;
        }
    }

    public function onGetClassification(Event $event, Entity $entity)
    {
        return $this->classificationOptions[$entity->classification];
    }
}
