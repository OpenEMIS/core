<?php
namespace Institution\Model\Table;

use DateTime;
use DateInterval;
use Cake\Event\Event;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use App\Model\Traits\OptionsTrait;

class InstitutionSitePositionsTable extends AppTable {
	use OptionsTrait;

	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('StaffPositionTitles', ['className' => 'Institution.StaffPositionTitles']);
		$this->belongsTo('StaffPositionGrades', ['className' => 'Institution.StaffPositionGrades']);
		$this->belongsTo('Institutions', 		['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);

	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}

	public function beforeAction($event) {
		$this->ControllerAction->field('position_no', ['visible' => true]);
		$this->ControllerAction->field('staff_position_title_id', [
			'visible' => true,
			'type' => 'select'
		]);
		$this->ControllerAction->field('staff_position_grade_id', [
			'visible' => true,
			'type' => 'select'
		]);
		$this->ControllerAction->field('type', [
			'visible' => true,
			'type' => 'select',
			'options' => $this->getSelectOptions('Staff.position_types')
		]);
		$this->ControllerAction->field('status', [
			'visible' => true,
			'type' => 'select',
			'options' => $this->getSelectOptions('general.active')
		]);
		$this->ControllerAction->field('current_staff_list', [
			'label' => '',
			'override' => true,
			'type' => 'element', 
			'element' => 'Institution.Positions/current',
			'visible' => true
		]);
		$this->ControllerAction->field('past_staff_list', [
			'label' => '',
			'override' => true,
			'type' => 'element',
			'element' => 'Institution.Positions/past',
			'visible' => true
		]);


		if (strtolower($this->action) != 'index') {
			$this->Navigation->addCrumb($this->getHeader($this->action));
		}
	}


/******************************************************************************************************************
**
** index action methods
**
******************************************************************************************************************/
	public function indexBeforeAction(Event $event) {

		$this->fields['current_staff_list']['visible'] = false;
		$this->fields['past_staff_list']['visible'] = false;

		$this->ControllerAction->setFieldOrder([
			'position_no', 'staff_position_title_id', 
			'staff_position_grade_id', 'type', 'status',
		]);

	}

	public function indexAfterAction(Event $event, $data) {
		return $data;
	}


/******************************************************************************************************************
**
** addEdit action methods
**
******************************************************************************************************************/

	public function addEditBeforeAction($event) {

		$this->fields['current_staff_list']['visible'] = false;
		$this->fields['past_staff_list']['visible'] = false;

		$this->ControllerAction->setFieldOrder([
			'position_no', 'staff_position_title_id', 
			'staff_position_grade_id', 'type', 'status',
		]);

	}

/******************************************************************************************************************
**
** view action methods
**
******************************************************************************************************************/
	public function viewBeforeQuery($event) {
		// pr('viewBeforeQuery');
		// pr($this->id);
		// pr($event->action);
		// pr($this->controller->request->params);
		// return true;
	}

	public function viewBeforeAction($event) {

		$this->ControllerAction->setFieldOrder([
			'position_no', 'staff_position_title_id', 
			'staff_position_grade_id', 'type', 'status',
			'modified_user_id', 'modified', 'created_user_id', 'created',
			'current_staff_list', 'past_staff_list'
		]);

		$viewVars = $this->ControllerAction->vars();
		$id = $viewVars['_buttons']['view']['url'][1];

		$session = $this->controller->request->session();

		// start Current Staff List field
		$Staff = $this->Institutions->InstitutionSiteStaff;
		$currentStaff = $Staff ->find('all')
							->where([$Staff->aliasField('end_date').' IS NULL'])
							->order([$Staff->aliasField('start_date')])
							->find('withBelongsTo')
							->find('byPosition', ['InstitutionSitePositions.id'=>$id])
							->find('byInstitution', ['Institutions.id'=>$session->read('Institutions.id')])
							;

		$this->fields['current_staff_list']['data'] = $currentStaff;
		$totalCurrentFTE = '0.00';
		if (count($currentStaff)>0) {
			foreach ($currentStaff as $cs) {
				$totalCurrentFTE = number_format((floatVal($totalCurrentFTE) + floatVal($cs->FTE)),2);
			}
		}
		$this->fields['current_staff_list']['totalCurrentFTE'] = $totalCurrentFTE;
		// end Current Staff List field

		// start Current Staff List field
		$pastStaff = $Staff ->find('all')
							->where([$Staff->aliasField('end_date').' IS NOT NULL'])
							->order([$Staff->aliasField('start_date')])
							->find('withBelongsTo')
							->find('byPosition', ['InstitutionSitePositions.id'=>$id])
							->find('byInstitution', ['Institutions.id'=>$session->read('Institutions.id')])
							;

		$this->fields['past_staff_list']['data'] = $pastStaff;
		// end Current Staff List field

		return true;
	}

	public function addBeforeAction($event) {
	}

	/**
	 * Used by InstitutionSiteStaff.add
	 * @param  boolean $institutionId [description]
	 * @param  boolean $status        [description]
	 * @return [type]                 [description]
	 */
	public function getInstitutionSitePositionList($institutionId = false, $status = false) {
		$data = $this->find();

		if ($institutionId !== false) {
			$data->where(['institution_site_id' => $institutionId]);
		}

		if ($status !== false) {
			$data->where(['status' => $status]);
		}

		$list = array();
		if (is_object($data)) {
			
			$staffOptions = $this->StaffPositionTitles->getList();
			$staffOptions = (is_object($staffOptions))? $staffOptions->toArray(): [];

			// pr($staffOptions);
			
			foreach ($data as $posInfo) {
				$list[$posInfo['id']] = sprintf('%s - %s', 
					$posInfo->position_no, 
					$staffOptions[$posInfo->staff_position_title_id]
					);
			}
		}
		return $list;
	}

}
