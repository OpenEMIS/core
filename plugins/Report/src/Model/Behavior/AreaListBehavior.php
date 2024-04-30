<?php

namespace Report\Model\Behavior;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;

/**
 * Get areas based on area_level and areaId for Reports>Institution 
 * @author Megha Gupta <megha.gupta@mail.valuecoders.com>
 * POCOR-7794
 */
class AreaListBehavior extends Behavior
{

    public function getAreaList($id, $idArray)
    {
        //POCOR-8189
        $Areas = TableRegistry::get('Area.Areas');
        $result = $Areas->find()
                           ->where([
                               $Areas->aliasField('parent_id') => $id
                            ]) 
                             ->toArray();
       foreach ($result as $key => $value) {
            $idArray[] = $value['id'];
           $idArray = $this->getAreaList($value['id'], $idArray);
        }
        return $idArray;

        /*$Areas = TableRegistry::get('areas');
        $areaList = [];

        //Based on area level
        if ($areaLevelId != 0) {
            $areas = $Areas
                ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
                ->where([$Areas->aliasField('area_level_id') => $areaLevelId])
                ->order([$Areas->aliasField('order')])->toArray();
            $areaList1 = array_keys($areas);
            $areaList = array_merge($areaList, $areaList1);
        }
        //for area id
        if ($areaId > 1) {
            array_push($areaList, $areaId);
        }
        //Based on parent id
        if (!empty($areaList1 || $areaId != 0)) {
            $values = $areaId > 1 ? array($areaId) : array_slice($areaList1, 0);

            while (!empty($values)) {
                $areas2 = $Areas
                    ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
                    ->where([$Areas->aliasField('parent_id In (') . implode(",", $values) . ')'])
                    ->order([$Areas->aliasField('order')])->toArray();

                if (!empty($areas2)) {
                    $areaList2 = array_keys($areas2);
                    $areaList = array_merge($areaList, $areaList2);
                    $values = $areaList2;
                } else {
                    $values = [];
                }
            }
        }

        return $areaList;*/
    }
}
