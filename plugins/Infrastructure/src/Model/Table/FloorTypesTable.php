<?php
namespace Infrastructure\Model\Table;

use ArrayObject;

use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\EventInterface;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;

class FloorTypesTable extends ControllerActionTable
{
    private $levelOptions = [];
    private $floorLevel = null;

    public function initialize(array $config): void
    {
        $this->setTable('floor_types');
        parent::initialize($config);

        $this->hasMany('InstitutionFloors', ['className' => 'Institution.InstitutionFloors', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->addBehavior('FieldOption.FieldOption');
        $this->addBehavior('Infrastructure.Types');

        $InfrastructureLevels = TableRegistry::getTableLocator()->get('Infrastructure.InfrastructureLevels');
        $this->levelOptions = $InfrastructureLevels->find('list')->toArray();
        $this->floorLevel = $InfrastructureLevels->getFieldByCode('FLOOR', 'id');
        $this->setDeleteStrategy('restrict');
    }

    public function onGetInfrastructureLevel(EventInterface $event, Entity $entity)
    {
        return $this->levelOptions[$this->floorLevel];
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $extra['config']['selectedLink'] = ['controller' => 'Infrastructures', 'action' => 'Fields'];
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('infrastructure_level', ['after' => 'national_code']);
    }

    // POCOR-9074
    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
        unset($entity->infrastructure_level);
    }
    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function addEditAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    // POCOR-9074
    public function onUpdateFieldInfrastructureLevel(EventInterface $event, array $attr, $action, $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $attr['type'] = 'readonly';
            $attr['value'] = $this->floorLevel;
            $attr['attr']['value'] = $this->levelOptions[$this->floorLevel];
        }

        return $attr;
    }

    private function setupFields(Entity $entity)
    {
        $this->field('infrastructure_level', ['type' => 'select']);
    }
}
