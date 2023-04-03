<?php
namespace Competency\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\Utility\Inflector;

use App\Model\Table\ControllerActionTable;

class CompetencyTemplatesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->hasMany('Items', ['className' => 'Competency.CompetencyItems', 'foreignKey' => ['competency_template_id', 'academic_period_id'], 'dependent' => true, 'cascadeCallbacks' => true, 'bindingKey' => ['id', 'academic_period_id']]);
        $this->hasMany('Periods', ['className' => 'Competency.CompetencyPeriods', 'foreignKey' => ['competency_template_id', 'academic_period_id'], 'dependent' => true, 'cascadeCallbacks' => true, 'bindingKey' => ['id', 'academic_period_id']]);
        $this->hasMany('Criterias', ['className' => 'Competency.CompetencyCriterias', 'foreignKey' => ['competency_template_id', 'academic_period_id'], 'dependent' => true, 'cascadeCallbacks' => true, 'bindingKey' => ['id', 'academic_period_id']]);
        $this->hasMany('InstitutionCompetencyResults', ['className' => 'Institution.InstitutionCompetencyResults', 'foreignKey' => ['competency_template_id', 'academic_period_id'], 'dependent' => true, 'cascadeCallbacks' => true, 'bindingKey' => ['id', 'academic_period_id']]);
        $this->hasMany('CompetencyPeriodComments', ['className' => 'Institution.InstitutionCompetencyPeriodComments', 'foreignKey' => ['competency_template_id', 'academic_period_id'], 'bindingKey' => ['id', 'academic_period_id'], 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('CompetencyItemComments', ['className' => 'Institution.InstitutionCompetencyItemComments', 'foreignKey' => ['competency_template_id', 'academic_period_id'], 'bindingKey' => ['id', 'academic_period_id'], 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->addBehavior('Restful.RestfulAccessControl', [
            'StudentCompetencies' => ['view'],
            'StudentCompetencyComments' => ['view']
        ]);

        $this->setDeleteStrategy('restrict');
        $this->addBehavior('Import.ImportLink', ['import_model'=>'ImportCompetencyTemplates']);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('code', [
                'ruleUniqueCode' => [
                    'rule' => ['validateUnique', ['scope' => 'academic_period_id']],
                    'provider' => 'table'
                ]
            ]);
    }
    

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        if ($this->action == 'index' || $this->action == 'add') {
            $this->controller->getCompetencyTabs();
        }
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriodOptions($this->request->query('period')));

        $extra['selectedPeriod'] = $selectedPeriod;
        $extra['elements']['control'] = [
            'name' => 'Competency.templates_controls',
            'data' => [
                'periodOptions'=> $periodOptions,
                'selectedPeriod'=> $selectedPeriod,
            ],
            'order' => 3
        ];

        $this->field('type', [
            'visible' => false
        ]);


        // Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Administration','Import Competency Templates','Competencies');       
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
        $queryString = ['queryString' => $this->paramsEncode(['competency_template_id' => $entity->id, 'academic_period_id' => $entity->academic_period_id])];
        $this->controller->getCompetencyTemplateTabs($queryString);
        $header = $entity->name . ' - ' . __('Overview');
        $this->controller->set('contentHeader', $header);
        $this->controller->Navigation->substituteCrumb(Inflector::humanize(Inflector::underscore($this->alias())), $header);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $queryString = ['queryString' => $this->paramsEncode(['competency_template_id' => $entity->id, 'academic_period_id' => $entity->academic_period_id])];
        $this->controller->getCompetencyTemplateTabs($queryString);
        $header = $entity->name . ' - ' . __('Overview');
        $this->controller->set('contentHeader', $header);
        $this->controller->Navigation->substituteCrumb($this->alias(), $header);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->where([$this->aliasField('academic_period_id') => $extra['selectedPeriod']]);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('academic_period_id', [ 
            'type' => 'hidden',
            'select' => false,
            'entity' => $entity
        ]);
        $this->field('education_programme_id', [
            'type' => 'select',
            'entity' => $entity
        ]);
        $this->field('education_grade_id', [
            'type' => 'select',
            'entity' => $entity
        ]);

        $this->setFieldOrder([
            'code', 'name', 'description', 'academic_period_id', 'education_programme_id', 'education_grade_id'//, 'assessment_items'
        ]);
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriodOptions($this->request->query('period')));
            $attr['value'] = $selectedPeriod; //POCOR-7066
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

        if ($action == 'view') {
            $attr['visible'] = false;
        } else if ($action == 'add') {
			$AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
			if(!empty($this->request->query('period')) && empty($request->data($this->aliasField('academic_period_id')))) {
				$academicPeriodId = $this->request->query('period');
			} else {
                $academicPeriodId = !empty($request->data($this->aliasField('academic_period_id'))) ? $request->data($this->aliasField('academic_period_id')) : $AcademicPeriod->getCurrent();	//POCOR-7066				
			}	
			
			$programmeOptions = $EducationProgrammes
                    ->find('list', ['keyField' => 'id', 'valueField' => 'cycle_programme_name'])
                    ->find('availableProgrammes')
					->contain(['EducationCycles.EducationLevels.EducationSystems'])
                    ->where(['EducationSystems.academic_period_id' => $academicPeriodId])
					->toArray();	

            $attr['options'] = $programmeOptions;
            $attr['onChangeReload'] = 'changeEducationProgrammeId';

        } else if ($action == 'edit') {
            //since programme_id is not stored, then during edit need to get from grade
            $programmeId = $this->EducationGrades->get($attr['entity']->education_grade_id)->education_programme_id;
            $attr['type'] = 'readonly';
            $attr['value'] = $programmeId;
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
                    ->contain(['EducationProgrammes'])
                    ->where([$this->EducationGrades->aliasField('education_programme_id') => $selectedProgramme])
                    ->order(['EducationProgrammes.order' => 'ASC', $this->EducationGrades->aliasField('order') => 'ASC'])
                    ->toArray();
            }

            $attr['options'] = $gradeOptions;

        } else if ($action == 'edit') {

            $attr['type'] = 'readonly';
            $attr['attr']['value'] = $this->EducationGrades->get($attr['entity']->education_grade_id)->name;
        }

        return $attr;
    }

    //Start:POCOR-7066
    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $RecordAlready = $this->find()->where(['education_grade_id'=> $entity->education_grade_id, 'academic_period_id'=>$entity->academic_period_id])->first();
        if(!empty($RecordAlready)){
            $entity->alreayexit = 1;
            $this->Alert->error('CopyData.alreadyexist', ['reset' => true]);
            return false;
        }else{
            $entity->alreayexit = 0;
        }
    }
    //End:POCOR-7066

    public function addAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
    {  
        if (empty($entity->errors())) {
            $extra['redirect'] = [
                'plugin' => 'Competency',
                'controller' => 'Competencies',
                'action' => 'Items',
                '0' => 'index',
                'template' => $entity->id,
                'period' => $entity->academic_period_id
            ];

            $pass = $this->paramsEncode(['id' => $entity->id, 'academic_period_id' => $entity->academic_period_id]);
            $url = $this->url('view');
            $url[] = $pass;
            $extra['redirect'] = $this->setQueryString($url, ['competency_template_id' => $entity->id, 'academic_period_id' => $entity->academic_period_id]);
            //Start:POCOR-7066
            if($entity->alreayexit == 1){
                $this->Alert->error('Templates.alreadyexist', ['reset' => true]);
            }else{
                $this->Alert->success('Templates.addSuccess', ['reset' => true]);
            }
            //End:POCOR-7066
            
        }
        
        
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
}
