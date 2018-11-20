<?php
namespace SpecialNeeds\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class SpecialNeedsDifficultiesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('special_need_difficulties'); 
        parent::initialize($config);

        $this->hasMany('SpecialNeedsAssessments', ['className' => 'SpecialNeeds.SpecialNeedsAssessments', 'foreignKey' => 'special_need_difficulty_id', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->addBehavior('FieldOption.FieldOption');
    }
}
