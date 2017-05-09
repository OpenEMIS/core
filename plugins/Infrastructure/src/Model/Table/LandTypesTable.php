<?php
namespace Infrastructure\Model\Table;

use ArrayObject;

use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\Event;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;

class LandTypesTable extends ControllerActionTable
{
    private $levelOptions = [];
    private $landLevel = null;

    public function initialize(array $config)
    {
        $this->table('land_types');
        parent::initialize($config);

        $this->hasMany('InstitutionLands', ['className' => 'Institution.InstitutionLands', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->addBehavior('FieldOption.FieldOption');
        $this->addBehavior('Infrastructure.Types');

        $InfrastructureLevels = TableRegistry::get('Infrastructure.InfrastructureLevels');
        $this->levelOptions = $InfrastructureLevels->find('list')->toArray();
        $this->landLevel = $InfrastructureLevels->getFieldByCode('LAND', 'id');
        $this->setDeleteStrategy('restrict');
    }

    public function onGetInfrastructureLevel(Event $event, Entity $entity)
    {
        return $this->levelOptions[$this->landLevel];
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
            $attr['value'] = $this->landLevel;
            $attr['attr']['value'] = $this->levelOptions[$this->landLevel];
        }

        return $attr;
    }

    private function setupFields(Entity $entity)
    {
        $this->field('infrastructure_level', ['type' => 'select']);
    }
}
