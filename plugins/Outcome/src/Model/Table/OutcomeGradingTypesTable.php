<?php
namespace Outcome\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Validation\Validator;

use App\Model\Table\ControllerActionTable;

class OutcomeGradingTypesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->hasMany('Criterias', [
            'className' => 'Outcome.OutcomeCriterias',
            'foreignKey' => 'outcome_grading_type_id',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->hasMany('GradingOptions', [
            'className' => 'Outcome.OutcomeGradingOptions',
            'foreignKey' => 'outcome_grading_type_id',
            'saveStrategy' => 'replace',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->controller->getOutcomeTabs();
    }
}