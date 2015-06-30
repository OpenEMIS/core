<?php 
namespace Institution\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;

class UserBehavior extends Behavior {
	private $associatedModel;
	public function initialize(array $config) {
		$this->associatedModel = (array_key_exists('associatedModel', $config))? $config['associatedModel']: null;
	}

	public function beforeFind(Event $event, Query $query, $options) {
		$session = $this->_table->request->session();
		if ($session->check('Institutions.id')) {
			$institutionId = $session->read('Institutions.id');
		} 
		$query
			->where(['institution_site_id = '.$institutionId])
			;
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$newEvent = [
			// 'ControllerAction.Model.beforeAction' => 'beforeAction',
			'ControllerAction.Model.add.beforeAction' => 'addBeforeAction',
			'ControllerAction.Model.add.beforePatch' => 'addBeforePatch',
			'ControllerAction.Model.add.afterPatch' => 'addAfterPatch',
			// 'ControllerAction.Model.add.afterSaveRedirect' => 'addAfterSaveRedirect'
			'ControllerAction.Model.onUpdateFieldAcademicPeriod' => 'onUpdateFieldAcademicPeriod',
			'ControllerAction.Model.onUpdateFieldEducationProgrammeId' => 'onUpdateFieldEducationProgrammeId',
			'ControllerAction.Model.onUpdateFieldEducationGrade' => 'onUpdateFieldEducationGrade',
			'ControllerAction.Model.onUpdateFieldSection' => 'onUpdateFieldSection',
			'ControllerAction.Model.onUpdateFieldStudentStatusId' => 'onUpdateFieldStudentStatusId',

		];
		$events = array_merge($events,$newEvent);
		return $events;
	}

	public function addBeforeAction(Event $event) {
		if (array_key_exists('new', $this->_table->request->query)) {

		} else {
			foreach ($this->_table->fields as $key => $value) {
				$this->_table->fields[$key]['visible'] = false;
			}

			$associationString = $this->_table->alias().'.'.Inflector::tableize($this->associatedModel->alias()).'.0.';

			$this->_table->ControllerAction->field('academic_period', ['fieldName' => $associationString.'academic_period']);
			$this->_table->ControllerAction->field('education_programme_id', ['fieldName' => $associationString.'education_programme_id']);
			$this->_table->ControllerAction->field('education_grade', ['fieldName' => $associationString.'education_grade']);
			$this->_table->ControllerAction->field('section', ['fieldName' => $associationString.'section']);
			$this->_table->ControllerAction->field('student_status_id', ['fieldName' => $associationString.'student_status_id']);
			$this->_table->ControllerAction->field('start_date', ['type' => 'Date', 'fieldName' => $associationString.'start_date']);
			$this->_table->ControllerAction->field('end_date', ['type' => 'Date', 'fieldName' => $associationString.'end_date']);

			$session = $this->_table->request->session();
			$institutionsId = $session->read('Institutions.id');
			$this->_table->ControllerAction->field('institution_site_id', ['type' => 'hidden', 'value' => $institutionsId, 'fieldName' => $associationString.'institution_site_id']);
			// $this->_table->ControllerAction->field('search');

			$this->_table->ControllerAction->setFieldOrder([
					'academic_period', 'education_programme_id', 'education_grade', 'section', 'student_status_id', 'start_date', 'end_date'
				// , 'search'
				]);	
		}
	}

	public function addBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		if (!array_key_exists('new', $this->_table->request->query)) {
			$newOptions = [];
			$newOptions['validate'] = false;

			$arrayOptions = $options->getArrayCopy();
			$arrayOptions = array_merge_recursive($arrayOptions, $newOptions);
			$options->exchangeArray($arrayOptions);
		}

		return compact('entity', 'data', 'options');
	}

	public function addAfterPatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		if (!array_key_exists('new', $this->_table->request->query)) {
			$timeNow = strtotime("now");
			$sessionVar = $this->_table->alias().'.add.'.strtotime("now");
			$session = $this->_table->request->session();
			$session->write($sessionVar, $this->_table->request->data);

			if (!$entity->errors()) {
				$event->stopPropagation();
				return $this->_table->controller->redirect(['plugin' => 'Institution', 'controller' => $this->_table->controller->name, 'action' => $this->_table->alias(), 'add', 'new' => $timeNow]);
			}
		}
		
		return compact('entity', 'data', 'options');
	}


	public function onUpdateFieldAcademicPeriod(Event $event, array $attr, $action, $request) {
		$session = $this->_table->request->session();
		$institutionsId = $session->read('Institutions.id');
		$conditions = array(
			'InstitutionSiteProgrammes.institution_site_id' => $institutionsId
		);

		$InstitutionSiteProgramme = TableRegistry::get('Institution.InstitutionSiteProgrammes');
		$list = $InstitutionSiteProgramme->getAcademicPeriodOptions($conditions);

		$attr['type'] = 'select';
		$attr['options'] = $list;
		$attr['onChangeReload'] = 'true';

		return $attr;
	}

	public function onUpdateFieldEducationProgrammeId(Event $event, array $attr, $action, $request) {
		$session = $this->_table->request->session();
		$institutionsId = $session->read('Institutions.id');
		$this->academicPeriodId = null;
		if (array_key_exists('academic_period', $this->_table->fields)) {
			if (array_key_exists('options', $this->_table->fields['academic_period'])) {
				$this->academicPeriodId = key($this->_table->fields['academic_period']['options']);
				if ($this->_table->request->data($this->associatedModel->aliasField('academic_period'))) {
					$this->academicPeriodId = $this->_table->request->data($this->associatedModel->aliasField('academic_period'));
				}
			}
		}
		$attr['type'] = 'select';
		$attr['onChangeReload'] = 'true';
		if (isset($this->academicPeriodId)) {
			$InstitutionSiteProgrammes = TableRegistry::get('Institution.InstitutionSiteProgrammes');
			$attr['options'] = $InstitutionSiteProgrammes->getSiteProgrammeOptions($institutionsId, $this->academicPeriodId);
		}

		return $attr;
	}

	public function onUpdateFieldEducationGrade(Event $event, array $attr, $action, $request) {
		$session = $this->_table->request->session();
		$institutionsId = $session->read('Institutions.id');

		if (array_key_exists('education_programme_id', $this->_table->fields)) {
			if (array_key_exists('options', $this->_table->fields['education_programme_id'])) {
				$this->educationProgrammeId = key($this->_table->fields['education_programme_id']['options']);
				if ($this->_table->request->data($this->associatedModel->aliasField('education_programme_id'))) {
					$this->educationProgrammeId = $this->_table->request->data($this->associatedModel->aliasField('education_programme_id'));
				}
			}
		}
		$attr['type'] = 'select';
		$attr['onChangeReload'] = 'true';

		if (isset($this->educationProgrammeId)) {
			$InstitutionSiteGrades = TableRegistry::get('Institution.InstitutionSiteGrades');
			$attr['options'] = $InstitutionSiteGrades->getGradeOptions($institutionsId, $this->academicPeriodId, $this->educationProgrammeId);
		}

		return $attr;
	}

	public function onUpdateFieldSection(Event $event, array $attr, $action, $request) {
		$session = $this->_table->request->session();
		$institutionsId = $session->read('Institutions.id');

		if (array_key_exists('education_grade', $this->_table->fields)) {
			if (array_key_exists('options', $this->_table->fields['education_grade'])) {
				$this->education_grade = key($this->_table->fields['education_grade']['options']);
				if ($this->_table->request->data($this->associatedModel->aliasField('education_grade'))) {
					$this->education_grade = $this->_table->request->data($this->associatedModel->aliasField('education_grade'));
				}
			}
		}
		$attr['type'] = 'select';

		if (isset($this->education_grade)) {
			$InstitutionSiteSections = TableRegistry::get('Institution.InstitutionSiteSections');
			$attr['options'] = $InstitutionSiteSections->getSectionOptions($this->academicPeriodId, $institutionsId, $this->education_grade);
		}

		return $attr;
	}

	public function onUpdateFieldStudentStatusId(Event $event, array $attr, $action, $request) {
		$attr['type'] = 'select';
		$attr['options'] = $this->associatedModel->StudentStatuses->getList();

		return $attr;
	}



	// need to intercept the add


}
