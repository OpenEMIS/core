<?php
namespace Competency\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class CompetencyCriteriasTable extends ControllerActionTable {

    private $itemOptions;

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Items',           ['className' => 'Competency.CompetencyItems', 'foreignKey' => ['competency_item_id', 'competency_template_id', 'academic_period_id'], 'bindingKey' => ['id', 'competency_template_id', 'academic_period_id']]);
        $this->belongsTo('GradingTypes',    ['className' => 'Competency.CompetencyGradingTypes', 'foreignKey' => 'competency_grading_type_id']);
        $this->hasMany('StudentCompetencyResults', ['className' => 'Institution.StudentCompetencyResults', 'foreignKey' => ['competency_criteria_id', 'academic_period_id']]);
        $this->belongsTo('Templates', ['className' => 'Competency.CompetencyTemplates', 'foreignKey' => ['competency_template_id', 'academic_period_id'], 'bindingKey' => ['id', 'academic_period_id']]);
        $this->setDeleteStrategy('restrict');
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $queryString = $this->request->query('queryString');
        if ($queryString) {
            $this->controller->getCompetencyTabs(['queryString' => $queryString]);
            $queryStringArr = $this->getQueryString();
            $academicPeriodId = $queryStringArr['academic_period_id'];
            $competencyTemplateId = $queryStringArr['competency_template_id'];

            $extra['selectedPeriod'] = $academicPeriodId;
            $extra['selectedTemplate'] = $competencyTemplateId;

            $name = $this->Templates->get(['id' => $competencyTemplateId, 'academic_period_id' => $academicPeriodId])->name;
            $header = $name . ' - ' . __($this->alias());
            $this->controller->set('contentHeader', $header);
            $this->controller->Navigation->substituteCrumb($this->alias(), $header);
        } else {
            $event->stopPropagation();
            return $this->controller->redirect(['plugin' => $this->controller->plugin, 'controller' => $this->controller->name, 'action' => 'Templates']);
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

            $itemOptions = array(-1 => __('-- Select Item --')) + $itemOptions;

            if ($request->query('item')) {
                $selectedItem = $request->query('item');
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
        if (array_key_exists('selectedPeriod', $extra)) {
            if ($extra['selectedPeriod']) {
                $conditions[$this->aliasField('academic_period_id')] = $extra['selectedPeriod'];
            }
        }

        if (array_key_exists('selectedTemplate', $extra)) {
            if ($extra['selectedTemplate']) {
                $conditions[$this->aliasField('competency_template_id')] = $extra['selectedTemplate'];
            }
        }

        if (array_key_exists('selectedItem', $extra)) {
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
        if ($this->request->query('item')) {
            $this->request->data[$this->alias()]['competency_item_id'] = $this->request->query('item');
        }
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
            'academic_period_id', 'competency_template_id', 'competency_item_id', 'name', 'percentage', 'competency_grading_type_id'
        ]);
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
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
        $request = $this->request;
        $request->query['template'] = '-1';
        $request->query['item'] = '-1';

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('academic_period_id', $request->data[$this->alias()])) {
                    $request->query['period'] = $request->data[$this->alias()]['academic_period_id'];
                }
            }
        }
    }

    public function onUpdateFieldCompetencyTemplateId(Event $event, array $attr, $action, Request $request)
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
        $request = $this->request;
        $request->query['item'] = '-1';

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('academic_period_id', $request->data[$this->alias()])) {
                    $request->query['period'] = $request->data[$this->alias()]['academic_period_id'];
                }

                if (array_key_exists('competency_template_id', $request->data[$this->alias()])) {
                    $request->query['template'] = $request->data[$this->alias()]['competency_template_id'];
                }
            }
        }
    }

    public function onUpdateFieldCompetencyItemId(Event $event, array $attr, $action, Request $request)
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
}
