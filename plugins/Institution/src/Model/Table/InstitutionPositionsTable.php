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
	public $CAVersion = '4.0';
	
	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
		$this->belongsTo('StaffPositionTitles', ['className' => 'Institution.StaffPositionTitles']);
		$this->belongsTo('StaffPositionGrades', ['className' => 'Institution.StaffPositionGrades']);
		$this->belongsTo('Institutions', 		['className' => 'Institution.Institutions']);

		$this->hasMany('InstitutionStaff', 		['className' => 'Institution.Staff']);
		$this->hasMany('StaffPositions', 		['className' => 'Staff.Positions']);
		$this->hasMany('StaffAttendances', 		['className' => 'Institution.StaffAttendances']);

		$this->addBehavior('OpenEmis.OpenEmis');
		$this->addBehavior('ControllerAction.ControllerAction', [
			'actions' => ['remove' => 'transfer'],
			'fields' => ['excludes' => ['modified_user_id', 'created_user_id']]
		]);
	}

	public function validationDefault(Validator $validator) {
		return $validator
			->add('position_no', 'ruleUnique', [
				'rule' => 'validateUnique', 
				'provider' => 'table'
			])
			;
	}

	public function onWorkflowUpdateRoles(Event $event) {
		if (!$this->AccessControl->isAdmin() && $this->Session->check('Institution.Institutions.id') && $this->controller->name == 'Institutions') {
			$userId = $this->Auth->user('id');
			$institutionId = $this->Session->read('Institution.Institutions.id');
			return $this->Institutions->getInstitutionRoles($userId, $institutionId);
		}
	}

	public function beforeAction(Event $event, ArrayObject $extra) {
		$this->field('position_no', ['visible' => true]);
		$this->field('staff_position_title_id', [
			'visible' => true,
			'type' => 'select'
		]);
		$this->field('staff_position_grade_id', [
			'visible' => true,
			'type' => 'select'
		]);
		$this->field('current_staff_list', [
			'label' => '',
			'override' => true,
			'type' => 'element', 
			'element' => 'Institution.Positions/current',
			'visible' => true
		]);
		$this->field('past_staff_list', [
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

	public function onGetStaffPositionTitleId(Event $event, Entity $entity) {
		$types = $this->getSelectOptions('Staff.position_types');
   		if ($entity->has('staff_position_title')) {
			return $this->fields['staff_position_title_id']['options'][$entity->staff_position_title->id];
   		}
	}

   	public function onUpdateFieldStaffPositionTitleId(Event $event, array $attr, $action, $request) {
   		$types = $this->getSelectOptions('Staff.position_types');
		$titles = new ArrayObject();
		if (in_array($action, ['add', 'edit'])) {
			$this->StaffPositionTitles
					->find()
				    ->where(['id >' => 1])
				    ->order(['order'])
				    ->map(function ($row) use ($types, $titles) { // map() is a collection method, it executes the query
				        $type = array_key_exists($row->type, $types) ? $types[$row->type] : $row->type;
				        $titles[$type][$row->id] = $row->name;
				        return $row;
				    })
				    ->toArray(); // Also a collections library method
			$titles = $titles->getArrayCopy();
		} else {
			$titles = $this->StaffPositionTitles
							->find()
						    ->where(['id >' => 1])
						    ->order(['order'])
						    ->map(function ($row) use ($types) { // map() is a collection method, it executes the query
						        $row->name_and_type = $row->name . ' - ' . (array_key_exists($row->type, $types) ? $types[$row->type] : $row->type);
						        return $row;
						    })
						    ->combine('id', 'name_and_type') // combine() is another collection method
						    ->toArray(); // Also a collections library method
		}
		$attr['options'] = $titles;
		return $attr;
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
	public function indexBeforeAction(Event $event, ArrayObject $extra) {
		$this->fields['current_staff_list']['visible'] = false;
		$this->fields['past_staff_list']['visible'] = false;

		$this->fields['staff_position_title_id']['sort'] = ['field' => 'StaffPositionTitles.order'];
		$this->fields['staff_position_grade_id']['sort'] = ['field' => 'StaffPositionGrades.order'];

		$this->setFieldOrder([
			'position_no', 'staff_position_title_id', 
			'staff_position_grade_id',
		]);

		if ($extra['auto_search']) {
			$search = $this->getSearchKey();
			if (!empty($search)) {
				$extra['OR'] = [$this->StaffPositionTitles->aliasField('name').' LIKE' => '%' . $search . '%'];
			}
		}
	}

	public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
		// pr('model - indexBeforeQuery');
		$extra['auto_contain'] = false;
		$extra['auto_order'] = false;

		$query->contain(['Statuses', 'StaffPositionTitles', 'StaffPositionGrades', 'Institutions'])
			->autoFields(true);

		$sortList = ['position_no', 'StaffPositionTitles.order', 'StaffPositionGrades.order'];
		if (array_key_exists('sortWhitelist', $extra['options'])) {
			$sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
		}
		$extra['options']['sortWhitelist'] = $sortList;
	}

/******************************************************************************************************************
**
** addEdit action methods
**
******************************************************************************************************************/

	public function addEditBeforeAction(Event $event) {

		$this->fields['current_staff_list']['visible'] = false;
		$this->fields['past_staff_list']['visible'] = false;

		$this->setFieldOrder([
			'position_no', 'staff_position_title_id', 
			'staff_position_grade_id',
		]);

	}

/******************************************************************************************************************
**
** view action methods
**
******************************************************************************************************************/

	public function viewBeforeAction(Event $event) {

		$this->setFieldOrder([
			'position_no', 'staff_position_title_id', 
			'staff_position_grade_id',
			'modified_user_id', 'modified', 'created_user_id', 'created',
			'current_staff_list', 'past_staff_list'
		]);

		$session = $this->Session;
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
** add action methods
**
******************************************************************************************************************/

	public function transferOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $options) {
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

	public function onWorkflowUpdateRoles(Event $event) {
		if (!$this->AccessControl->isAdmin() && $this->Session->check('Institution.Institutions.id')) {
			$userId = $this->Auth->user('id');
			$institutionId = $this->Session->read('Institution.Institutions.id');
			return $this->Institutions->getInstitutionRoles($userId, $institutionId);
		}
	}
}
