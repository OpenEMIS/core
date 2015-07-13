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

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
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

		$institutionId = $this->Session->read('Institutions.id');
		$selectedStatus = $this->request->query('status');
		$selectedAssessment = $this->request->query('assessment');
		$selectedPeriod = $this->request->query('period');
		$selectedSubject = $this->request->query('subject');
		$id = $entity->id;

		$Items = TableRegistry::get('Assessment.AssessmentItems');
		$Results = TableRegistry::get('Assessment.AssessmentItemResults');

		$itemObj = $Items
			->find()
			->where([
				$Items->aliasField('assessment_id') => $selectedAssessment,
				$Items->aliasField('education_subject_id') => $selectedSubject
			])
			->first();

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
			$fieldPrefix = $Items->alias() . '.'.$alias.'.' . $entity->id;

			$gradingOptions = $this->gradingOptions;
			$this->advancedSelectOptions($gradingOptions, $entity->assessment_grading_option_id);

			$options = ['type' => 'select', 'label' => false, 'options' => $gradingOptions];
			$html .= $Form->input($fieldPrefix.".assessment_grading_option_id", $options);
		} else {
			$html = $this->gradingOptions[$entity->assessment_grading_option_id];
		}

		return $html;
	}

	public function beforeAction(Event $event) {
		$institutionId = $this->Session->read('Institutions.id');
		$selectedStatus = $this->request->query('status');
		$selectedAssessment = $this->request->query('assessment');
		$selectedPeriod = $this->request->query('period');

		$AssessmentItems = TableRegistry::get('Assessment.AssessmentItems');
		$subjectIds = $AssessmentItems
			->findByAssessmentId($selectedAssessment)
			->find('list', ['keyField' => 'education_subject_id', 'valueField' => 'education_subject_id'])
			->toArray();

		if (!empty($subjectIds)) {
			$classes = $this->InstitutionSiteClasses
				->findByInstitutionSiteIdAndAcademicPeriodId($institutionId, $selectedPeriod)
				->where([
					$this->InstitutionSiteClasses->aliasField('education_subject_id IN') => $subjectIds
				])
				->all();

			if (!$classes->isEmpty()) {
				$plugin = $this->controller->plugin;
				$controller = $this->controller->name;
				$action = $this->alias;
				$action .= '?status=' . $selectedStatus;
				$action .= '&assessment=' . $selectedAssessment;
				$action .= '&period=' . $selectedPeriod;

				$tabElements = [];
				$classOptions = [];
				$subjectOptions = [];
				foreach ($classes as $key => $class) {
					$classId = $class->id;
					$className = $class->name;
					$subjectId = $class->education_subject_id;
					$classOptions[$classId] = $className;
					$subjectOptions[$subjectId] = $subjectId;

					$tabElements[$className] = [
						'url' => ['plugin' => $plugin, 'controller' => $controller, 'action' => $action.'&class='.$classId.'&subject='.$subjectId],
						'text' => __($className)
					];
				}
				$selectedAction = $this->queryString('class', $classOptions);
				$selectedSubject = $this->queryString('subject', $subjectOptions);

				$this->controller->set('tabElements', $tabElements);
				$this->controller->set('selectedAction', $classOptions[$selectedAction]);
			} else {
				$this->Alert->warning('InstitutionAssessmentResults.noClasses');
			}
		} else {
			$this->Alert->warning('InstitutionAssessmentResults.noSubjects');
		}
	}

	public function indexBeforeAction(Event $event, Query $query, ArrayObject $settings) {
		$this->ControllerAction->field('status', ['visible' => false]);
		$this->ControllerAction->field('institution_site_class_id', ['visible' => false]);
		$this->ControllerAction->field('institution_site_section_id', ['visible' => false]);
		$this->ControllerAction->field('identity');
		$this->ControllerAction->field('mark');
		$this->ControllerAction->field('grade');
		$this->ControllerAction->setFieldOrder(['identity', 'security_user_id', 'mark', 'grade']);

		$settings['pagination'] = false;
		$selectedClass = !is_null($this->request->query('class')) ? $this->request->query('class') : -1;
		$selectedAssessment = $this->request->query('assessment');
		$selectedSubject = $this->request->query('subject');

		$Items = TableRegistry::get('Assessment.AssessmentItems');
		$itemObj = $Items
			->find()
			->where([
				$Items->aliasField('assessment_id') => $selectedAssessment,
				$Items->aliasField('education_subject_id') => $selectedSubject
			])
			->contain(['GradingTypes.GradingOptions'])
			->first();

		$gradings = $itemObj->grading_type->grading_options;
		foreach ($gradings as $key => $obj) {
			$this->gradingOptions[$obj->id] = $obj->code ." - ". $obj->name;
		}

		return $query
			->where([$this->aliasField('institution_site_class_id') => $selectedClass])
			->contain(['Users']);
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
