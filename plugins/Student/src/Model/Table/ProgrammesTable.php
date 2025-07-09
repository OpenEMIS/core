<?php

namespace Student\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\Http\ServerRequest;
use Cake\ORM\Query;
use Cake\ORM\ResultSet;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Datasource\ResultSetInterface;

use App\Model\Table\ControllerActionTable;

class ProgrammesTable extends ControllerActionTable
{
	public function initialize(array $config): void
	{
		$this->setTable('institution_students');
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
		$this->addBehavior('Institution.InstitutionTab', [
			'appliedAction' => [
                // POCOR-8980 start
				'StudentProgrammes' => ['id',
                    'education_programme_id',
                    'institution_id',
                    'student_id']
                // POCOR-8980 end
			]
		]);
		// $this->addBehavior('Student.StudentTab', [
		//     'appliedAction' => ['StudentProgrammes' =>['id','education_programme_id']
		//     ]
		// ]);

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
		$studentWithdraw = TableRegistry::getTableLocator()->get('Institution.StudentWithdraw');
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

    // POCOR-8980
	public function onGetInstitution(Event $event, Entity $entity)
	{

		return $entity->institution->code_name;
	}

	//POCOR-8870 start
	public function onGetRegistrationNumber(Event $event, Entity $entity)
	{
		$InstitutionStudentProgrammesTable = TableRegistry::get('Student.InstitutionStudentProgrammes');
		// Find existing record
		$institutionStudentProgramme = $InstitutionStudentProgrammesTable
		->find()
		->where(['education_programme_id' => $entity->education_grade->education_programme_id,'student_id' => $entity->student_id,'institution_id'=>$entity->institution_id])
		->first();
		return $institutionStudentProgramme->registration_number;
	}
	//POCOR-8870 end

	public function beforeAction(Event $event, ArrayObject $extra)
	{
		$this->field('previous_institution_student_id', ['visible' => false]);
		$this->field('registration_number', ['after'=>'student_id' , 'visible' => true]);
	}


	public function indexBeforeAction(Event $event, ArrayObject $extra)
	{
		$this->fields['student_id']['visible'] = 'false';
		$this->fields['start_year']['visible'] = 'false';
		$this->fields['end_year']['visible'] = 'false';
		$this->fields['photo_content']['visible'] = 'false';
		$this->fields['openemis_no']['visible'] = 'false';
		$this->fields['institution_id']['type'] = 'hidden'; // POCOR-8980 start
		$this->field('institution'); // POCOR-8980 end
		// $this->fields['academic_period_id']['sort'] = ['field' => 'AcademicPeriods.name'];
		$this->fields['academic_period_id']['sort'] = ['field' => 'academic_period_id']; //POCOR-9170
		$this->fields['registration_number']['visible'] = 'false'; //POCOR-8870

		$this->setFieldOrder([
			'academic_period_id',
			'institution_id',
			'institution', // POCOR-8980
			'education_grade_id',
			'start_date',
			'end_date',
			'student_status_id'
		]);

		// Start POCOR-5188
		if ($this->request->getParam('controller') == 'Institutions') {
			$is_manual_exist = $this->getManualUrl('Institutions', 'Programmes', 'Students - Academic');
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
		} else if ($this->request->getParam('controller') == 'Students') {
			$is_manual_exist = $this->getManualUrl('Institutions', 'Programmes', 'Students - Academic');
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
		} elseif ($this->request->getParam('controller') == 'Directories') {
			$is_manual_exist = $this->getManualUrl('Directory', 'Programmes', 'Students - Academic');
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
		}
		// End POCOR-5188
	}

	public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
	{
		$session = $this->request->getSession();

		if ($this->controller->getName() == 'Profiles') {
			if ($session->read('Auth.User.is_guardian') == 1) {
				$sId = $session->read('Student.ExaminationResults.student_id');
			} else {
				$sId = $this->getStudentID();
			}
			if (!empty($sId)) {
                $studentId = $this->paramsDecode($sId); // POCOR-8980
				if ($studentId['id']) {
					$studentId = $studentId['id']; // POCOR-8980
				}
			} else {
				$studentId = $this->getUserID();
			}
		} else {
			$queryString = $this->getQueryString();
			$studentId = $queryString['student_id'];
			if ($this->controller->getName() == 'GuardianNavs' && isset($this->request->getQueryParams()['studentId'])) {
				//POCOR-8379 starts
				$session = $this->request->getSession();
				$studentId = $session->read('Student.Students.id');
				if (empty($studentId)) {
					$encodeStudentId = $this->request->getQueryParams()['studentId'];
					$studentId = $this->paramsDecode($encodeStudentId);
				} //POCOR-8379 ends
			}
		}
		if (empty($studentId)) { //POCOR-8316
			$studentId = $this->Auth->user('id');
		}
		// end POCOR-1893
		$sortList = ['AcademicPeriods.name'];

		if (array_key_exists('sortWhitelist', $extra['options'])) {
			$sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
		}
		$extra['options']['sortWhitelist'] = $sortList;
		$institutionId = $this->getInstitutionID();
		$query
			->where([
				$this->aliasField('student_id') => $studentId,
				//$this->aliasField('institution_id') => $institutionId
			]);
		//POCOR-8704 -- Commenting code as it is fetching limited data
		// if(!empty($institutionId)) {
		// 	$query
		// 	->where([
		// 		$this->aliasField('institution_id') => $institutionId
		// 	]);
		// }
		//POCOR-8704 -- END
		$extra['auto_contain_fields'] = ['Institutions' => ['code']];
	}

	public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
	{
		$this->field('photo_content', ['type' => 'image', 'before' => 'openemis_no']);
		$this->field('openemis_no', ['before' => 'student_id']);
		$this->field('student_status_id', ['after' => 'student_id']);
		$this->field('start_year', ['visible' => 'false']);
		$this->field('end_year', ['visible' => 'false']);

		$this->setupTabElements();
		//POCOR-5671
		$statuses = $this->StudentStatuses->findCodeList();
		$studentStatusId = $entity->student_status_id;
		if ($studentStatusId == $statuses['CURRENT']) {
			$queryString   = $this->getQueryString();
			$institutionId  = $queryString['institution_id'];
			$entity->institution->id = $institutionId;
			$encodedQueryString = $this->paramsEncode($queryString);

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
						'url' => [
							0 => 'edit',
							1 => $encodedQueryString
						]
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
        // POCOR-8980 start
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        // POCOR-9097 start
        if ($entity->student_id) {
            $studentId = $entity->student_id;
        }
        if ($entity->institution_id) {
            $institutionId = $entity->institution_id;
        }
        if(!$institutionId) { // POCOR-9097
            $queryString = $this->getQueryString();
            $institutionId = $queryString['institution_id'];
        }
        if(!$studentId) { // POCOR-9097
            $queryString = $this->getQueryString(); // POCOR-9097
            $studentId = $queryString['student_id'];
        }
        if ($institutionId && !$entity->institution) { // POCOR-9097
             $result = $this->Institutions
				->find()
				->where(['id' =>  $institutionId])
				->first();
			$entity->institution = $result;
		}
        $queryString['institution_id'] = $institutionId;
        $queryString['student_id'] = $studentId;
        $queryString['id'] = $entity->id; // POCOR-9097
		$encodedQueryString = $this->paramsEncode($queryString);
//        dd($queryString);
		if (isset($buttons['view'])) {
			$url = [
				'plugin' => 'Institution',
				'controller' => 'Institutions',
				'action' => 'StudentProgrammes',
				'view',
				$encodedQueryString
			];
			$buttons['view']['url'] = $url;
            $buttons['view']['attr']['title'] = $institutionId;
            $buttons['view']['title'] = $institutionId;
		}

		$statuses = $this->StudentStatuses->findCodeList();
		$studentStatusId = $entity->student_status_id;

		if (isset($buttons['edit']) && $studentStatusId == $statuses['CURRENT']) {
			$url = [
				'plugin' => 'Institution',
				'controller' => 'Institutions',
				'action' => 'StudentProgrammes',
				'edit',
				$encodedQueryString
			];
			$buttons['edit']['url'] = $url;
		} else {
			if (isset($buttons['edit'])) {
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
                $encodedQueryString // POCOR-9097
			];
			$buttons['transition'] = $buttons['view'];
			$buttons['transition']['label'] = $icon . __('Transition');
			$buttons['transition']['url'] = $url;
		}
		//POCOR-5671
		return $buttons;
        // POCOR-9097 end
        // POCOR-8980 end
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
		$tabElements = $this->getAcademicTabElements($options);
		if ($this->controller->getName() == 'GuardianNavs' || $this->controller->getName() == 'Directories') {
			$tabElements = $this->controller->getAcademicTabElements($options);
		}
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->getAlias());
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

			//POCOR-8870 start
			$InstitutionStudentProgrammesTable = TableRegistry::get('Student.InstitutionStudentProgrammes');
			// Find existing record
			$institutionStudentProgramme = $InstitutionStudentProgrammesTable
			->find()
			->where(['education_programme_id' => $entity->education_grade->education_programme_id,'student_id' => $entity->student_id,'institution_id'=>$entity->institution_id])
			->first();

			$this->field('registration_number',['attr' => ['value' => $institutionStudentProgramme->registration_number]]);
			//POCOR-8870 end

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

	// POCOR-8870 start
	public function editAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
	{
		if ($this->action == 'edit') {
			$InstitutionStudentProgrammesTable = TableRegistry::get('Student.InstitutionStudentProgrammes');
			// Find existing record or create a new one
			$institutionStudentProgramme = $InstitutionStudentProgrammesTable
				->find()
				->where(['education_programme_id' => $entity->education_grade->education_programme_id])
				->first();

			if (!$institutionStudentProgramme) {
				$institutionStudentProgramme = $InstitutionStudentProgrammesTable->newEmptyEntity();
				$institutionStudentProgramme->education_programme_id = $entity->education_grade->education_programme_id;
			}

			// Set values
			$institutionStudentProgramme->student_id = $entity->student_id;
			$institutionStudentProgramme->registration_number = $entity->registration_number;
			$institutionStudentProgramme->institution_id = $entity->institution_id;

			$InstitutionStudentProgrammesTable->save($institutionStudentProgramme);

		}
	}
	//POCOR-8870 end

	//POCOR-8414 start
	public function afterAction(Event $event, ArrayObject $options)
	{
		$plugin = __($this->controller->getPlugin());
		if ($plugin != 'Profile' && $plugin != 'GuardianNav') {
			$id = $this->request->getAttribute('params')['pass'][1];
			//POCOR-8489 --Start
			if (isset($id)) {
				$DecodedQueryString = $this->paramsDecode($id);
				$userId = $DecodedQueryString['user_id'] ?? $DecodedQueryString['student_id'];
			} else {
				$queryString = $this->getQueryString();
				$userId = $queryString['student_id'];
			}
			//POCOR-8489 --End
			$Users = TableRegistry::get('User.Users');
			$result = $Users
				->find()
				->select(['first_name', 'last_name'])
				->where(['id' =>  $userId])
				->first();
			$fullName = $result->first_name . ' ' . $result->last_name;
			try {

				$gettabName = 'Student Programmes';
				$this->controller->set('contentHeader', $fullName . ' - ' . $gettabName);
				//$this->controller->set('contentHeader', $plugin);
			} catch (RecordNotFoundException $e) {
				Log::write('error', $e->getMessage());
			}
		}
	}

	public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
	{
		$LabelTable = TableRegistry::get('Labels');
		if ($field == 'name') {
			return __('Name');
		}elseif ($field == 'registration_number') { //POCOR-9125, POCOR-9048
		   $codeName = $LabelTable->find()->where(['module_name' =>'Institution-> Students-> Academic-> Programme' , 'field_name' =>'Registration Number'])->first();
		   if(empty($codeName->name)){
					$fieldName = $LabelTable->find()->where(['module_name' =>'Institution-> Students-> Academic-> Programme' , 'field' =>'registration_number'])->first();
					$fieldName =  $fieldName->field_name;
					return  __((string)$fieldName);
			}else{
				$codeName =  $codeName->name;
				return  __((string)$codeName);
		 	}
		}else {
			return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
		}
	}
}
