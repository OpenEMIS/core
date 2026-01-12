<?php

namespace Competency\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\EventInterface;
use Cake\Validation\Validator;
use Cake\Collection\Collection;

use App\Model\Table\ControllerActionTable;

class CompetencyGradingTypesTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('competency_grading_types');

        parent::initialize($config);
        $this->hasMany('Criterias', ['className' => 'Competency.CompetencyCriterias']);
        $this->hasMany('GradingOptions', ['className' => 'Competency.CompetencyGradingOptions', 'foreignKey' => 'competency_grading_type_id', 'saveStrategy' => 'replace']);

        $this->addBehavior('Restful.RestfulAccessControl', [
            'StudentCompetencies' => ['index']
        ]);

        $this->setDeleteStrategy('restrict');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->requirePresence('grading_options')
            ->allowEmpty('code');
        // ->add('code', [
        //     'ruleUniqueCode' => [
        //         'rule' => ['checkUniqueCode', ''],
        //         'provider' => 'table'
        //     ]
        // ]);
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->controller->getCompetencyTabs();

        if ($this->action == 'add' || $this->action == 'edit' || $this->action == 'view') {
            $this->field('grading_options', [
                'type' => 'element',
                'element' => 'Competency.grading_options',
                'fields' => $this->GradingOptions->fields,
                'formFields' => [],
                'attr' => [
                    'label' => 'Criteria Grading Options'
                ]
            ]);
        }

        // Start POCOR-5188
        $is_manual_exist = $this->getManualUrl('Administration', 'GradingTypes', 'Competencies');
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

    public function addBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $criteriaForm = $this->getQueryString(null, 'criteriaForm');
        if ($criteriaForm) {
            $toolbarButtons = $extra['toolbarButtons'];
            if ($toolbarButtons->offsetExists('back')) {
                $toolbarButtons['back']['url']['action'] = 'Criterias';
                $toolbarButtons['back']['url'][0] = 'add';
            }
            $extra['criteriaForm'] = $criteriaForm;
        }
    }

    public function addEditBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->fields['grading_options']['formFields'] = array_keys($this->GradingOptions->getFormFields());

        $this->setFieldOrder([
            'code', 'name', 'grading_options',
        ]);
    }

    public function addEditAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        // $gradingOptions will contain the GradeOptionId and the association.(1 for true and 0 for false)
        // $GradingOptions = TableRegistry::getTableLocator()->get('Competency.CompetencyGradingOptions');
        $gradingOptions = [];
        if (!is_null($entity->grading_options)) {

            foreach ($entity->grading_options as $key => $gradingOption) {
                $gradingOptionId = $gradingOption->id;
                if ($gradingOptionId) { //POCOR-8074-5
                    $gradingOptions[$gradingOptionId] = 0;
                    if ($this->hasAssociatedRecords($this->GradingOptions, $gradingOption, $extra)) {
                        $gradingOptions[$gradingOptionId] = 1;
                    }
                }
            }
        }

        // to passed the array of the association to the view (grading_options.ctp).
        $this->controller->set('gradingOptions', $gradingOptions);
    }

    public function addEditOnReload(EventInterface $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions)
    {
        $groupOptionData = $this->GradingOptions->getFormFields();
        if (!empty($entity->id)) {
            $groupOptionData['competency_grading_type_id'] = $entity->id;
        }
        $newGroupOption = $this->GradingOptions->newEntity($groupOptionData);
        $requestData[$this->getAlias()]['grading_options'][] = $newGroupOption->toArray();
        $newOptions = [$this->GradingOptions->getAlias() => ['validate' => false]];
        if (isset($patchOptions['associated'])) {
            $patchOptions['associated'] = array_merge($patchOptions['associated'], $newOptions);
        } else {
            $patchOptions['associated'] = $newOptions;
        }
    }

    public function addBeforeSave(EventInterface $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
    {
        if (!isset($requestData[$this->getAlias()]['grading_options']) || empty($requestData[$this->getAlias()]['grading_options'])) {
            $this->Alert->warning($this->aliasField('noGradingOptions'));
        } else if (isset($requestData[$this->getAlias()]['grading_options']) && is_array($requestData[$this->getAlias()]['grading_options'])) {
            $gradingOptions = $requestData[$this->getAlias()]['grading_options'];
            $codes = array_column($gradingOptions, 'code');
            $codes = array_filter($codes);
            $vals = array_count_values($codes);
            foreach ($vals as $count) {
                if ($count > 1) {
                    $entity->errors('grading_options', __('Duplicated Code'));
                    $this->Alert->error('general.uniqueCodeForm');
                    break;
                }
            }
        }
    }

    public function addAfterSave(EventInterface $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
    {
        if ($extra->offsetExists('criteriaForm')) {
            $url = $this->url('add');
            $url['action'] = 'Criterias';
            $criteriaForm = $extra['criteriaForm'];
            $criteriaForm['competency_grading_type_id'] = $entity->id;
            $url = $this->setQueryString($url, $criteriaForm, 'criteriaForm');
            $extra['redirect'] = $url;
        }
    }

    public function editBeforeSave(EventInterface $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
    {

        if (!isset($requestData[$this->getAlias()]['grading_options']) || empty($requestData[$this->getAlias()]['grading_options'])) {
            $this->Alert->warning($this->aliasField('noGradingOptions'));
        } else if (isset($requestData[$this->getAlias()]['grading_options']) && is_array($requestData[$this->getAlias()]['grading_options'])) {
            $gradingOptions = $requestData[$this->getAlias()]['grading_options'];
            $codes = array_column($gradingOptions, 'code');
            $vals = array_count_values($codes);
            foreach ($vals as $count) {
                if ($count > 1) {
                    $entity->errors('grading_options', __('Duplicated Code'));
                    $this->Alert->error('general.uniqueCodeForm');
                    break;
                }
            }
        }
    }

    public function deleteOnInitialize(EventInterface $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        $extra['excludedModels'] = [
            $this->GradingOptions->getAlias()
        ];
    }

    public function viewBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->fields['grading_options']['formFields'] = array_keys($this->GradingOptions->getFormFields('view'));

        $this->setFieldOrder([
            'code', 'name', 'grading_options'
        ]);
    }

    public function viewEditBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $query->contain([
            $this->GradingOptions->getAlias()
        ]);
    }

    public function getCustomList($params = [])
    {
        if (isset($params['keyField'])) {
            $keyField = $params['keyField'];
        } else {
            $keyField = 'id';
        }
        if (isset($params['valueField'])) {
            $valueField = $params['valueField'];
        } else {
            $valueField = 'name';
        }
        $query = $this->find('list', ['keyField' => $keyField, 'valueField' => $valueField]);
        return $this->getList($query);
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        if ($field == 'code') {
            return __('Code');
        } elseif ($field == 'name') {
            return __('name');
        } elseif ($field == 'competency_items') {
            return __('Competency Items');
        } elseif ($field == 'code') {
            return __('Code');
        } elseif ($field == 'name') {
            return __('Name');
        } elseif ($field == 'start_date') {
            return __('Start Date');
        } elseif ($field == 'end_date') {
            return __('End Date');
        } elseif ($field == 'date_enabled') {
            return __('Date Enabled');
        } elseif ($field == 'date_disabled') {
            return __('Date Disabled');
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
