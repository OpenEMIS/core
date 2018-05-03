<?php
namespace FieldOption\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Validation\Validator;

class CountriesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('countries');
        parent::initialize($config);

        $this->hasMany('InstitutionChoices', ['className' => 'Scholarship.InstitutionChoices']);
        
        $this->addBehavior('FieldOption.FieldOption');
        $this->setDeleteStrategy('cascade');
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        $validator
            ->notEmpty('name', 'Please enter a name.');

        return $validator;
    }
}
