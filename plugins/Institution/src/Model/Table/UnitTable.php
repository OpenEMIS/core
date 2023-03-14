<?php
namespace Institution\Model\Table;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;

class UnitTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('institution_units');
        parent::initialize($config);

        //$this->hasMany('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }

    public function getUnitOptions($institutionsId, $periodId)
    {
        $institutionClasses = TableRegistry::get('institution_units');
        $query = $institutionClasses->find('list',['keyField' => 'id', 'valueField' => 'name']);
        return $query;
    }
}
