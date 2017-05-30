<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;
use Cake\ORM\Query;

class SpecialNeedTypesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('special_need_types');
        parent::initialize($config);
        $this->hasMany('SpecialNeeds', ['className' => 'User.SpecialNeeds', 'foreignKey' => 'special_need_type_id']);
        $this->hasMany('ExaminationCentreSpecialNeeds', ['className' => 'Examination.ExaminationCentreSpecialNeeds', 'foreignKey' => 'special_need_type_id']);

        $this->addBehavior('FieldOption.FieldOption');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Students' => ['index', 'add'],
            'Staff' => ['index', 'add']
        ]);
    }

    public function getVisibleNeedTypes(array $options = [])
    {
        $query = $this
            ->find('visible')
            ->find('order')
            ->find('list')
            ->toArray();
        return $query;
    }
}
