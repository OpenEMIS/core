<?php
namespace FieldOption\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class LanguageProficienciesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('language_proficiencies');
        parent::initialize($config);
        $this->addBehavior('FieldOption.FieldOption');
    }

    public function validationDefault(Validator $validator) 
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('name', [
                'ruleUnique' => [
                    'rule' => ['validateUnique'],
                    'provider' => 'table'
                ]
            ]);
    }

}
