<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class StudentResultsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_class_students');
		$config['Modified'] = false;
		$config['Created'] = false;
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('Sections', ['className' => 'Institution.InstitutionSections', 'foreignKey' => 'institution_section_id']);
		$this->belongsTo('Classes', ['className' => 'Institution.InstitutionClasses', 'foreignKey' => 'institution_class_id']);
	}

	// Event: ControllerAction.Model.onGetOpenemisNo
	public function onGetOpenemisNo(Event $event, Entity $entity) {
		return $entity->user->openemis_no;
	}

	public function onGetMarks(Event $event, Entity $entity) {
		return rand(50, 100);
	}

	public function onGetGrade(Event $event, Entity $entity) {
		return 'Pass';
	}

	public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true) {
		if ($field == 'security_user_id') {
			return 'Student';
		} else {
			return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
		}
	}

	// Event: ControllerAction.Model.index.beforeAction
	public function indexBeforeAction(Event $event) {
		$toolbarElements = [
			['name' => 'Institution.StudentResults/controls', 'data' => [], 'options' => []]
		];
		$this->controller->set('toolbarElements', $toolbarElements);

		$this->ControllerAction->field('openemis_no');
		$this->ControllerAction->field('status', ['visible' => false]);
		$this->ControllerAction->field('institution_class_id', ['visible' => false]);
		$this->ControllerAction->field('institution_section_id', ['visible' => false]);
		$this->ControllerAction->field('marks');
		$this->ControllerAction->field('grade');

		$this->ControllerAction->setFieldOrder([
			'openemis_no', 'security_user_id', 
			'institution_section_id', 'institution_class_id', 'marks'
		]);

		// Setup period options
		$AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		$periodOptions = $AcademicPeriod->getList();
		
		$Sections = $this->Sections;
		$institutionId = $this->Session->read('Institution.Institutions.id');
		$selectedPeriod = $this->queryString('academic_period_id', $periodOptions);

		$this->advancedSelectOptions($periodOptions, $selectedPeriod, [
			'message' => '{{label}} - ' . $this->getMessage('general.noSections'),
			'callable' => function($id) use ($Sections, $institutionId) {
				return $Sections->findByInstitutionIdAndAcademicPeriodId($institutionId, $id)->count();
			}
		]);
		$this->controller->set(compact('periodOptions'));
		// End setup periods

		// Setup section options
		$sectionList = $Sections
			->find('all')
			->contain(['InstitutionClasses'])
			->where([
			$Sections->aliasField('institution_id') => $institutionId,
				$Sections->aliasField('academic_period_id') => $selectedPeriod
			])->all();
		
		$sectionOptions = [];
		$classOptions = [];

		// build options for sections and classes
		foreach ($sectionList as $section) {
			$sectionOptions[$section->id] = ['value' => $section->id, 'text' => $section->name];
			if ($section->has('institution_classes')) {
				if (empty($section->institution_classes)) {
					$sectionOptions[$section->id]['text'] .= ' - ' . $this->getMessage('general.noClasses');
				} else {
					$classOptions[$section->id] = [];
					foreach ($section->institution_classes as $class) {
						$classOptions[$section->id][$class->id] = ['value' => $class->id, 'text' => $class->name];
					}
				}
			}
		}

		$selectedSection = $this->queryString('section_id', $sectionOptions);
		$sectionOptions[$selectedSection][] = 'selected';
		$classOptions = $classOptions[$selectedSection];
		$selectedClass = $this->queryString('class_id', $classOptions);
		$classOptions[$selectedClass][] = 'selected';
		
		$this->controller->set(compact('sectionOptions', 'classOptions'));
		// End setup sections

		// Setup class options
		// $sectionOptions = $this->Classes
		// 	->find('list')
		// 	->where([
		// 		$Sections->aliasField('institution_id') => $institutionId, 
		// 		$Sections->aliasField('academic_period_id') => $selectedPeriod
		// 	])
		// 	->toArray();

		// $selectedSection = $this->queryString('section_id', $sectionOptions);
		// $this->advancedSelectOptions($sectionOptions, $selectedSection);
		// $this->controller->set(compact('sectionOptions'));
		// End setup sections
	}

	// Event: ControllerAction.Model.index.beforePaginate
	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$sectionId = $request->query('section_id');
		$classId = $request->query('class_id');
		
		$query
		->find('withResults')
		->where([$this->aliasField('status') => 1])
		->andWhere([$this->aliasField('institution_section_id') => $sectionId])
		->andWhere([$this->aliasField('institution_class_id') => $classId]);
	}

	public function findWithResults(Query $query, array $options) {
		$query
			->select([
				$this->aliasField('security_user_id'),
				$this->aliasField('institution_section_id'),
				$this->aliasField('institution_class_id'),
				'Users.openemis_no', 'Users.first_name', 'Users.last_name',
				'Sections.name', 'Classes.name', 'Results.marks'
			])
			->join([
				[
					'table' => 'institution_classes', 'alias' => 'Classes', 'type' => 'INNER',
					'conditions' => ['Classes.id = ' . $this->aliasField('institution_class_id')]
				],
				[
					'table' => 'assessment_items', 'alias' => 'AssessmentItems', 'type' => 'INNER',
					'conditions' => ['AssessmentItems.education_subject_id = Classes.education_subject_id']
				],
				[
					'table' => 'assessment_item_results', 'alias' => 'Results', 'type' => 'LEFT',
					'conditions' => [
						'Results.security_user_id = ' . $this->aliasField('security_user_id'),
						'Results.institution_id = Classes.institution_id',
						'Results.assessment_item_id = AssessmentItems.id'
					]
				]
			])
			->group(['Users.id'])
			;
    		
		return $query;
	}
}
