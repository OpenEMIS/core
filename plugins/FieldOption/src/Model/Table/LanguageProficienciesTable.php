<?php
namespace FieldOption\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Http\ServerRequest;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class LanguageProficienciesTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('language_proficiencies');
        parent::initialize($config);
        $this->addBehavior('FieldOption.FieldOption');
    }

    public function validationDefault(Validator $validator) : Validator
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
