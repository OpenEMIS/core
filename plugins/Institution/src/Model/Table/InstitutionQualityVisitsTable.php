<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\Event;

class InstitutionQualityVisitsTable extends AppTable {
	private $_fieldOrder = ['date', 'academic_period_level', 'academic_period_id', 'institution_section_id', 'education_grade_id', 'institution_class_id', 'security_user_id', 'quality_visit_type_id'];

	public function initialize(array $config) {
		$this->table('institution_quality_visits');
		parent::initialize($config);

		$this->belongsTo('QualityVisitTypes', ['className' => 'FieldOption.QualityVisitTypes']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
		$this->belongsTo('Sections', ['className' => 'Institution.InstitutionSections', 'foreignKey' => 'institution_section_id']);
		$this->belongsTo('Classes', ['className' => 'Institution.InstitutionClasses', 'foreignKey' => 'institution_class_id']);
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);

		$this->addBehavior('AcademicPeriod.Period');
		$this->addBehavior('AcademicPeriod.AcademicPeriod');
	}

	public function onGetInstitutionClassId(Event $event, Entity $entity) {
		if ($this->action == 'index') {
			return $entity->section->name . '<span class="divider"></span>' .  $entity->class->name;
		}
	}

	public function afterAction(Event $event) {
		$this->ControllerAction->setFieldOrder($this->_fieldOrder);
	}

	// PHPOE-1916
	// Bug fix on view page
	public function viewAfterAction(Event $event, Entity $entity) {
		unset($this->_fieldOrder[1]); // Remove academic period level on view page
		$this->ControllerAction->setFieldOrder($this->_fieldOrder);
	}

	public function indexBeforeAction(Event $event) {
		$this->ControllerAction->field('comment', ['visible' => false]);
		$this->ControllerAction->field('academic_period_id', ['visible' => false]);
		$this->ControllerAction->field('institution_section_id', ['visible' => false]);

		$this->_fieldOrder = [
			'date', 'education_grade_id', 'institution_class_id', 'security_user_id', 'quality_visit_type_id'
		];
	}

	public function addEditAfterAction(Event $event, Entity $entity) {
		$institutionId = $this->Session->read('Institution.Institutions.id');
		list($levelOptions, $selectedLevel, $periodOptions, $selectedPeriod, $sectionOptions, $selectedSection, $gradeOptions, $selectedGrade, $classOptions, $selectedClass, $staffOptions, $selectedStaff) = array_values($this->_getSelectOptions());

		// Academic Period Level Options
		$AcademicPeriods = $this->AcademicPeriods;
		$this->advancedSelectOptions($levelOptions, $selectedLevel, [
			'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noPeriods')),
			'callable' => function($id) use ($AcademicPeriods) {
				return $AcademicPeriods
					->find()
					->find('visible')
					->where([$AcademicPeriods->aliasField('academic_period_level_id') => $id])
					->count();
			}
		]);
		$this->ControllerAction->field('academic_period_level', [
			'options' => $levelOptions,
			'onChangeReload' => true
		]);
		// End

		// Academic Period Options
		$Sections = $this->Sections;
		$this->advancedSelectOptions($periodOptions, $selectedPeriod, [
			'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noSections')),
			'callable' => function($id) use ($Sections, $institutionId) {
				return $Sections
					->find()
					->where([
						$Sections->aliasField('institution_id') => $institutionId,
						$Sections->aliasField('academic_period_id') => $id
					])
					->count();
			}
		]);
		$this->ControllerAction->field('academic_period_id', [
			'options' => $periodOptions,
			'onChangeReload' => true
		]);
		// End

		// Section Options
		$Classes = $this->Classes->InstitutionSectionClasses;
		$this->advancedSelectOptions($sectionOptions, $selectedSection, [
			'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noClasses')),
			'callable' => function($id) use ($Classes) {
				return $Classes
					->find()
					->where([
						$Classes->aliasField('institution_section_id') => $id
					])
					->count();
			}
		]);
		$this->ControllerAction->field('institution_section_id', [
			'options' => $sectionOptions,
			'onChangeReload' => true
		]);
		// End

		// Education Grade Options
		$this->advancedSelectOptions($gradeOptions, $selectedGrade);
		$this->ControllerAction->field('education_grade_id', ['options' => $gradeOptions]);
		// End

		// Classes Options
		$Staff = $this->Classes->InstitutionClassStaff;
		$this->advancedSelectOptions($classOptions, $selectedClass, [
			'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noStaff')),
			'callable' => function($id) use ($Staff) {
				return $Staff
					->find()
					->where([
						$Staff->aliasField('institution_class_id') => $id
					])
					->count();
			}
		]);
		$this->ControllerAction->field('institution_class_id', [
			'options' => $classOptions,
			'onChangeReload' => true
		]);
		// End

		// Staff Options
		$this->ControllerAction->field('security_user_id', ['options' => $staffOptions]);
		// End

		// Visit Type Options
		$this->ControllerAction->field('quality_visit_type_id', ['type' => 'select']);
		// End
	}

	public function editOnInitialize(Event $event, Entity $entity) {
		$request = $this->controller->request;
		$request->query['period'] = $entity->academic_period_id;
		$request->query['section'] = $entity->institution_section_id;
		$request->query['grade'] = $entity->education_grade_id;
		$request->query['class'] = $entity->institution_class_id;
		$request->query['staff'] = $entity->security_user_id;
	}

	public function _getSelectOptions() {
		$request = $this->controller->request;
		$institutionId = $this->Session->read('Institution.Institutions.id');

		//Return all required options and their key
		$levelOptions = $this->AcademicPeriods->Levels
			->getList()
			->toArray();
		$selectedLevel = $this->queryString('level', $levelOptions);
		if ($request->is(['post', 'put'])) {
			$selectedLevel = $request->data($this->aliasField('academic_period_level'));
		}

		$periodOptions = $this->AcademicPeriods
			->find('list')
			->find('visible')
			->find('order')
			->where([$this->AcademicPeriods->aliasField('academic_period_level_id') => $selectedLevel])
			->toArray();
		$selectedPeriod = $this->queryString('period', $periodOptions);
		if ($request->is(['post', 'put'])) {
			$selectedPeriod = $request->data($this->aliasField('academic_period_id'));
		}

		$sectionOptions = $this->Sections
			->find('list')
			->where([
				$this->Sections->aliasField('institution_id') => $institutionId,
				$this->Sections->aliasField('academic_period_id') => $selectedPeriod
			])
			->toArray();
		$selectedSection = $this->queryString('section', $sectionOptions);
		if ($request->is(['post', 'put'])) {
			$selectedSection = $request->data($this->aliasField('institution_section_id'));
		}

		$InstitutionSectionGrades = TableRegistry::get('Institution.InstitutionSectionGrades');
		$gradeOptions = $this->EducationGrades
			->find('list', ['keyField' => 'id', 'valueField' => 'programme_grade_name'])
			->join([
				'table' => $InstitutionSectionGrades->_table,
				'alias' => $InstitutionSectionGrades->alias(),
				'conditions' => [
					$InstitutionSectionGrades->aliasField('education_grade_id =') . $this->EducationGrades->aliasField('id'),
					$InstitutionSectionGrades->aliasField('institution_section_id') => $selectedSection
				]
			])
			->toArray();
		$selectedGrade = $this->queryString('grade', $gradeOptions);
		if ($request->is(['post', 'put'])) {
			$selectedGrade = $request->data($this->aliasField('education_grade_id'));
		}

		$InstitutionSectionClasses = TableRegistry::get('Institution.InstitutionSectionClasses');
		$classOptions = $this->Classes
			->find('list')
			->join([
				'table' => $InstitutionSectionClasses->_table,
				'alias' => $InstitutionSectionClasses->alias(),
				'conditions' => [
					$InstitutionSectionClasses->aliasField('institution_class_id =') . $this->Classes->aliasField('id'),
					$InstitutionSectionClasses->aliasField('institution_section_id') => $selectedSection
				]
			])
			->toArray();
		$selectedClass = $this->queryString('class', $classOptions);
		if ($request->is(['post', 'put'])) {
			$selectedClass = $request->data($this->aliasField('institution_class_id'));
		}

		$InstitutionClassStaff = TableRegistry::get('Institution.InstitutionClassStaff');
		$staffOptions = $this->Users
			// ->find('list')
			->find('list', ['keyField' => 'id', 'valueField' => 'name_with_id'])
			->join([
				'table' => $InstitutionClassStaff->_table,
				'alias' => $InstitutionClassStaff->alias(),
				'conditions' => [
					$InstitutionClassStaff->aliasField('security_user_id =') . $this->Users->aliasField('id'),
					$InstitutionClassStaff->aliasField('institution_class_id') => $selectedClass
				]
			])
			->toArray();
		$selectedStaff = $this->queryString('staff', $staffOptions);
		if ($request->is(['post', 'put'])) {
			$selectedStaff = $request->data($this->aliasField('security_user_id'));
		}

		return compact('levelOptions', 'selectedLevel', 'periodOptions', 'selectedPeriod', 'sectionOptions', 'selectedSection', 'gradeOptions', 'selectedGrade', 'classOptions', 'selectedClass', 'staffOptions', 'selectedStaff');
	}
}
