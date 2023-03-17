<?php
namespace Student\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Query;
use Cake\ORM\ResultSet;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Datasource\ResultSetInterface;

use App\Model\Table\ControllerActionTable;

class ProgrammesTable extends ControllerActionTable
{
	public function initialize(array $config)
	{
		$this->table('institution_students');
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
		$this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('PreviousInstitutionStudents', ['className' => 'Institution.Students', 'foreignKey' => 'previous_institution_student_id']);

		$this->toggle('remove', false);
		$this->toggle('add', false);
		$this->toggle('search', false);

        $this->addBehavior('User.User');

	}

	public function onGetEducationGradeId(Event $event, Entity $entity)
	{
		return $entity->education_grade->programme_grade_name;
	}

	//POCOR-5742 starts
	public function onGetEndDate(Event $event, Entity $entity)
	{ 
		$studentId = $entity->student_id;
		$gradeId = $entity->education_grade->id;
		$periodId = $entity->academic_period_id;
		$studentWithdraw = TableRegistry::get('institution_student_withdraw');
		$record = $studentWithdraw->find()
					->select([$studentWithdraw->aliasField('effective_date')])
					->where([
						$studentWithdraw->aliasField('student_id') => $studentId,
						$studentWithdraw->aliasField('academic_period_id') => $periodId,
						$studentWithdraw->aliasField('education_grade_id') => $gradeId
					])
					->first();
		$statuses = $this->StudentStatuses->findCodeList();
		$studentStatusId = $entity->student_status->id;
		if (!empty($record) && $studentStatusId == $statuses['WITHDRAWN']) {
			$endDate = date("F d, Y", strtotime($record->effective_date));
			return $endDate;
		} else {
			$endDate = date("F d, Y", strtotime($entity->end_date));
			return $endDate;
		}
	}
	//POCOR-5742 ends

	public function onGetInstitutionId(Event $event, Entity $entity)
	{
		return $entity->institution->code_name;
	}

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('previous_institution_student_id', ['visible' => false]);
    }

	public function indexBeforeAction(Event $event, ArrayObject $extra)
	{
		$this->fields['student_id']['visible'] = 'false';
		$this->fields['start_year']['visible'] = 'false';
		$this->fields['end_year']['visible'] = 'false';
		$this->fields['photo_content']['visible'] = 'false';
		$this->fields['openemis_no']['visible'] = 'false';
		$this->fields['institution_id']['type'] = 'integer';
		$this->fields['academic_period_id']['sort'] = ['field' => 'AcademicPeriods.name'];

		$this->setFieldOrder([
			'academic_period_id', 'institution_id', 'education_grade_id', 'start_date', 'end_date', 'student_status_id'
		]);

		// Start POCOR-5188
		if($this->request->params['controller'] == 'Institutions'){
			$is_manual_exist = $this->getManualUrl('Institutions','Programmes','Students - Academic');       
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
		}else if($this->request->params['controller'] == 'Students'){
			$is_manual_exist = $this->getManualUrl('Institutions','Programmes','Students - Academic');       
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
		}elseif($this->request->params['controller'] == 'Directories'){ 
			$is_manual_exist = $this->getManualUrl('Directory','Programmes','Students - Academic');       
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

		}
		// End POCOR-5188
	}

	public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
	{	
		$session = $this->request->session();
		if ($this->controller->name == 'Profiles') {
			if ($session->read('Auth.User.is_guardian') == 1) {
				$sId = $session->read('Student.ExaminationResults.student_id');
			}else {
				$sId = $session->read('Student.Students.id');
			}
			if (!empty($sId)) {
				if ($studentId['id']) {					
					$studentId = $this->ControllerAction->paramsDecode($sId)['id'];
				}
			} else {
				$studentId = $session->read('Auth.User.id');
			}
		} else {
				$studentId = $session->read('Student.Students.id');
		}
		
		// end POCOR-1893
		$sortList = ['AcademicPeriods.name'];
		
        if (array_key_exists('sortWhitelist', $extra['options'])) {
            $sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
        }
        $extra['options']['sortWhitelist'] = $sortList;
        $institutionId = $this->Session->read('Institution.Institutions.id');
        $query
        		->where([
        			$this->aliasField('student_id') => $studentId,
        			//$this->aliasField('institution_id') => $institutionId
        		]);
        $extra['auto_contain_fields'] = ['Institutions' => ['code']];
        
	}

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {  	
        $this->field('photo_content', ['type' => 'image', 'before' => 'openemis_no']);
        $this->field('openemis_no',['before' => 'student_id']);
        $this->field('student_status_id',['after' => 'student_id']);
        $this->field('start_year', ['visible' => 'false']);
        $this->field('end_year', ['visible' => 'false']);
        $this->setupTabElements();
        //POCOR-5671 
        $statuses = $this->StudentStatuses->findCodeList();
		$studentStatusId = $entity->student_status_id;
		if ($studentStatusId == $statuses['CURRENT']) {
			$institutionId = $this->Session->read('Institution.Institutions.id');

	        $btnAttr = [
	            'class' => 'btn btn-xs btn-default icon-big',
	            'data-toggle' => 'tooltip',
	            'data-placement' => 'bottom',
	            'escape' => false
	        ];
	 
	        $extraButtons = [
	            'process' => [
	                'Institution' => ['Institution', 'Institutions'],
	                'action' => 'StudentTransition',
	                'icon' => '<i class="kd-process"></i>',
	                'title' => __('Transition')
	            ]
	        ];

	        foreach ($extraButtons as $key => $attr) {
	            if ($this->AccessControl->check($attr['permission'])) {
	                $button = [
	                    'type' => 'button',
	                    'attr' => $btnAttr,
	                    'url' => [0 => 'edit', $this->paramsEncode(['id' => $entity->id]),
					'institution_id' => $entity->institution->id]
	                ];
	                $button['url']['action'] = $attr['action'];
	                $button['attr']['title'] = $attr['title'];
	                $button['label'] = $attr['icon'];

	                $extra['toolbarButtons'][$key] = $button;
	            }
	        }
		}
        //POCOR-5671
    }

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
	{
		if (array_key_exists('view', $buttons)) {
			$url = [
				'plugin' => 'Institution',
				'controller' => 'Institutions',
				'action' => 'StudentProgrammes',
				'view',
				$this->paramsEncode(['id' => $entity->id]),
				'institution_id' => $entity->institution->id
			];
			$buttons['view']['url'] = $url;
		}

		$statuses = $this->StudentStatuses->findCodeList();
		$studentStatusId = $entity->student_status_id;

		if (array_key_exists('edit', $buttons) && $studentStatusId == $statuses['CURRENT']) {
			$url = [
				'plugin' => 'Institution',
				'controller' => 'Institutions',
				'action' => 'StudentProgrammes',
				'edit',
				$this->paramsEncode(['id' => $entity->id]),
				'institution_id' => $entity->institution->id
			];
			$buttons['edit']['url'] = $url;
		} else {
			if (array_key_exists('edit', $buttons)) {
				unset($buttons['edit']);
			}
		}
		//POCOR-5671
		if (isset($buttons['view']) && $this->AccessControl->check(['Institutions', 'StudentTransition']) && $studentStatusId == $statuses['CURRENT']) {
            $icon = '<i class="kd-process"></i>';
            $url = [
				'plugin' => 'Institution',
				'controller' => 'Institutions',
				'action' => 'StudentTransition',
				'edit',
				$this->paramsEncode(['id' => $entity->id]),
				'institution_id' => $entity->institution->id
			];
            $buttons['transition'] = $buttons['view'];
            $buttons['transition']['label'] = $icon . __('Transition');
            $buttons['transition']['url'] = $url;
        }
		//POCOR-5671
		
		return parent::onUpdateActionButtons($event, $entity, $buttons);
	}
	
    public function onGetOpenemisNo(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('user')) {
            $value = $entity->user->openemis_no;
        }
        return $value;
    }

	private function setupTabElements()
	{
		$options['type'] = 'student';
		$tabElements = $this->controller->getAcademicTabElements($options);
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->alias());
	}

	public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
	{		
		$this->setupTabElements();
	}

    public function editBeforeQuery(Event $event, Query $query)
    {
        $query->contain(['Users', 'EducationGrades', 'AcademicPeriods', 'StudentStatuses']);
    }

    public function editAfterAction(Event $event, Entity $entity)
    {
        $this->field('start_year', ['visible' => 'false']);
        $this->field('end_year', ['visible' => 'false']);

        // Start PHPOE-1897
        $statuses = $this->StudentStatuses->findCodeList();
        if ($entity->student_status_id != $statuses['CURRENT']) {
            $event->stopPropagation();
            $urlParams = $this->url('view');
            return $this->controller->redirect($urlParams);
        // End PHPOE-1897
        } else {
            $this->field('student_id', [
                'type' => 'readonly',
                'order' => 10,
                'attr' => ['value' => $entity->user->name_with_id]
            ]);

            $this->field('education_grade_id', ['type' => 'readonly', 'attr' => ['value' => $entity->education_grade->programme_grade_name]]);
            $this->field('academic_period_id', ['type' => 'readonly', 'attr' => ['value' => $entity->academic_period->name]]);
            $this->field('student_status_id', ['type' => 'readonly', 'attr' => ['value' => $entity->student_status->name]]);

            $period = $entity->academic_period;
            $dateOptions = [
                'startDate' => $period->start_date->format('d-m-Y'),
                'endDate' => $period->end_date->format('d-m-Y')
            ];

            $this->fields['start_date']['date_options'] = $dateOptions;
            $this->fields['end_date']['date_options'] = $dateOptions;

            $this->Session->write('Student.Students.id', $entity->student_id);
            $this->Session->write('Student.Students.name', $entity->user->name);
            $this->setupTabElements($entity);
        }
    }	
}
