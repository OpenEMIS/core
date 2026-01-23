<?php

namespace Report\Model\Behavior;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Event\EventInterface;
use Cake\Network\Request;

/**
 * Get areas based on area_level and areaId for Reports>Institution
 * @author Megha Gupta <megha.gupta@mail.valuecoders.com>
 * POCOR-7794
 */
class AreaListBehavior extends Behavior
{

    /**
     * OCOR-8157
     * @param $id
     * @param $idArray
     * @return array|mixed
     */
     public function getAreaList($id, $idArray = []): array
    {
        $locator = TableRegistry::getTableLocator();

        if(!is_array($idArray)){
            $idArray = [];
        }
        $Areas =  $locator->get('areas');
        $result = $Areas->find()
                           ->select('id')
                           ->where([
                               $Areas->aliasField('parent_id') => $id
                            ])
                             ->toArray();

       foreach ($result as $area) {
           $area_id = $area['id'];
           $idArray[] = $area_id;
           $idArray = $this->getAreaList($area_id, $idArray);
        }
        return $idArray;

    }
}
