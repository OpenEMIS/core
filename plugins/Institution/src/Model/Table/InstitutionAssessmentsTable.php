<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use App\Model\Traits\MessagesTrait;

class InstitutionAssessmentsTable extends AppTable {
	use OptionsTrait;
	use MessagesTrait;

	public function initialize(array $config) {
		$this->table('institution_site_assessments');
		parent::initialize($config);
		
		$this->belongsTo('Assessments', ['className' => 'Assessment.Assessments']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->addBehavior('AcademicPeriod.AcademicPeriod');
	}

	public function afterDelete(Event $event, Entity $entity, ArrayObject $options) {
		// To clear all records in assessment_item_results when delete from draft
		$AssessmentItems = $this->Assessments->AssessmentItems;
		$itemIds = $AssessmentItems
			->find('list', ['keyField' => 'id', 'valueField' => 'id'])
			->where([$AssessmentItems->aliasField('assessment_id') => $entity->assessment_id])
			->toArray();

		$Results = TableRegistry::get('Assessment.AssessmentItemResults');
		$Results->deleteAll([
			$Results->aliasField('institution_site_id') => $entity->institution_site_id,
			$Results->aliasField('academic_period_id') => $entity->academic_period_id,
			$Results->aliasField('assessment_item_id IN') => $itemIds
		]);
	}

	public function onGetStatus(Event $event, Entity $entity) {
		list($statusOptions) = array_values($this->_getSelectOptions());
		return $statusOptions[$entity->status];
	}

	public function onGetAssessmentId(Event $event, Entity $entity) {
		return $event->subject()->Html->link($entity->assessment->code_name, [
			'plugin' => $this->controller->plugin,
			'controller' => $this->controller->name,
			'action' => 'Results',
			'index',
			'status' => $entity->status,
			'assessment' => $entity->assessment_id,
			'period' => $entity->academic_period_id
		]);
	}

	public function onGetLastModified(Event $event, Entity $entity) {
		return $this->formatDate($entity->modified);
	}

	public function onGetToBeCompletedBy(Event $event, Entity $entity) {
		$value = '<i class="fa fa-minus"></i>';

		$AssessmentStatuses = $this->Assessments->AssessmentStatuses;
		$AssessmentStatusPeriods = TableRegistry::get('Assessment.AssessmentStatusPeriods');

		$results = $AssessmentStatuses
			->find()
			->select([
				$AssessmentStatuses->aliasField('date_disabled')
			])
			->where([$AssessmentStatuses->aliasField('assessment_id') => $entity->assessment->id])
			->join([
				'table' => $AssessmentStatusPeriods->_table,
				'alias' => $AssessmentStatusPeriods->alias(),
				'conditions' => [
					$AssessmentStatusPeriods->aliasField('assessment_status_id =') . $AssessmentStatuses->aliasField('id'),
					$AssessmentStatusPeriods->aliasField('academic_period_id') => $entity->academic_period_id
				]
			])
			->all();

		if (!$results->isEmpty()) {
			$dateDisabled = $results->first()->date_disabled;
			$value = $this->formatDate($dateDisabled);
		}

		return $value;
	}

	public function onGetCompletedOn(Event $event, Entity $entity) {
		return $entity->modified;
	}

	public function beforeAction(Event $event) {
		$this->ControllerAction->field('status', ['visible' => ['index' => false, 'view' => true, 'edit' => false]]);
	}

	public function indexBeforeAction(Event $event, Query $query, ArrayObject $settings) {
		list($statusOptions, $selectedStatus) = array_values($this->_getSelectOptions());

		$plugin = $this->controller->plugin;
		$controller = $this->controller->name;
		$action = $this->alias;

		$tabElements = [];
		foreach ($statusOptions as $key => $status) {
			$tabElements[$status] = [
				'url' => ['plugin' => $plugin, 'controller' => $controller, 'action' => $action.'?status='.$key],
				'text' => $status
			];
		}

		$this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $statusOptions[$selectedStatus]);

        $fieldOrder = ['assessment_id', 'academic_period_id'];
        if ($selectedStatus == 0) {	//New
			$this->ControllerAction->field('to_be_completed_by');
			$fieldOrder[] = 'to_be_completed_by';
			$this->_buildRecords();
        } else if ($selectedStatus == 1) {	//Draft
			$this->ControllerAction->field('last_modified');
			$this->ControllerAction->field('to_be_completed_by');
			$fieldOrder[] = 'last_modified';
			$fieldOrder[] = 'to_be_completed_by';
        } else if ($selectedStatus == 2) {	//Completed
			$this->ControllerAction->field('completed_on');
			$fieldOrder[] = 'completed_on';
        }

        $this->ControllerAction->setFieldOrder($fieldOrder);
    }

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		list(, $selectedStatus) = array_values($this->_getSelectOptions());

		$options['auto_contain'] = false;
		$query
			->contain(['Assessments', 'AcademicPeriods'])
			->where([$this->aliasField('status') => $selectedStatus])
			->order([$this->AcademicPeriods->aliasField('order')]);
	}

	public function onBeforeDelete(Event $event, ArrayObject $options, $id) {
		$assessmentRecord = $this->get($id);

		if ($assessmentRecord->status == 2) {
			$entity = $this->newEntity(['id' => $id, 'status' => 1], ['validate' => false]);
			if ($this->save($entity)) {
				$this->Alert->success('InstitutionAssessments.reject.success');
			} else {
				$this->Alert->success('InstitutionAssessments.reject.failed');
				$this->log($entity->errors(), 'debug');
			}

			$event->stopPropagation();
			$action = $this->ControllerAction->url('index'); //$this->ControllerAction->buttons['index']['url']
			$action['status'] = 2;
			return $this->controller->redirect($action);
		}
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		list(, $selectedStatus) = array_values($this->_getSelectOptions());
		$buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

		if ($selectedStatus == 0) {	// New
			unset($buttons['view']);
			unset($buttons['remove']);
		} else if ($selectedStatus == 2) {	// Completed
			unset($buttons['edit']);
		}
		return $buttons;
	}

	public function _buildRecords($status=0) {
		$institutionId = $this->Session->read('Institution.Institutions.id');

		//delete all New Assessment by Institution Id and reinsert
		$this->deleteAll([
			$this->aliasField('institution_site_id') => $institutionId,
			$this->aliasField('status') => $status
		]);

		$assessments = $this->Assessments
			->find('list')
			->find('visible')
			->find('order')
			->toArray();
		$todayDate = date("Y-m-d");

		$AssessmentStatuses = $this->Assessments->AssessmentStatuses;
		foreach ($assessments as $key => $assessment) {
			$assessmentStatuses = $AssessmentStatuses
				->find()
				->contain(['AcademicPeriods'])
				->where([
					$AssessmentStatuses->aliasField('assessment_id') => $key,
					$AssessmentStatuses->aliasField('date_disabled >=') => $todayDate
				])
				->toArray();

			foreach ($assessmentStatuses as $assessmentStatus) {
				foreach ($assessmentStatus->academic_periods as $academic_period) {
					$academicPeriodId = $academic_period->id;
					$assessmentId = $assessmentStatus->assessment_id;

					$results = $this
						->findAllByInstitutionSiteIdAndAcademicPeriodIdAndAssessmentId($institutionId, $academicPeriodId, $assessmentId)
						->all();

					if ($results->isEmpty()) {
						$InstitutionAssessment = $this->newEntity();
						$InstitutionAssessment->status = $status;
						$InstitutionAssessment->institution_site_id = $institutionId;
						$InstitutionAssessment->academic_period_id = $academicPeriodId;
						$InstitutionAssessment->assessment_id = $assessmentId;

						if ($this->save($InstitutionAssessment)) {
						} else {
							$this->log($InstitutionAssessment->errors(), 'debug');
						}
					}
				}
			}
		}
	}

	public function _getSelectOptions() {
		//Return all required options and their key
		$statusOptions = $this->getSelectOptions('Assessments.status');
		$selectedStatus = $this->queryString('status', $statusOptions);

		return compact('statusOptions', 'selectedStatus');
	}
}
