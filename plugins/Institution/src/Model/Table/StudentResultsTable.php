<?php
namespace Institution\Model\Table;

use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class StudentResultsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_site_class_students');
		$config['Modified'] = false;
		$config['Created'] = false;
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('Sections', ['className' => 'Institution.InstitutionSiteSections', 'foreignKey' => 'institution_site_section_id']);
		$this->belongsTo('Classes', ['className' => 'Institution.InstitutionSiteClasses', 'foreignKey' => 'institution_site_class_id']);
	}

	// Event: ControllerAction.Model.onGetOpenemisNo
	public function onGetOpenemisNo(Event $event, Entity $entity) {
		return $entity->user->openemis_no;
	}

	public function onGetMarks(Event $event, Entity $entity) {
		return rand(0, 100);
	}

	public function onGetGrade(Event $event, Entity $entity) {
		$grades = ['A', 'B', 'C', 'D', 'E'];
		return $grades[rand(0, 4)];
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
		$this->ControllerAction->field('institution_site_class_id', ['visible' => false]);
		$this->ControllerAction->field('institution_site_section_id', ['visible' => false]);
		$this->ControllerAction->field('marks');
		$this->ControllerAction->field('grade');

		$this->ControllerAction->setFieldOrder([
			'openemis_no', 'security_user_id', 
			'institution_site_section_id', 'institution_site_class_id', 'marks'
		]);

		// Setup period options
		$AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		$periodOptions = $AcademicPeriod->getList();
		
		$Sections = $this->Sections;
		$institutionId = $this->Session->read('Institutions.id');
		$selectedPeriod = $this->queryString('academic_period_id', $periodOptions);

		$this->advancedSelectOptions($periodOptions, $selectedPeriod, [
			'message' => '{{label}} - ' . $this->getMessage('general.noSections'),
			'callable' => function($id) use ($Sections, $institutionId) {
				return $Sections->findByInstitutionSiteIdAndAcademicPeriodId($institutionId, $id)->count();
			}
		]);
		$this->controller->set(compact('periodOptions'));
		// End setup periods

		// Setup section options
		$sectionList = $Sections
			->find('all')
			->contain(['InstitutionSiteClasses'])
			->where([
			$Sections->aliasField('institution_site_id') => $institutionId,
				$Sections->aliasField('academic_period_id') => $selectedPeriod
			])->all();
		
		$sectionOptions = [];
		$classOptions = [];

		// build options for sections and classes
		foreach ($sectionList as $section) {
			$sectionOptions[$section->id] = ['value' => $section->id, 'text' => $section->name];
			if ($section->has('institution_site_classes')) {
				if (empty($section->institution_site_classes)) {
					$sectionOptions[$section->id]['text'] .= ' - ' . $this->getMessage('general.noClasses');
				} else {
					$classOptions[$section->id] = [];
					foreach ($section->institution_site_classes as $class) {
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
		// 		$Sections->aliasField('institution_site_id') => $institutionId, 
		// 		$Sections->aliasField('academic_period_id') => $selectedPeriod
		// 	])
		// 	->toArray();

		// $selectedSection = $this->queryString('section_id', $sectionOptions);
		// $this->advancedSelectOptions($sectionOptions, $selectedSection);
		// $this->controller->set(compact('sectionOptions'));
		// End setup sections
	}

	// Event: ControllerAction.Model.index.beforePaginate
	public function indexBeforePaginate(Event $event, Request $request, array $options) {
		$sectionId = $request->query('section_id');
		$classId = $request->query('class_id');
		
		$options['conditions'][$this->aliasField('status')] = 1;
		$options['conditions'][$this->aliasField('institution_site_section_id')] = $sectionId;
		$options['conditions'][$this->aliasField('institution_site_class_id')] = $classId;
		$options['finder'] = ['withResults' => []];

		return $options;
	}

	public function findWithResults(Query $query, array $options) {
		$query
			->select([
				$this->aliasField('security_user_id'),
				$this->aliasField('institution_site_section_id'),
				$this->aliasField('institution_site_class_id'),
				'Users.openemis_no', 'Users.first_name', 'Users.last_name',
				'Sections.name', 'Classes.name', 'Results.marks'
			])
			->join([
				[
					'table' => 'institution_site_classes', 'alias' => 'Classes', 'type' => 'INNER',
					'conditions' => ['Classes.id = ' . $this->aliasField('institution_site_class_id')]
				],
				[
					'table' => 'assessment_items', 'alias' => 'AssessmentItems', 'type' => 'INNER',
					'conditions' => ['AssessmentItems.education_subject_id = Classes.education_subject_id']
				],
				[
					'table' => 'assessment_item_results', 'alias' => 'Results', 'type' => 'LEFT',
					'conditions' => [
						'Results.security_user_id = ' . $this->aliasField('security_user_id'),
						'Results.institution_site_id = Classes.institution_site_id',
						'Results.assessment_item_id = AssessmentItems.id'
					]
				]
			])
			->group(['Users.id'])
			;
    		
		return $query;
	}
}
