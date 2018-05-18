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

class OutcomeTemplatesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);

        $this->hasMany('Periods', [
            'className' => 'Outcome.OutcomePeriods',
            'foreignKey' => ['outcome_template_id', 'academic_period_id'],
            'bindingKey' => ['id', 'academic_period_id'],
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->hasMany('Criterias', [
            'className' => 'Outcome.OutcomeCriterias',
            'foreignKey' => ['outcome_template_id', 'academic_period_id'],
            'bindingKey' => ['id', 'academic_period_id'],
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->hasMany('InstitutionOutcomeResults', [
            'className' => 'Institution.InstitutionOutcomeResults',
            'foreignKey' => ['outcome_template_id', 'academic_period_id'],
            'bindingKey' => ['id', 'academic_period_id'],
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->hasMany('InstitutionOutcomeSubjectComments', [
            'className' => 'Institution.InstitutionOutcomeSubjectComments',
            'foreignKey' => ['outcome_template_id', 'academic_period_id'],
            'bindingKey' => ['id', 'academic_period_id'],
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->addBehavior('Restful.RestfulAccessControl', [
            'StudentOutcomes' => ['view']
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
            ]);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        if ($this->action == 'index' || $this->action == 'add') {
            $this->controller->getOutcomeTabs();
        }
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        // academic period filter
        $periodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
        $selectedPeriod = !is_null($this->request->query('period')) ? $this->request->query('period') : $this->AcademicPeriods->getCurrent();
        $this->controller->set(compact('periodOptions', 'selectedPeriod'));
        $conditions[$this->aliasField('academic_period_id')] = $selectedPeriod;

        $extra['elements']['controls'] = ['name' => 'Outcome.templates_controls', 'data' => [], 'options' => [], 'order' => 1];

        $query->where($conditions);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        // set tabs
        $queryString = ['queryString' => $this->paramsEncode(['outcome_template_id' => $entity->id, 'academic_period_id' => $entity->academic_period_id])];
        $this->controller->getOutcomeTemplateTabs($queryString);

        // set header
        $header = $entity->name . ' - ' . __('Overview');
        $this->controller->set('contentHeader', $header);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        // set tabs
        $queryString = ['queryString' => $this->paramsEncode(['outcome_template_id' => $entity->id, 'academic_period_id' => $entity->academic_period_id])];
        $this->controller->getOutcomeTemplateTabs($queryString);

        // set header
        $header = $entity->name . ' - ' . __('Overview');
        $this->controller->set('contentHeader', $header);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('academic_period_id', ['entity' => $entity]);
        $this->field('education_programme_id', ['entity' => $entity]);
        $this->field('education_grade_id', ['entity' => $entity]);
        $this->setFieldOrder([
            'code', 'name', 'description', 'academic_period_id', 'education_programme_id', 'education_grade_id'
        ]);
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
        } else if ($action == 'edit') {
            $academicPeriodId = $attr['entity']->academic_period_id;
            $attr['type'] = 'readonly';
            $attr['value'] = $academicPeriodId;
            $attr['attr']['value'] = $this->AcademicPeriods->get($academicPeriodId)->name;
        }
        return $attr;
    }

    public function onUpdateFieldEducationProgrammeId(Event $event, array $attr, $action, Request $request)
    {
        $EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');

        if ($action == 'add') {
            $programmeOptions = $EducationProgrammes
                ->find('list', ['keyField' => 'id', 'valueField' => 'cycle_programme_name'])
                ->find('availableProgrammes')
                ->toArray();

            $attr['type'] = 'select';
            $attr['options'] = $programmeOptions;
            $attr['onChangeReload'] = 'changeEducationProgrammeId';
        } else if ($action == 'edit') {
            $gradeId = $attr['entity']->education_grade_id;
            $programmeId = $this->EducationGrades->get($gradeId)->education_programme_id;

            $attr['type'] = 'readonly';
            $attr['attr']['value'] = $EducationProgrammes->get($programmeId)->name;
        }
        return $attr;
    }

    public function addEditOnChangeEducationProgrammeId(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;
        unset($request->query['programme']);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('education_programme_id', $request->data[$this->alias()])) {
                    $request->query['programme'] = $request->data[$this->alias()]['education_programme_id'];
                }
            }
        }
    }

    public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $selectedProgramme = $request->query('programme');

            $gradeOptions = [];
            if (!is_null($selectedProgramme)) {
                $gradeOptions = $this->EducationGrades
                    ->find('list')
                    ->find('visible')
                    ->contain('EducationProgrammes')
                    ->where([$this->EducationGrades->aliasField('education_programme_id') => $selectedProgramme])
                    ->order(['EducationProgrammes.order', $this->EducationGrades->aliasField('order')])
                    ->toArray();
            }
            $attr['type'] = 'select';
            $attr['options'] = $gradeOptions;

        } else if ($action == 'edit') {
            $gradeId = $attr['entity']->education_grade_id;

            $attr['type'] = 'readonly';
            $attr['value'] = $gradeId;
            $attr['attr']['value'] = $this->EducationGrades->get($gradeId)->name;
        }
        return $attr;
    }

    public function addAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
    {
        if (empty($entity->errors())) {
            // set redirect url to view page
            $url = $this->url('view');
            $url[1] = $this->paramsEncode(['id' => $entity->id, 'academic_period_id' => $entity->academic_period_id]);
            $extra['redirect'] = $url;

            $this->Alert->success('OutcomeTemplates.addSuccess', ['reset' => true]);
        }
    }
}
