<?php

namespace Outcome\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Validation\Validator;

use App\Model\Table\ControllerActionTable;

class OutcomeGradingTypesTable extends ControllerActionTable
{
    public function initialize(array $config): void
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
    //POCOR-9293 start
    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        return $validator
            ->requirePresence('grading_options')
            ->requirePresence('code')
            ->requirePresence('name')
            ->add('code', 'ruleUniqueCode', [
                'rule' => 'validateUnique',
                'provider' => 'table'
            ]);
    }
    //POCOR-9293 end
    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->controller->getOutcomeTabs();

        // Start POCOR-5188
        $is_manual_exist = $this->getManualUrl('Administration', 'Grading Types', 'Learning Outcomes');
        if (!empty($is_manual_exist)) {
            $btnAttr = [
                'class' => 'btn btn-xs btn-default icon-big',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'target' => '_blank'
            ];

            $helpBtn['url'] = $is_manual_exist['url'];
            $helpBtn['type'] = 'button';
            $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
            $helpBtn['attr'] = $btnAttr;
            $helpBtn['attr']['title'] = __('Help');
            $extra['toolbarButtons']['help'] = $helpBtn;
        }
        // End POCOR-5188
    }

    public function viewBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->setupFields();
    }

    public function addEditBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->setupFields();
    }

    public function viewEditBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        if ($this->request->getParam('action') == 'GradingTypes') {
            $query->contain(['GradingOptions']);
        } else {
            $query->contain(['GradingOptions.InstitutionOutcomeResults']);
        }
    }

    public function addBeforeAction(EventInterface $event, ArrayObject $extra)
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

    public function addBeforeSave(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $extra)
    {
        if (!isset($data[$this->getAlias()]['grading_options']) || empty($data[$this->getAlias()]['grading_options'])) {
            $this->Alert->warning($this->aliasField('noGradingOptions'));
        }
    }

    public function addEditOnAddOption(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        if ($data->offsetExists($this->getAlias())) {
            if (!array_key_exists('grading_options', $data[$this->getAlias()])) { //POCOR-9154
                $data[$this->getAlias()]['grading_options'] = [];
            } else {
                // reindex array keys
                $gradingOptions = $data[$this->getAlias()]['grading_options'];
                $data[$this->getAlias()]['grading_options'] = array_values($gradingOptions);
            }

            $data[$this->getAlias()]['grading_options'][] = [
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


    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options)
    {
        // enables all options to be deleted
        if (!$data->offsetExists('grading_options')) {
            $data->offsetSet('grading_options', []);
        }
    }

    public function addAfterSave(EventInterface $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
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

    public function editBeforeSave(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $extra)
    {
        if (!isset($data[$this->getAlias()]['grading_options']) || empty($data[$this->getAlias()]['grading_options'])) {
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

    public function deleteOnInitialize(EventInterface $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        $extra['excludedModels'] = [
            $this->GradingOptions->alias()
        ];
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        if ($field == 'academic_period_id') {
            return __('Academic Period');
        } elseif ($field == 'outcome_template_id') {
            return __('Outcome Template');
        } elseif ($field == 'code') {
            return __('Code');
        } elseif ($field == 'name') {
            return __('Name');
        } elseif ($field == 'modified_user_id') {
            return __('Modified By');
        } elseif ($field == 'modified') {
            return __('Modified On');
        } elseif ($field == 'created_user_id') {
            return __('Created By');
        } elseif ($field == 'created') {
            return __('Created On');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
