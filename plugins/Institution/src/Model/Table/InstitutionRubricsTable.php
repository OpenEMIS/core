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

class InstitutionRubricsTable extends AppTable {
	use OptionsTrait;
	use MessagesTrait;

	public function initialize(array $config) {
		$this->table('institution_site_quality_rubrics');
		parent::initialize($config);

		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('RubricTemplates', ['className' => 'Rubric.RubricTemplates']);
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
		$this->belongsTo('Sections', ['className' => 'Institution.InstitutionSiteSections', 'foreignKey' => 'institution_site_section_id']);
		$this->belongsTo('Classes', ['className' => 'Institution.InstitutionSiteClasses', 'foreignKey' => 'institution_site_class_id']);
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
	}

	public function beforeAction(Event $event) {
		$this->ControllerAction->field('status', ['visible' => ['index' => false, 'view' => true, 'edit' => false]]);
		$this->ControllerAction->field('comment', ['visible' => ['index' => false, 'view' => true, 'edit' => true]]);
		$this->ControllerAction->field('institution_site_section_id', ['visible' => ['index' => false, 'view' => true, 'edit' => true]]);
	}

	public function indexBeforeAction(Event $event) {
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

		$fieldOrder = ['rubric_template_id', 'academic_period_id', 'education_grade_id', 'institution_site_class_id', 'security_user_id'];
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

	public function indexBeforePaginate(Event $event, Request $request, ArrayObject $options) {
		list(, $selectedStatus) = array_values($this->_getSelectOptions());
		$options['conditions'][$this->aliasField('status')] = $selectedStatus;
		$options['order'] = [
			$this->AcademicPeriods->aliasField('order')
		];
	}

	public function _buildRecords($status=0) {
		$institutionId = $this->Session->read('Institutions.id');

		//delete all New Assessment by Institution Id and reinsert
		$this->deleteAll([
			$this->aliasField('institution_site_id') => $institutionId,
			$this->aliasField('status') => $status
		]);

		$rubrics = $this->RubricTemplates
			->find('list')
			->toArray();
		$todayDate = date("Y-m-d");

		$RubricStatuses = $this->RubricTemplates->RubricStatuses;
		foreach ($rubrics as $key => $rubric) {
			$rubricStatuses = $RubricStatuses
				->find()
				->contain(['AcademicPeriods'])
				->where([
					$RubricStatuses->aliasField('rubric_template_id') => $key,
					$RubricStatuses->aliasField('date_disabled >=') => $todayDate
				])
				->toArray();
			
			foreach ($rubricStatuses as $rubricStatus) {
				foreach ($rubricStatus->academic_periods as $academic_period) {
					$academicPeriodId = $academic_period->id;
					$templateId = $rubricStatus->rubric_template_id;
					// pr(' Template: ' . $templateId . ' Period: ' . $academicPeriodId);
				}
			}
		}
	}

	public function _getSelectOptions() {
		//Return all required options and their key
		$statusOptions = $this->getSelectOptions('Rubrics.status');
		$selectedStatus = $this->queryString('status', $statusOptions);

		return compact('statusOptions', 'selectedStatus');
	}
}
