<?php
namespace Infrastructure\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\Event;

use App\Model\Table\ControllerActionTable;

class InfrastructureTypesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('infrastructure_types');
        parent::initialize($config);

        $this->belongsTo('Levels', ['className' => 'Infrastructure.InfrastructureLevels', 'foreignKey' => 'infrastructure_level_id']);
        $this->hasMany('InstitutionInfrastructures', ['className' => 'Institution.InstitutionInfrastructures', 'dependent' => true, 'cascadeCallbacks' => true]);
        if ($this->behaviors()->has('Reorder')) {
            $this->behaviors()->get('Reorder')->config([
                'filter' => 'infrastructure_level_id'
            ]);
        }

        $this->addBehavior('FieldOption.FieldOption');
        $this->addBehavior('Infrastructure.Types');
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        if ($extra->offsetExists('params') && array_key_exists('selectedLevel', $extra['params'])) {
            $selectedLevel = $extra['params']['selectedLevel'];
            $query->where([$this->aliasField('infrastructure_level_id') => $selectedLevel]);
        }
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function onUpdateFieldInfrastructureLevelId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $levelOptions = $this->Levels->find('list')->toArray();
            $selectedLevel = $this->queryString('level', $levelOptions);

            $attr['type'] = 'readonly';
            $attr['value'] = $selectedLevel;
            $attr['attr']['value'] = $levelOptions[$selectedLevel];
        }

        return $attr;
    }

    private function setupFields(Entity $entity)
    {
        $this->field('infrastructure_level_id', ['type' => 'select']);
    }
}
