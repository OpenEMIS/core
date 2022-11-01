<?php
namespace Area\Controller\Component;

use Cake\Controller\Component;
use Page\Model\Entity\PageElement;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;

class AreapickerComponent extends Component
{
    private $controller = null;

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->controller = $this->_registry->getController();
    }

    public function implementedEvents()
    {
        $event = parent::implementedEvents();
        return $event;
    }

    public function renderAreaId(Entity $entity, $params) 
    {     
        $fieldName = $params['areaKey'];
        $targetModel = $params['targetModel'];

        $this->controller->Page->get($fieldName)
                    ->setControlType('hidden');
        
        $areaId = $entity->address_area_id;
        if(!empty($areaId)) {
            $list = $this->getAreaLevelName($targetModel, $areaId);
        } else {
            $list = [];
        }

        $after = $fieldName;
        foreach ($list as $key => $area) {
            $this->controller->Page->addNew($fieldName.$key)
                        ->setControlType('string')
                        ->setLabel($area['level_name'])
                        ->setValue($area['area_name']);

            $this->controller->Page->move($fieldName.$key)->after($after);

            $after = $fieldName.$key;
        }
    }

    public function getAreaLevelName($targetModel, $areaId)
    {

        $targetTable = TableRegistry::get($targetModel);
        $levelAssociation = Inflector::singularize($targetTable->alias()).'Levels';
        $path = $targetTable
            ->find('path', ['for' => $areaId])
            ->contain([$levelAssociation])
            ->select(['level_name' => $levelAssociation.'.name', 'area_name' => $targetTable->aliasField('name')])
            ->bufferResults(false)
            ->toArray();

        if ($targetModel == 'Area.AreaAdministratives') {
            // unset world
            unset($path[0]);
        }
        return $path;
    }
}
