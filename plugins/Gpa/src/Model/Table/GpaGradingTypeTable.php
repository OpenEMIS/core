<?php
namespace Gpa\Model\Table;

use App\Model\Table\ControllerActionTable;
use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Validation\Validator;
use Cake\ORM\TableRegistry;
use Cake\Http\ServerRequest;


/**
 * POCOR-8222
 * Develop GPA features in system
 * */
class GpaGradingTypeTable extends ControllerActionTable {
    public function initialize(array $config): void
    {
        $this->setTable('gpa_grading_types');
        parent::initialize($config);

        $this->setDeleteStrategy('restrict');
    }

    public function validationDefault(Validator $validator): Validator {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        return $validator
            ->add('code', 'ruleUniqueCode', [
                'rule' => ['checkUniqueCode', null]
             ])
            ->notEmpty('name')
            ->notEmpty('result_type')
            ->notEmpty('pass_mark')
            ->notEmpty('max');
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        $this->controller->getGpaTab();
        
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $option = array('Marks' =>'Marks', 'Grades' => 'Grades','Duration' => 'Duration');
        $this->field('result_type', ['type' => 'select', 'options' => $option]);

        $this->setFieldOrder(['visible', 'code', 'name','result_type','max','pass_mark']);
    }
    
}
