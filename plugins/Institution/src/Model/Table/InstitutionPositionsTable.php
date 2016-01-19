<?php
namespace Institution\Model\Table;

use DateTime;
use DateInterval;
use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;

class InstitutionPositionsTable extends AppTable {
	use OptionsTrait;
	public $institutionId = 0;
	
	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('StaffPositionTitles', ['className' => 'Institution.StaffPositionTitles']);
		$this->belongsTo('StaffPositionGrades', ['className' => 'Institution.StaffPositionGrades']);
		$this->belongsTo('Institutions', 		['className' => 'Institution.Institutions']);

		$this->hasMany('InstitutionStaff', 		['className' => 'Institution.Staff']);
		$this->hasMany('StaffPositions', 		['className' => 'Staff.Positions']);
		$this->hasMany('StaffAttendances', 		['className' => 'Institution.StaffAttendances']);
	}

	public function validationDefault(Validator $validator) {
		return $validator
			->add('position_no', 'ruleUnique', [
				'rule' => 'validateUnique', 
				'provider' => 'table'
			])
			;
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
	}

	public function onUpdateFieldPositionNo(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			$attr['attr']['value'] = $this->getUniquePositionNo();
			return $attr;
		}
	}

	public function getUniquePositionNo() {
		$prefix = '';
		$currentStamp = time();
		$institutionId = $this->Session->read('Institution.Institutions.id');
		$institutionCode = $this->Institutions->get($institutionId)->code;
		$prefix .= $institutionCode;
		$newStamp = $currentStamp;
		return $prefix.'-'.$newStamp;
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

/******************************************************************************************************************
**
** addEdit action methods
**
******************************************************************************************************************/

	public function addEditBeforeAction(Event $event) {

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

	public function viewBeforeAction(Event $event) {

		$this->ControllerAction->setFieldOrder([
			'position_no', 'staff_position_title_id', 
			'staff_position_grade_id', 'type', 'status',
			'modified_user_id', 'modified', 'created_user_id', 'created',
			'current_staff_list', 'past_staff_list'
		]);

		$session = $this->controller->request->session();
		$pass = $this->request->param('pass');
		if (is_array($pass) && !empty($pass)) {
			$id = $pass[1];
		}
		if (!isset($id)) {
			if ($session->check($this->registryAlias() . '.id')) {
				$id = $session->read($this->registryAlias() . '.id');
			}
		}

		if (!isset($id)) {
			die('no position id specified');
		}
		// pr($id);die;
		// start Current Staff List field
		$Staff = $this->Institutions->Staff;
		$currentStaff = $Staff ->findAllByInstitutionIdAndInstitutionPositionId($session->read('Institution.Institutions.id'), $id)
							->where(['('.$Staff->aliasField('end_date').' IS NULL OR ('.$Staff->aliasField('end_date').' IS NOT NULL AND '.$Staff->aliasField('end_date').' >= DATE(NOW())))'])
							->order([$Staff->aliasField('start_date')])
							->find('withBelongsTo');

		$this->fields['current_staff_list']['data'] = $currentStaff;
		$totalCurrentFTE = '0.00';
		if (count($currentStaff)>0) {
			foreach ($currentStaff as $cs) {
				$totalCurrentFTE = number_format((floatVal($totalCurrentFTE) + floatVal($cs->FTE)),2);
			}
		}
		$this->fields['current_staff_list']['totalCurrentFTE'] = $totalCurrentFTE;
		// end Current Staff List field

		// start PAST Staff List field
		$pastStaff = $Staff ->findAllByInstitutionIdAndInstitutionPositionId($session->read('Institution.Institutions.id'), $id)
							->where([$Staff->aliasField('end_date').' IS NOT NULL'])
							->andWhere([$Staff->aliasField('end_date').' < DATE(NOW())'])
							->order([$Staff->aliasField('start_date')])
							->find('withBelongsTo');

		$this->fields['past_staff_list']['data'] = $pastStaff;
		// end Current Staff List field

		return true;
	}

    public function viewAfterAction(Event $event, Entity $entity) {
    	$this->fields['created_user_id']['options'] = [$entity->created_user_id => $entity->created_user->name];
    	if (!empty($entity->modified_user_id)) {
	    	$this->fields['modified_user_id']['options'] = [$entity->modified_user_id => $entity->modified_user->name];
	    }
		return $entity;
    }


/******************************************************************************************************************
**
** view action methods
**
******************************************************************************************************************/
	public function addBeforeAction($event) {
	}

	/**
	 * Used by Staff.add
	 * @param  boolean $institutionId [description]
	 * @param  boolean $status        [description]
	 * @return [type]                 [description]
	 */
	public function getInstitutionPositionList($institutionId = false, $status = false) {
		$data = $this->find();

		if ($institutionId !== false) {
			$data->where(['institution_id' => $institutionId]);
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

	public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $options) {
		$institutionId = $this->Session->read('Institution.Institutions.id');
		$query->where([$this->aliasField('institution_id') => $institutionId]);
	}


/******************************************************************************************************************
**
** essential methods
**
******************************************************************************************************************/
	public function findWithBelongsTo(Query $query, array $options) {
		return $query
			->contain(['StaffPositionTitles', 'Institutions', 'StaffPositionGrades']);
	}

}
