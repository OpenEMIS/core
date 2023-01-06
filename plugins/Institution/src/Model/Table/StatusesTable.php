<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;

class StatusesTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('institution_statuses');
        parent::initialize($config);

        $this->hasMany('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_status_id']);
    }

    public function findCodeList()
    {
        return $this->find('list', ['keyField' => 'code', 'valueField' => 'id'])->toArray();
    }

    /**
     * Get all statuses of Institution id as key and name as value
     * @author Anand Malvi <anand.malvi@mail.valuecoders.com>
     * @ticket POCOR-6591
     */
    public function findIdList()
    {
        return $this->find('list', ['keyField' => 'id', 'valueField' => 'name'])->toArray();
    }

    public function getIdByCode($code)
    {
        $entity = $this->find()
            ->where([$this->aliasField('code') => $code])
            ->first();

        return $entity->id;
    }
}
