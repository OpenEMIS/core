<?php

namespace Competency\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Http\ServerRequest;
use Cake\Validation\Validator;
use Cake\Utility\Inflector;
use App\Model\Table\ControllerActionTable;

class CompetencyCriteriasTable extends ControllerActionTable
{

    private $itemOptions;

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Items', ['className' => 'Competency.CompetencyItems', 'foreignKey' => ['competency_item_id', 'competency_template_id', 'academic_period_id'], 'bindingKey' => ['id', 'competency_template_id', 'academic_period_id']]);
        $this->belongsTo('GradingTypes', ['className' => 'Competency.CompetencyGradingTypes', 'foreignKey' => 'competency_grading_type_id']);
        $this->hasMany('InstitutionCompetencyResults', ['className' => 'Institution.InstitutionCompetencyResults', 'foreignKey' => ['competency_template_id', 'competency_criteria_id', 'academic_period_id'], 'bindingKey' => ['competency_template_id', 'id', 'academic_period_id']]);
        $this->belongsTo('Templates', ['className' => 'Competency.CompetencyTemplates', 'foreignKey' => ['competency_template_id', 'academic_period_id'], 'bindingKey' => ['id', 'academic_period_id']]);
        $this->setDeleteStrategy('restrict');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('code', [
                'ruleUnique' => [
                    'rule' => [
                        'validateUnique', [
                            'scope' => ['academic_period_id', 'competency_item_id', 'competency_template_id']
                        ]
                    ],
                    'provider' => 'table'
                ]
            ])
            ->allowEmpty('code');
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        //POCOR-8074-5 start
        $queryStringArr = $this->getQueryString();
        if ($queryStringArr) {
            $this->controller->getCompetencyTemplateTabs();
            $academicPeriodId = $queryStringArr['academic_period_id'];
            $competencyTemplateId = $queryStringArr['competency_template_id'];
            $extra['selectedPeriod'] = $academicPeriodId;
            $extra['selectedTemplate'] = $competencyTemplateId;
            $name = $this->Templates->get(['id' => $competencyTemplateId, 'academic_period_id' => $academicPeriodId])->name;
            $header = $name . ' - ' . __(Inflector::humanize(Inflector::underscore($this->getAlias())));
            $this->controller->set('contentHeader', $header);
            $this->controller->Navigation->substituteCrumb($this->getAlias(), $header);
        } else {
            $this->log('queryString is not set properly', 'error');
            $event->stopPropagation();
            return $this->controller->redirect(['plugin' => $this->controller->getPlugin(), 'controller' => $this->controller->getName(), 'action' => 'Templates']);
        }
        //POCOR-8074-5: end
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $toolbarButtons = $extra['toolbarButtons'];
        if ($toolbarButtons->offsetExists('back')) {
            $url = $this->url('index');
            if (isset($url['criteriaForm'])) {
                unset($url['criteriaForm']);
            }
            $toolbarButtons['back']['url'] = $url;
        }
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $request = $this->request;

        $selectedPeriod = $extra['selectedPeriod'];
        $selectedTemplate = $extra['selectedTemplate'];

        $this->fields['competency_item_id']['type'] = 'integer';

        //item filter
        if ($selectedPeriod && $selectedTemplate) {

            $itemOptions = $this->Items->find('ItemList', ['templateId' => $selectedTemplate, 'academicPeriodId' => $selectedPeriod])->toArray();
            $this->itemOptions = $itemOptions;

            $itemOptions = ['0' => '-- ' . __('All Items') . ' --'] + $itemOptions;

            if ($request->getQuery('item')) {
                $selectedItem = $request->getQuery('item');
            } else {
                $selectedItem = 0;
            }
            $data['itemOptions'] = $itemOptions;
            $data['selectedItem'] = $selectedItem;
            $extra['selectedItem'] = $selectedItem;
        }

        $data['baseUrl'] = $this->url('index');

        $extra['elements']['control'] = [
            'name' => 'Competency.criterias_controls',
            'data' => $data,
            'order' => 3
        ];
    }

    public function onGetCompetencyItemId(Event $event, Entity $entity)
    {
        return isset($this->itemOptions[$entity->competency_item_id]) ? $this->itemOptions[$entity->competency_item_id] : '';
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        if (isset($extra['selectedPeriod'])) {
            if ($extra['selectedPeriod']) {
                $conditions[$this->aliasField('academic_period_id')] = $extra['selectedPeriod'];
            }
        }

        if (isset($extra['selectedTemplate'])) {
            if ($extra['selectedTemplate']) {
                $conditions[$this->aliasField('competency_template_id')] = $extra['selectedTemplate'];
            }
        }

        if (isset($extra['selectedItem'])) {
            if ($extra['selectedItem']) {
                $conditions[$this->aliasField('competency_item_id')] = $extra['selectedItem'];
            }
        }
        $query->where([$conditions]);
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['Items.Templates']);
    }

    public function addOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
    {
        //POCOR-8074-5 start
        $item = $this->request->getQuery('item');
        $alias = $this->getAlias();
        if ($item) {
            $this->request = $this->request->withData($alias, ['competency_item_id' => $item]);
        }
        $criteriaArr = $this->getQueryString(null, 'criteriaForm');
        if (!empty($criteriaArr)) {
            $this->request = $this->request->withData($alias, ['competency_item_id' => $criteriaArr['competency_item_id']]);
            $this->request = $this->request->withData($alias, ['name' => $criteriaArr['name']]);
            $this->request = $this->request->withData($alias, ['competency_grading_type_id' => $criteriaArr['competency_grading_type_id']]);
        }
        //POCOR-8074-5 end
    }

    public function addAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
    {
        $url = $this->url('index');
        if (isset($url['criteriaForm'])) {
            unset($url['criteriaForm']);
        }
        $extra['redirect'] = $url;
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('academic_period_id', [
            'type' => 'select',
            'entity' => $entity,
            'extra' => $extra
        ]);
        $this->field('competency_template_id', [
            'type' => 'select',
            'entity' => $entity,
            'extra' => $extra
        ]);
        $this->field('competency_item_id', [
            'type' => 'select',
            'entity' => $entity,
            'extra' => $extra
        ]);
        $this->field('name', [
            'type' => 'text',
            'entity' => $entity
        ]);
        $this->field('competency_grading_type_id', [
            'type' => 'select',
            'entity' => $entity
        ]);

        $this->setFieldOrder([
            'academic_period_id', 'competency_template_id', 'competency_item_id', 'code', 'name', 'percentage', 'competency_grading_type_id'
        ]);
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add' || $action == 'edit') {
            if ($action == 'add') {
                $attr['type'] = 'readonly';
                $attr['value'] = $attr['extra']['selectedPeriod'];
                $attr['attr']['value'] = $this->AcademicPeriods->get($attr['extra']['selectedPeriod'])->name;
            } else if ($action == 'edit') {
                $attr['type'] = 'readonly';
                $attr['attr']['value'] = $this->AcademicPeriods->get([$attr['entity']->academic_period_id])->name;
                $attr['value'] = $attr['entity']->academic_period_id;
            }
        }
        return $attr;
    }

    public function addEditOnChangeAcademicPeriod(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        //POCOR-8074-5 start
        $request = $this->request;
        $this->request = $request->withQueryParams([
            'template' => '-1',
            'item' => '-1']);
        if ($request->is(['post', 'put'])) {
            $alias = $this->getAlias();
            if (isset($data[$alias]) && isset($data[$alias]['academic_period_id'])) {
                $this->request = $request->withQueryParams([
                    'period' => $data[$alias]['academic_period_id']]);
            }
        }
        //POCOR-8074-5 end
    }

    public function onUpdateFieldCompetencyTemplateId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add') {

            $attr['type'] = 'readonly';
            $attr['attr']['value'] = $this->Templates->get(['id' => $attr['extra']['selectedTemplate'], 'academic_period_id' => $attr['extra']['selectedPeriod']])->code_name;
            $attr['value'] = $attr['extra']['selectedTemplate'];

        } else if ($action == 'edit') {
            $attr['type'] = 'readonly';
            $attr['value'] = $attr['entity']->item->competency_template_id;
            $attr['attr']['value'] = $attr['entity']->item->template->code_name;

        }
        return $attr;
    }

    public function addEditOnChangeCompetencyTemplate(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        //POCOR-8074-5 start
        $request = $this->request;
        $this->request = $request->withQueryParams([
            'item' => '-1']);
        if ($request->is(['post', 'put'])) {
            $alias = $this->getAlias();
            if (isset($data[$alias]) && isset($data[$alias]['academic_period_id'])) {
                $this->request = $request->withQueryParams([
                    'period' => $data[$alias]['academic_period_id']]);
            }
            if (isset($data[$alias]) && isset($data[$alias]['competency_template_id'])) {
                $this->request = $request->withQueryParams([
                    'template' => $data[$alias]['competency_template_id']]);
            }
        }
        //POCOR-8074-5 end
    }

    public function addEditOnChangeGradingType(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $alias = $this->getAlias();
        $competencyGradingTypeId = $data[$alias]['competency_grading_type_id'];
        if ($competencyGradingTypeId == 'createNew') {
            $url = $this->url('add');
            $url['action'] = 'GradingTypes';
            $params = ['competency_item_id' => $data[$alias]['competency_item_id'], 'name' => $data[$alias]['name']];
            $url = $this->setQueryString($url, $params, 'criteriaForm');
            $event->stopPropagation();
            return $this->controller->redirect($url);
        }
    }

    public function onUpdateFieldCompetencyGradingTypeId(Event $event, array $attr, $action, $request)
    {
        $options = ['' => '-- ' . __('Select') . ' --', 'createNew' => '-- ' . __('Create New') . ' --'];
        $gradingTypeOptions = $this->GradingTypes->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])->toArray();
        $options = $options + $gradingTypeOptions;
        $attr['options'] = $options;
        $attr['type'] = 'chosenSelect';
        $attr['attr']['multiple'] = false;
        $attr['onChangeReload'] = 'ChangeGradingType';
        return $attr;
    }

    public function onUpdateFieldCompetencyItemId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            $selectedPeriod = $attr['extra']['selectedPeriod'];
            $selectedTemplate = $attr['extra']['selectedTemplate'];
            $itemOptions = [];
            if ($selectedTemplate) {
                $itemOptions = $this->Items->find('ItemList', ['templateId' => $selectedTemplate, 'academicPeriodId' => $selectedPeriod])->toArray();
            }
            $attr['options'] = $itemOptions;
        } else if ($action == 'edit') {
            $attr['type'] = 'readonly';
            $attr['value'] = $attr['entity']->competency_item_id;
            $attr['attr']['value'] = $attr['entity']->item->name;

        }
        return $attr;
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'code') {
            return __('Code');
        } elseif ($field == 'name') {
            return __('Name');
        } elseif ($field == 'competency_item_id') {
            return __('Competency Items');
        } elseif ($field == 'competency_grading_type_id') {
            return __('Competency Grading Types');
        } elseif ($field == 'competency_template_id') {
            return __('Competency Template');
        } elseif ($field == 'academic_period_id') {
            return __('Academic Period');
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
