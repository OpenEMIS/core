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

        $this->addBehavior('Restful.RestfulAccessControl', [
            'StudentOutcomes' => ['index']
        ]);

        $this->setDeleteStrategy('restrict');
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        return $validator
            ->requirePresence('grading_options')
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
        if ($this->request->params['action'] == 'GradingTypes') {
            $query->contain(['GradingOptions']);
        } else {
            $query->contain(['GradingOptions.InstitutionOutcomeResults']);
        }
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $criteriaForm = $this->getQueryString(null, 'criteriaForm');

        // set back button to redirect to Criterias add page (when Create New from Criterias add page)
        if (!empty($criteriaForm)) {
            $toolbarButtons = $extra['toolbarButtons'];
            if ($toolbarButtons->offsetExists('back')) {
                $toolbarButtons['back']['url']['action'] = 'Criterias';
                $toolbarButtons['back']['url'][0] = 'add';
            }
            $extra['criteriaForm'] = $criteriaForm;
        }
    }

    public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $extra)
    {
        if (!isset($data[$this->alias()]['grading_options']) || empty($data[$this->alias()]['grading_options'])) {
            $this->Alert->warning($this->aliasField('noGradingOptions'));
        }
    }

    public function addEditOnAddOption(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        if ($data->offsetExists($this->alias())) {
            if (!array_key_exists('grading_options', $data[$this->alias()])) {
                $data[$this->alias()]['grading_options'] = [];
            } else {
                // reindex array keys
                $gradingOptions = $data[$this->alias()]['grading_options'];
                $data[$this->alias()]['grading_options'] = array_values($gradingOptions);
            }

            $data[$this->alias()]['grading_options'][] = [
                'outcome_grading_type_id' => '',
                'code' => '',
                'name' => '',
                'description' => ''
            ];
        }

        $options['associated'] = [
            'GradingOptions' => ['validate' => false]
        ];
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        // enables all options to be deleted
        if (!$data->offsetExists('grading_options')) {
            $data->offsetSet('grading_options', []);
        }
    }

    public function addAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
    {
        // set save button to redirect to Criterias add page (when Create New from Criterias add page)
        if ($extra->offsetExists('criteriaForm')) {
            $criteriaParams = $extra['criteriaForm'];
            $criteriaParams['outcome_grading_type_id'] = $entity->id;

            $url = $this->url('add');
            $url['action'] = 'Criterias';
            $extra['redirect'] = $this->setQueryString($url, $criteriaParams, 'criteriaForm');
        }
    }
    
    public function editBeforeSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $extra)
    {
        if (!isset($data[$this->alias()]['grading_options']) || empty($data[$this->alias()]['grading_options'])) {
            $this->Alert->warning($this->aliasField('noGradingOptions'));
        }
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
