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

class OutcomePeriodsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Templates', [
            'className' => 'Outcome.OutcomeTemplates',
            'foreignKey' => ['outcome_template_id', 'academic_period_id'],
            'bindingKey' => ['id', 'academic_period_id']
        ]);

        $this->hasMany('InstitutionOutcomeResults', [
            'className' => 'Institution.InstitutionOutcomeResults',
            'foreignKey' => ['outcome_period_id', 'academic_period_id'],
            'bindingKey' => ['id', 'academic_period_id'],
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->hasMany('InstitutionOutcomeSubjectComments', [
            'className' => 'Institution.InstitutionOutcomeSubjectComments',
            'foreignKey' => ['outcome_period_id', 'academic_period_id'],
            'bindingKey' => ['id', 'academic_period_id'],
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->setDeleteStrategy('restrict');
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        return $validator
            ->add('code', 'ruleUniqueCode', [
                'rule' => ['validateUnique', ['scope' => 'academic_period_id']],
                'provider' => 'table'
            ])
            ->add('start_date', 'ruleCompareDate', [
                'rule' => ['compareDate', 'end_date', true]
            ])
            ->add('date_enabled', 'ruleCompareDate', [
                'rule' => ['compareDate', 'date_disabled', true]
            ]);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->controller->getOutcomeTabs();
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        // academic period filter
        $periodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
        $selectedPeriod = !is_null($this->request->query('period')) ? $this->request->query('period') : $this->AcademicPeriods->getCurrent();
        $this->controller->set(compact('periodOptions', 'selectedPeriod'));
        $conditions[$this->aliasField('academic_period_id')] = $selectedPeriod;

        // outcome template filter
        $templateOptions = $this->Templates
            ->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
            ->where([$this->Templates->aliasField('academic_period_id') => $selectedPeriod])
            ->order([$this->Templates->aliasField('code')])
            ->toArray();
        if (!empty($templateOptions)) {
            $templateOptions = ['0' => '-- '.__('All Templates').' --'] + $templateOptions;
        }

        $selectedTemplate = !is_null($this->request->query('template')) ? $this->request->query('template') : 0;
        $this->controller->set(compact('templateOptions', 'selectedTemplate'));
        if (!empty($selectedTemplate)){
            $conditions[$this->aliasField('outcome_template_id')] = $selectedTemplate;
        }

        $extra['elements']['controls'] = ['name' => 'Outcome.periods_controls', 'data' => [], 'options' => [], 'order' => 1];

        $query->where($conditions);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setFieldOrder(['outcome_template_id', 'code', 'name', 'start_date', 'end_date', 'date_enabled', 'date_disabled']);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('academic_period_id', ['entity' => $entity]);
        $this->field('outcome_template_id', ['entity' => $entity]);

        $this->setFieldOrder(['academic_period_id', 'outcome_template_id', 'code', 'name', 'start_date', 'end_date', 'date_enabled', 'date_disabled']);
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $periodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
            $selectedPeriod = $this->AcademicPeriods->getCurrent();

            $attr['type'] = 'select';
            $attr['select'] = false;
            $attr['options'] = $periodOptions;
            $attr['default'] = $selectedPeriod;
            $attr['onChangeReload'] = 'changeAcademicPeriod';

        } else if ($action == 'edit') {
            $periodId = $attr['entity']->academic_period_id;

            $attr['type'] = 'readonly';
            $attr['value'] = $periodId;
            $attr['attr']['value'] = $this->AcademicPeriods->get($periodId)->name;
        }
        return $attr;
    }

    public function addEditOnChangeAcademicPeriod(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;
        unset($request->query['period']);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('academic_period_id', $request->data[$this->alias()])) {
                    $request->query['period'] = $request->data[$this->alias()]['academic_period_id'];
                }
            }
        }
    }

    public function onUpdateFieldOutcomeTemplateId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $selectedPeriod = !is_null($request->query('period')) ? $request->query('period') : $this->AcademicPeriods->getCurrent();

            $templateOptions = $this->Templates
                ->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
                ->where([$this->Templates->aliasField('academic_period_id') => $selectedPeriod])
                ->toArray();

            $attr['type'] = 'select';
            $attr['options'] = $templateOptions;

        } else if ($action == 'edit') {
            $templateId = $attr['entity']->outcome_template_id;
            $periodId = $attr['entity']->academic_period_id;

            $attr['type'] = 'readonly';
            $attr['value'] = $templateId;
            $attr['attr']['value'] = $this->Templates->get(['id' => $templateId, 'academic_period_id' => $periodId])->code_name;
        }
        return $attr;
    }
}
