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
        $this->hasMany('ExaminationCentreSpecialNeeds', ['className' => 'Examination.ExaminationCentreSpecialNeeds', 'foreignKey' => 'special_need_type_id']);
        
        $this->hasMany('SpecialNeedsAssessments', ['className' => 'SpecialNeeds.SpecialNeedsAssessments', 'foreignKey' => 'special_need_type_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('SpecialNeedsReferrals', ['className' => 'SpecialNeeds.SpecialNeedsReferrals', 'foreignKey' => 'reason_type_id', 'dependent' => true, 'cascadeCallbacks' => true]);

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
