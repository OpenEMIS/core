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

    public function getIdByCode($code)
    {
        $entity = $this->find()
            ->where([$this->aliasField('code') => $code])
            ->first();

        return $entity->id;
    }
}
