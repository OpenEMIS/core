<?php
namespace Infrastructure\Model\Table;

use ArrayObject;

use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\EventInterface;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;

class LandTypesTable extends ControllerActionTable
{
    private $levelOptions = [];
    private $landLevel = null;

    public function initialize(array $config): void
    {
        $this->setTable('land_types');
        parent::initialize($config);

        $this->hasMany('InstitutionLands', ['className' => 'Institution.InstitutionLands', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->addBehavior('FieldOption.FieldOption');
        $this->addBehavior('Infrastructure.Types');

        $InfrastructureLevels = TableRegistry::getTableLocator()->get('Infrastructure.InfrastructureLevels');
        $this->levelOptions = $InfrastructureLevels->find('list')->toArray();
        $this->landLevel = $InfrastructureLevels->getFieldByCode('LAND', 'id');
        $this->setDeleteStrategy('restrict');
    }

    public function onGetInfrastructureLevel(EventInterface $event, Entity $entity)
    {
        return $this->levelOptions[$this->landLevel];
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $extra['config']['selectedLink'] = ['controller' => 'Infrastructures', 'action' => 'Fields'];
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('infrastructure_level', ['after' => 'national_code']);
    }

    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function addEditAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    // public function onUpdateFieldInfrastructureLevel(EventInterface $event, array $attr, $action, Request $request)
    public function onUpdateFieldInfrastructureLevel(EventInterface $event, array $attr, $action)
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

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'infrastructure_level') {
            return __('Infrastructure Level');
        } elseif ($field == 'name') {
            return __('Name');
        } elseif ($field == 'default') {
            return __('Default');
        } elseif ($field == 'international_code') {
            return __('International Code');
        } elseif ($field == 'national_code') {
            return __('National Code');
        } elseif ($field == 'visible') {
            return __('Visible');
        } elseif ($field == 'modified_user_id') {
            return __('Modified By');
        } elseif ($field == 'modified') {
            return __('Modified On');
        } elseif ($field == 'created_user_id') {
            return __('Created By');
        } elseif ($field == 'created') {
            return __('Created On');
        } elseif ($field == 'custom_module_id') {
            return __('Custom Module');
        } elseif ($field == 'staff_custom_field_id') {
            return __('Custom Fields');
        }elseif ($field == 'to_be_deleted') {
            return __('To be Deleted ');
        }elseif ($field == 'associated_records') {
            return __('Associated Records');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
        unset($entity->infrastructure_level); // POCOR-9074
    }

    public function beforeDelete(EventInterface $event, Entity $entity)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }
}
