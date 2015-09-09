<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\I18n\Time;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class AssessmentStatusesTable extends AppTable {
	public $status = 'new';

	public function initialize(array $config) {
		$config['Modified'] = false;
		$config['Created'] = false;
		parent::initialize($config);

		$this->belongsTo('Assessments', ['className' => 'Assessment.Assessments']);

		$this->belongsToMany('AcademicPeriods', [
			'className' => 'AcademicPeriod.AcademicPeriods',
			'joinTable' => 'assessment_status_periods',
			'foreignKey' => 'assessment_status_id',
			'targetForeignKey' => 'academic_period_id'
		]);
	}

	public function beforeAction(Event $event) {
		$controller = $this->controller;
		$tabElements = [
			'new' => [
				'url' => ['plugin' => $controller->plugin, 'controller' => $controller->name, 'action' => $this->alias, 'status' => 'new'],
				'text' => __('New')
			],
			'completed' => [
				'url' => ['plugin' => $controller->plugin, 'controller' => $controller->name, 'action' => $this->alias, 'status' => 'completed'],
				'text' => __('Completed')
			]
		];

		$request = $this->request;

		if (!empty($request->query('status'))) {
			if (in_array($request->query('status'), ['new', 'completed'])) {
				$this->status = $request->query('status');
			}
		}

		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->status);

		$this->ControllerAction->field('code');
		$this->ControllerAction->field('academic_period');
		$this->ControllerAction->field('date_enabled', ['visible' => false]);

		$this->ControllerAction->setFieldOrder([
			'code', 'assessment_id', 'academic_period', 'date_disabled'
		]);
	}

	// Event: ControllerAction.Model.onGetCode
	public function onGetCode(Event $event, Entity $entity) {
		return $entity->assessment->code;
	}

	// Event: ControllerAction.Model.onGetAcademicPeriod
	public function onGetAcademicPeriod(Event $event, Entity $entity) {
		return $entity->AcademicPeriods['name'];
	}

	// Event: ControllerAction.Model.index.beforeAction
	public function indexBeforeAction(Event $event) {

	}
	
	// Event: ControllerAction.Model.index.beforePaginate
	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$query->find('inStatus');
	}

	public function findInStatus(Query $query, array $options) {
		$today = Time::today();
		$query
			->select([
				$this->aliasField('id'), $this->aliasField('date_enabled'), 
				$this->aliasField('date_disabled'), 'Assessments.id', 
				'Assessments.code', 'Assessments.name', 'AcademicPeriods.id',
				'AcademicPeriods.name'
			])
			->join([
				[
					'table' => 'assessment_status_periods', 'alias' => 'StatusPeriods', 'type' => 'INNER',
					'conditions' => ['StatusPeriods.assessment_status_id = ' . $this->aliasField('id')]
				],
				[
					'table' => 'academic_periods', 'alias' => 'AcademicPeriods', 'type' => 'INNER',
					'conditions' => ['AcademicPeriods.id = StatusPeriods.academic_period_id']
				]
			])
			->where([
				'date_enabled <=' => $today,
				'date_disabled >=' => $today
			])
			->order(['Assessments.order', 'AcademicPeriods.order'])
		;

		return $query;
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		$buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

		if (array_key_exists('view', $buttons)) {
			$buttons['view']['url']['action'] = 'StudentResults';
			$buttons['view']['url'][1] = $entity->assessment->id;
			$buttons['view']['url']['academic_period'] = $entity->AcademicPeriods['id'];
		}
		return $buttons;
	}
}
