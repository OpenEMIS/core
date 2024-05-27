<?php
namespace Outcome\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\Http\ServerRequest;

use App\Model\Table\ControllerActionTable;

class OutcomePeriodsTable extends ControllerActionTable
{
    public function initialize(array $config): void
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

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        return $validator
            ->add('code', 'ruleUniqueCode', [
                'rule' => ['validateUnique', ['scope' => 'academic_period_id']],
                'provider' => 'table'
            ]);
            // ->add('start_date', 'ruleCompareDate', [
            //     'rule' => ['compareDate', 'end_date', true]
            // ])
            // ->add('date_enabled', 'ruleCompareDate', [
            //     'rule' => ['compareDate', 'date_disabled', true]
            // ]);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->controller->getOutcomeTabs();
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        // academic period filter
        $serverRequest = new ServerRequest();
        $periodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
        $selectedPeriod = !is_null($serverRequest->getAttribute('query')['period']) ? $serverRequest->getAttribute('query')['period'] : $this->AcademicPeriods->getCurrent();
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

        $selectedTemplate = !is_null($serverRequest->getAttribute('query')['template']) ? $serverRequest->getAttribute('query')['template'] : 0;
        $this->controller->set(compact('templateOptions', 'selectedTemplate'));
        if (!empty($selectedTemplate)){
            $conditions[$this->aliasField('outcome_template_id')] = $selectedTemplate;
        }

        $extra['elements']['controls'] = ['name' => 'Outcome.periods_controls', 'data' => [], 'options' => [], 'order' => 1];

        $query->where($conditions);

        // Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Administration','Periods','Learning Outcomes');       
		if(!empty($is_manual_exist)){
			$btnAttr = [
				'class' => 'btn btn-xs btn-default icon-big',
				'data-toggle' => 'tooltip',
				'data-placement' => 'bottom',
				'escape' => false,
				'target'=>'_blank'
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

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, ServerRequest $request)
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
            if (array_key_exists($this->getAlias(), $request->data)) {
                if (array_key_exists('academic_period_id', $request->data[$this->getAlias()])) {
                    $request->query['period'] = $request->data[$this->getAlias()]['academic_period_id'];
                }
            }
        }
    }

    public function onUpdateFieldOutcomeTemplateId(Event $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $selectedPeriod = !is_null($request->getQuery('period')) ? $request->getQuery('period') : $this->AcademicPeriods->getCurrent();

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

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'academic_period_id') {
            return __('Academic Period');
        } elseif ($field == 'outcome_template_id') {
            return __('Outcome Template');
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
        }  elseif ($field == 'modified_user_id') {
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
