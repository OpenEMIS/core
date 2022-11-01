<?php
namespace Infrastructure\Model\Table;

use ArrayObject;

use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\Event;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;

class BuildingTypesTable extends ControllerActionTable
{
    private $levelOptions = [];
    private $buildingLevel = null;

    public function initialize(array $config)
    {
        $this->table('building_types');
        parent::initialize($config);

        $this->hasMany('InstitutionBuildings', ['className' => 'Institution.InstitutionBuildings', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->addBehavior('FieldOption.FieldOption');
        $this->addBehavior('Infrastructure.Types');

        $InfrastructureLevels = TableRegistry::get('Infrastructure.InfrastructureLevels');
        $this->levelOptions = $InfrastructureLevels->find('list')->toArray();
        $this->buildingLevel = $InfrastructureLevels->getFieldByCode('BUILDING', 'id');
        $this->setDeleteStrategy('restrict');
    }

    public function onGetInfrastructureLevel(Event $event, Entity $entity)
    {
        return $this->levelOptions[$this->buildingLevel];
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
            $attr['value'] = $this->buildingLevel;
            $attr['attr']['value'] = $this->levelOptions[$this->buildingLevel];
        }

        return $attr;
    }

    private function setupFields(Entity $entity)
    {
        $this->field('infrastructure_level', ['type' => 'select']);
    }
}
