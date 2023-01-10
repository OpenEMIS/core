<?php
namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Validation\Validator;

class LocalitiesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('institution_localities');
        parent::initialize($config);

        $this->hasMany('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_locality_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }
}
