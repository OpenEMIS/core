<?php
namespace Infrastructure\Model\Table;

use App\Model\Table\ControllerActionTable;

class InfrastructureStatusesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->hasMany('InstitutionRooms', ['className' => 'Institution.InstitutionRooms', 'dependent' => true, 'cascadeCallbacks' => true, 'foreignKey' => 'room_status_id']);
        $this->hasMany('InstitutionLands', ['className' => 'Institution.InstitutionLands', 'dependent' => true, 'cascadeCallbacks' => true, 'foreignKey' => 'land_status_id']);
        $this->hasMany('InstitutionBuildings', ['className' => 'Institution.InstitutionBuildings', 'dependent' => true, 'cascadeCallbacks' => true, 'foreignKey' => 'building_status_id']);
        $this->hasMany('InstitutionFloors', ['className' => 'Institution.InstitutionFloors', 'dependent' => true, 'cascadeCallbacks' => true, 'foreignKey' => 'floor_status_id']);
    }

    public function findCodeList()
    {
        return $this->find('list', ['keyField' => 'code', 'valueField' => 'id'])->toArray();
    }

    public function getIdByCode($code)
    {
        $entity = $this->find()->where([$this->aliasField('code') => $code])->first();

        if ($entity) {
            return $entity->id;
        } else {
            return '';
        }
    }
}
