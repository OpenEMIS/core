<?php
namespace FieldOption\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Validation\Validator;

class QualificationLevelsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('qualification_levels');
        parent::initialize($config);
        $this->hasMany('Qualifications', ['className' => 'Staff.Qualifications', 'foreignKey' => 'qualification_level_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }
}
