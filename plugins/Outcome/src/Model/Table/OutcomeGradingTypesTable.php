<?php
namespace Outcome\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
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

        $this->setDeleteStrategy('restrict');
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        return $validator
            ->allowEmpty('code')
            ->add('code', 'ruleUniqueCode', [
                'rule' => 'validateUnique',
                'provider' => 'table'
            ]);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->controller->getOutcomeTabs();
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->setupFields();
    }

    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->setupFields();
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['GradingOptions.InstitutionOutcomeResults']);
    }

    public function addEditOnAddOption(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $fieldKey = 'grading_options';

        if (empty($data[$this->alias()][$fieldKey])) {
            $data[$this->alias()][$fieldKey] = [];
        }
        if ($data->offsetExists($this->alias())) {
            $data[$this->alias()][$fieldKey][] = [
                'code' => '',
                'name' => '',
                'description' => '',
                'outcome_grading_type_id' => '',
                'id' => ''
            ];
        }

        $options['associated'] = [
            'GradingOptions' => ['validate' => false]
        ];
    }

    public function setupFields()
    {
        $this->field('grading_options', [
            'type' => 'element',
            'element' => 'Outcome.grading_options'
        ]);

        $this->setFieldOrder(['code', 'name', 'grading_options']);
    }

    public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        $extra['excludedModels'] = [
            $this->GradingOptions->alias()
        ];
    }
}
