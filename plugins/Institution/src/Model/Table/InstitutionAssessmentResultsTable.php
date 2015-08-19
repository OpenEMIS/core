<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use App\Model\Traits\MessagesTrait;

class InstitutionAssessmentResultsTable extends AppTable {
	use OptionsTrait;
	use MessagesTrait;

	private $gradingOptions = [];

	public function initialize(array $config) {
		$this->table('institution_site_class_students');
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
		$this->belongsTo('InstitutionSiteClasses', ['className' => 'Institution.InstitutionSiteClasses']);
		$this->belongsTo('InstitutionSiteSections', ['className' => 'Institution.InstitutionSiteSections']);
	}

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
    	return $events;
    }

	public function onGetIdentity(Event $event, Entity $entity) {
		return $entity->user->openemis_no;
	}

	public function onGetMark(Event $event, Entity $entity) {
		$html = '';

		$Classes = TableRegistry::get('Institution.InstitutionSiteClasses');
		$Items = TableRegistry::get('Assessment.AssessmentItems');
		$Results = TableRegistry::get('Assessment.AssessmentItemResults');

		$institutionId = $this->Session->read('Institutions.id');
		$selectedStatus = $this->request->query('status');
		$selectedAssessment = $this->request->query('assessment');
		$selectedPeriod = $this->request->query('period');
		$selectedClass = $this->request->query('class');
		$subjectId = $Classes->get($selectedClass)->education_subject_id;
		$id = $entity->student_id;

		$itemObj = $Items
			->find()
			->where([
				$Items->aliasField('assessment_id') => $selectedAssessment,
				$Items->aliasField('education_subject_id') => $subjectId
			])
			->first();

		if (!is_null($itemObj)) {
			$resultObj = $Results
				->find()
				->where([
					$Results->aliasField('assessment_item_id') => $itemObj->id,
					$Results->aliasField('security_user_id') => $id,
					$Results->aliasField('institution_site_id') => $institutionId,
					$Results->aliasField('academic_period_id') => $selectedPeriod
				])
				->first();

			$marks = 0;
			$gradingId = 0;
			if (!is_null($resultObj)) {
				$marks = $resultObj->marks;
				$gradingId = $resultObj->assessment_grading_option_id;
			}
			$entity->assessment_grading_option_id = $gradingId;

			if ($selectedStatus == 0 || $selectedStatus == 1) {
				$Form = $event->subject()->Form;
				$alias = Inflector::underscore($Results->alias());
				$fieldPrefix = $Items->alias() . '.'.$alias.'.' . $id;

				if (!is_null($resultObj)) {
					$html .= $Form->hidden($fieldPrefix.".id", ['value' => $resultObj->id]);
				}
				$options = ['type' => 'number', 'label' => false, 'value' => $marks];
				$html .= $Form->input($fieldPrefix.".marks", $options);
				$html .= $Form->hidden($Items->alias().".id", ['value' => $itemObj->id]);
				$html .= $Form->hidden($fieldPrefix.".security_user_id", ['value' => $id]);
				$html .= $Form->hidden($fieldPrefix.".institution_site_id", ['value' => $institutionId]);
				$html .= $Form->hidden($fieldPrefix.".academic_period_id", ['value' => $selectedPeriod]);
			} else {
				$html = $marks;
			}
		}

		return $html;
	}

	public function onGetGrade(Event $event, Entity $entity) {
		$html = '';

		$selectedStatus = $this->request->query('status');
		if ($selectedStatus == 0 || $selectedStatus == 1) {
			$Form = $event->subject()->Form;
			$Items = TableRegistry::get('Assessment.AssessmentItems');
			$Results = TableRegistry::get('Assessment.AssessmentItemResults');
			$alias = Inflector::underscore($Results->alias());
			$fieldPrefix = $Items->alias() . '.'.$alias.'.' . $entity->student_id;

			$gradingOptions = $this->gradingOptions;
			$selectedGrading = key($gradingOptions);
			if (isset($entity->assessment_grading_option_id) && $entity->assessment_grading_option_id != 0) {
				$selectedGrading = $entity->assessment_grading_option_id;
			}
			$this->advancedSelectOptions($gradingOptions, $selectedGrading);

			$options = ['type' => 'select', 'label' => false, 'options' => $gradingOptions];
			$html .= $Form->input($fieldPrefix.".assessment_grading_option_id", $options);
		} else {
			if (isset($entity->assessment_grading_option_id) && $entity->assessment_grading_option_id != 0) {
				$html = $this->gradingOptions[$entity->assessment_grading_option_id];
			} else {
				$html = '<i class="fa fa-minus"></i>';
			}
		}

		return $html;
	}

	public function indexBeforeAction(Event $event, Query $query, ArrayObject $settings) {
		$this->ControllerAction->field('status', ['visible' => false]);
		$this->ControllerAction->field('institution_site_class_id', ['visible' => false]);
		$this->ControllerAction->field('institution_site_section_id', ['visible' => false]);
		$this->ControllerAction->field('identity');
		$this->ControllerAction->field('mark');
		$this->ControllerAction->field('grade');
		$this->ControllerAction->setFieldOrder(['identity', 'student_id', 'mark', 'grade']);

		$institutionId = $this->Session->read('Institutions.id');
		$selectedStatus = $this->request->query('status');
		$selectedAssessment = $this->request->query('assessment');
		$selectedPeriod = $this->request->query('period');

		$AssessmentItems = TableRegistry::get('Assessment.AssessmentItems');
		$subjectIds = $AssessmentItems
			->find('list', ['keyField' => 'education_subject_id', 'valueField' => 'education_subject_id'])
			->find('visible')
			->where([$AssessmentItems->aliasField('assessment_id') => $selectedAssessment])
			->toArray();

		$settings['pagination'] = false;
		$querySkip = null;
		if (!empty($subjectIds)) {
			$Sections = TableRegistry::get('Institution.InstitutionSiteSections');
			$Classes = TableRegistry::get('Institution.InstitutionSiteClasses');
			$SectionClasses = TableRegistry::get('Institution.InstitutionSiteSectionClasses');

			$sectionResults = $Sections
				->find()
				->select([
					$Sections->aliasField('id'),
					$Sections->aliasField('name')
				])
				->where([
					$Sections->aliasField('institution_site_id') => $institutionId,
					$Sections->aliasField('academic_period_id') => $selectedPeriod,
				])
				->join([
					'table' => $SectionClasses->_table,
					'alias' => $SectionClasses->alias(),
					'conditions' => [
						$SectionClasses->aliasField('institution_site_section_id =') . $Sections->aliasField('id')
					]
				])
				->join([
					'table' => $Classes->_table,
					'alias' => $Classes->alias(),
					'conditions' => [
						$Classes->aliasField('id =') . $SectionClasses->aliasField('institution_site_class_id'),
						$Classes->aliasField('institution_site_id') => $institutionId,
						$Classes->aliasField('academic_period_id') => $selectedPeriod,
						$Classes->aliasField('education_subject_id IN') => $subjectIds
					]
				])
				->group([
					$Sections->aliasField('id')
				])
				->contain([
					'InstitutionSiteClasses.EducationSubjects',
				    'InstitutionSiteClasses' => function ($q) use ($Classes, $institutionId, $selectedPeriod, $subjectIds) {
				       return $q
				       		->where([
				       			$Classes->aliasField('institution_site_id') => $institutionId,
								$Classes->aliasField('academic_period_id') => $selectedPeriod,
								$Classes->aliasField('education_subject_id IN') => $subjectIds
				       		]);
				    }
				])
				->all();

			if (!$sectionResults->isEmpty()) {
				// tabElements variables
				$plugin = $this->controller->plugin;
				$controller = $this->controller->name;
				$action = $this->alias;
				$action .= '?status=' . $selectedStatus;
				$action .= '&assessment=' . $selectedAssessment;
				$action .= '&period=' . $selectedPeriod;
				// End
				
				$tabElements = [];
				$sectionOptions = [];
				$classOptions = [];
				foreach ($sectionResults as $section) {
					$sectionId = $section->id;
					$sectionName = $section->name;

					$classes = [];
					foreach ($section->institution_site_classes as $class) {
						$classId = $class->id;
						$className = $class->name;
						$subjectId = $class->education_subject->id;
						$subjectName = $class->education_subject->name;
						
						// Class Options
						$classes[$classId] = [
							'value' => $classId,
							'text' => $className
						];
					}

					$tabElements[$sectionName] = [
						'url' => ['plugin' => $plugin, 'controller' => $controller, 'action' => $action.'&section='.$sectionId],
						'text' => __($sectionName)
					];

					// Section Options
					$sectionOptions[$sectionId] = [
						'value' => $sectionId,
						'text' => $sectionName,
						'classes' => $classes
					];
				}
				$selectedSection = $this->queryString('section', $sectionOptions);

				$this->controller->set('tabElements', $tabElements);
				$this->controller->set('selectedAction', $sectionOptions[$selectedSection]['text']);


				if (!empty($sectionOptions[$selectedSection]['classes'])) {
					// Class Options
					$classOptions = $sectionOptions[$selectedSection]['classes'];
					$selectedClass = $this->queryString('class', $classOptions);
					$classOptions[$selectedClass][0] = 'selected';
					// End

					// toolbarElements
					$toolbarElements = [
						['name' => 'Institution.Assessment/controls', 'data' => [], 'options' => []]
					];
					$this->controller->set('toolbarElements', $toolbarElements);
					$this->controller->set('classOptions', $classOptions);
					// End

					// Grading Options
					$subjectId = $Classes->get($selectedClass)->education_subject_id;

					$Items = TableRegistry::get('Assessment.AssessmentItems');
					$itemObj = $Items
						->find()
						->where([
							$Items->aliasField('assessment_id') => $selectedAssessment,
							$Items->aliasField('education_subject_id') => $subjectId
						])
						->contain(['GradingTypes.GradingOptions'])
						->first();

					if (!is_null($itemObj)) {
						foreach ($itemObj->grading_type->grading_options as $key => $obj) {
							$this->gradingOptions[$obj->id] = $obj->code ." - ". $obj->name;
						}
					}
					// End

					return $query
						->where([$this->aliasField('institution_site_class_id') => $selectedClass])
						->contain(['Users']);
				}
			} else {
				$querySkip = 'InstitutionAssessmentResults.noClasses';
			}
		} else {
			$querySkip = 'InstitutionAssessmentResults.noSubjects';
		}

		if (!is_null($querySkip)) {
			$this->Alert->warning($querySkip);
		}
		return $query
				->where([$this->aliasField('student_id') => 0]);
	}

	public function afterAction(Event $event, ArrayObject $config) {
		$selectedStatus = $this->request->query('status');
		if ($selectedStatus == 0 || $selectedStatus == 1) {
			$config['formButtons'] = true;
			$config['url'] = $config['buttons']['index']['url'];
			$config['url'][0] = 'indexEdit';
		}
	}

    public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		$toolbarButtons['back'] = $buttons['back'];
		$toolbarButtons['back']['url'] = [
    		'plugin' => $buttons['back']['url']['plugin'],
    		'controller' => $buttons['back']['url']['controller'],
    		'action' => 'Assessments',
    		'index',
    		'status' => $this->request->query('status')
    	];
		$toolbarButtons['back']['type'] = 'button';
		$toolbarButtons['back']['label'] = '<i class="fa kd-back"></i>';
		$toolbarButtons['back']['attr'] = $attr;
		$toolbarButtons['back']['attr']['title'] = __('Back');
	}

	public function indexEdit() {
		$controller = $this->controller;

		if ($this->request->is(['post', 'put'])) {
			$institutionId = $this->Session->read('Institutions.id');
			$selectedStatus = $this->request->query('status');
			$selectedAssessment = $this->request->query('assessment');
			$selectedPeriod = $this->request->query('period');

			$Items = TableRegistry::get('Assessment.AssessmentItems');
			$InstitutionAssessments = TableRegistry::get('Institution.InstitutionAssessments');

			$entity = $Items->newEntity();
			$entity = $Items->patchEntity($entity, $this->request->data);

			if ($Items->save($entity)) {
				if ($selectedStatus == 0) {
					$this->Alert->success('general.add.success');
				} else if ($selectedStatus == 1) {
					$this->Alert->success('general.edit.success');
				}

				$this->request->query['status'] = ++$selectedStatus;
				$InstitutionAssessments->updateAll(
					['status' => $selectedStatus], [
						'institution_site_id' => $institutionId,
						'assessment_id' => $selectedAssessment,
						'academic_period_id' => $selectedPeriod
					]
				);
			} else {
				$Items->log($entity->errors(), 'debug');
				if ($selectedStatus == 0) {
					$this->Alert->success('general.add.failed');
				} else if ($selectedStatus == 1) {
					$this->Alert->success('general.edit.failed');
				}
			}

			$url = ['plugin' => $controller->plugin, 'controller' => $controller->name, 'action' => $this->alias];
			$url = array_merge($url, $this->request->query, $this->request->pass);
			$url[0] = 'index';

			return $this->controller->redirect($url);
		}
	}
}
