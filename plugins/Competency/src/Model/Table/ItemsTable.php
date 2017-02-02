<?php
namespace Competency\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Collection\Collection;
use Cake\Validation\Validator;
use Cake\View\Helper\UrlHelper;

use App\Model\Traits\OptionsTrait;
use App\Model\Traits\HtmlTrait;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\MessagesTrait;

class ItemsTable extends ControllerActionTable {
    use MessagesTrait;
    use HtmlTrait;
    use OptionsTrait;

    public function initialize(array $config)
    {
        $this->table('competency_items');

        parent::initialize($config);

        $this->belongsTo('Templates',       ['className' => 'Competency.Templates', 'foreignKey' => ['competency_template_id', 'academic_period_id']]);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);

        $this->hasMany('Criterias', ['className' => 'Competency.Criterias', 'foreignKey' => ['competency_item_id', 'competency_template_id', 'academic_period_id'], 'dependent' => true, 'cascadeCallbacks' => true]);
        // $this->hasMany('ItemsPeriods', ['className' => 'Competency.ItemsPeriods']);

        $this->belongsToMany('Periods', [
            'className' => 'Competency.Periods',
            'joinTable' => 'competency_items_periods',
            'foreignKey' => ['competency_item_id', 'academic_period_id'],
            'targetForeignKey' => 'competency_period_id',
            'through' => 'Competency.ItemsPeriods',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->hasMany('StudentCompetencyResults', ['className' => 'Institution.StudentCompetencyResults', 'foreignKey' => ['competency_criteria_id', 'academic_period_id']]);

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

            $extra['queryString'] = $queryString;

            $name = $this->Templates->get(['id' => $competencyTemplateId, 'academic_period_id' => $academicPeriodId])->name;
            $header = $name . ' - ' . __($this->alias());
            $this->controller->set('contentHeader', $header);
            $this->controller->Navigation->substituteCrumb($this->alias(), $header);

        } else {
            $event->stopPropagation();
            $this->controller->redirect(['plugin' => $this->controller->plugin, 'controller' => $this->controller->name, 'action' => 'Templates']);
        }
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $this->fields['competency_template_id']['type'] = 'integer';
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

        $query->where([$conditions]);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity, $extra);
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
        if ($action == 'add' || $action == 'edit') {
            if ($action == 'add') {

                $attr['type'] = 'readonly';
                $attr['attr']['value'] = $this->Templates->get(['id' => $attr['extra']['selectedTemplate'], 'academic_period_id' => $attr['extra']['selectedPeriod']])->code_name;
                $attr['value'] = $attr['extra']['selectedTemplate'];

            } else {

                $attr['type'] = 'readonly';
                $attr['value'] = $attr['entity']->competency_template_id;
                $attr['attr']['value'] = $this->Templates->get([$attr['entity']->competency_template_id, $attr['entity']->academic_period_id])->code_name;

            }
        }
        return $attr;
    }

    public function addAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
    {
        if (empty($entity->errors())) {
            $extra['redirect'] = [
                'plugin' => 'Competency',
                'controller' => 'Competencies',
                'action' => 'Criterias',
                '0' => 'index',
                'item' => $entity->id,
                'queryString' => $extra['queryString']
            ];
            $this->Alert->success('Items.addSuccess', ['reset'=>true]);
        }
    }

    public function setupFields(Entity $entity, ArrayObject $extra)
    {
        $this->field('competency_template_id', [
            'type' => 'hidden',
            'entity' => $entity,
            'extra' => $extra
        ]);
        $this->field('academic_period_id', [
            'type' => 'select',
            'entity' => $entity,
            'extra' => $extra
        ]);
        $this->field('name', [
            'type' => 'text',
            'entity' => $entity
        ]);

        $this->setFieldOrder([
            'academic_period_id', 'competency_template_id', 'name'
        ]);
    }

    public function getAcademicPeriodOptions($querystringPeriod)
    {
        $periodOptions = $this->AcademicPeriods->getYearList();

        if ($querystringPeriod) {
            $selectedPeriod = $querystringPeriod;
        } else {
            $selectedPeriod = $this->AcademicPeriods->getCurrent();
        }

        return compact('periodOptions', 'selectedPeriod');
    }

    public function getItemByTemplateAcademicPeriod($selectedTemplate, $selectedPeriod)
    {
        return $this
                ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
                ->where([
                    $this->aliasField('academic_period_id') => $selectedPeriod,
                    $this->aliasField('competency_template_id') => $selectedTemplate
                ])
                ->toArray();
    }
}
