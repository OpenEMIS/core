<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Validation\Validator;
use User\Model\Table\UsersTable as BaseTable;

class StaffTable extends BaseTable {
	public $fteOptions = array(5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55, 60, 65, 70, 75, 80, 85, 90, 95, 100);
	
	public function initialize(array $config) {
		parent::initialize($config);
		$this->entityClass('User.User');
		$this->addBehavior('Staff.Staff');
		$this->addBehavior('User.Mandatory', ['userRole' => 'Staff', 'roleFields' =>['Identities', 'Nationalities', 'Contacts', 'SpecialNeeds']]);
		$this->addBehavior('Institution.User', ['associatedModel' => $this->InstitutionSiteStaff]);
	}

	public function indexBeforeAction(Event $event, Query $query, ArrayObject $settings) {
		parent::indexBeforeAction($event, $query, $settings);

		$settings['model'] = 'Institution.InstitutionSiteStaff';

		$this->ControllerAction->field('position', []);
		$this->ControllerAction->setFieldOrder(['photo_content', 'openemis_no', 
			'name', 'default_identity_type', 'position', 'staffstatus']);
	}

	public function onGetPosition(Event $event, Entity $entity) {
		return $entity->position->staff_position_title->name;
	}

	public function onGetStaffstatus(Event $event, Entity $entity) {
		return $entity->staff_status->name;
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$session = $request->session();
		$institutionId = $session->read('Institutions.id');

		$query->contain([
			'Positions.StaffPositionTitles',
			'StaffStatuses',
			'Users'
		])
		->group(['InstitutionSiteStaff.security_user_id']);
	}

	public function addBeforeAction(Event $event) {
		if (array_key_exists('new', $this->request->query)) {

		} else {
			$session = $this->request->session();
			$institutionSiteId = $session->read('Institutions.id');
			$associationString = $this->alias().'.'.$this->InstitutionSiteStaff->table().'.0.';
			$this->ControllerAction->field('institution_site_id', ['type' => 'hidden', 'value' => $institutionSiteId, 'fieldName' => $associationString.'institution_site_id']);			

			$this->ControllerAction->field('institution_site_position_id', ['fieldName' => $associationString.'institution_site_position_id']);
			$this->ControllerAction->field('security_role_id', ['fieldName' => $associationString.'security_role_id']);
			$this->ControllerAction->field('start_date', ['fieldName' => $associationString.'start_date']);
			$this->ControllerAction->field('FTE', ['fieldName' => $associationString.'FTE']);
			$this->ControllerAction->field('staff_type_id', ['fieldName' => $associationString.'staff_type_id']);
			$this->ControllerAction->field('start_date', ['type' => 'Date', 'fieldName' => $associationString.'start_date']);
			$this->ControllerAction->field('staff_status_id', ['fieldName' => $associationString.'staff_status_id']);
			$this->ControllerAction->field('search',['type' => 'autocomplete', 
														     'placeholder' => 'openEMIS ID or Name',
														     'url' => '/Institutions/Staff/autoCompleteUserList',
														     'length' => 3 ]);
			$this->ControllerAction->setFieldOrder([
				'institution_site_position_id', 'security_role_id', 'start_date', 'FTE', 'staff_type_id', 'staff_status_id'
				, 'search'
				]);

		}
	}

	public function autoCompleteUserList() {
		if ($this->request->is('ajax')) {
			$this->layout = 'ajax';
			$this->autoRender = false;
			$this->ControllerAction->autoRender = false;
			$term = $this->ControllerAction->request->query('term');
			$search = "";
			if(isset($term)){
				$search = '%'.$term.'%';
			}

			$conditions = array(
				'OR' => array(
					'Users.openemis_no LIKE' => $search,
					'Users.first_name LIKE' => $search,
					'Users.middle_name LIKE' => $search,
					'Users.third_name LIKE' => $search,
					'Users.last_name LIKE' => $search
				)
			);

			$list = $this->InstitutionSiteStaff
					->find('all')
					->contain(['Users'])
					->where($conditions)
					;

			$session = $this->request->session();
			if ($session->check($this->controller->name.'.'.$this->alias)) {
				$filterData = $session->read($this->controller->name.'.'.$this->alias);
				// need to form an exclude list
				$excludeQuery = $this->InstitutionSiteStaff
					->find()
					->select(['security_user_id'])
					->where(
						[
							'AND' => $filterData
						]
					)
					->group('security_user_id')
				;
				$excludeList = [];
				foreach ($excludeQuery as $key => $value) {
					$excludeList[] = $value->security_user_id;
				}
				
				if(!empty($excludeList)) {
					$list->where([$this->InstitutionSiteStaff->aliasField('security_user_id').' NOT IN' => $excludeList]);
				}
			}
			
			$list
				->group('Users.id')
				->order(['Users.first_name asc']);

			$data = array();
			foreach ($list as $obj) {
				$data[] = array(
					'label' => $obj->user->nameWithId,
					'value' =>  $obj->user->id
				);
			}
			
			echo json_encode($data);
			die;
		}
	}	
	public function onUpdateFieldInstitutionSitePositionId(Event $event, array $attr, $action, $request) {
		$session = $this->request->session();
		$institutionSiteId = $session->read('Institutions.id');

		$InstitutionSitePositions = TableRegistry::get('Institution.InstitutionSitePositions');
		$list = $InstitutionSitePositions->getInstitutionSitePositionList($institutionSiteId, true);

		$attr['type'] = 'select';
		$attr['options'] = $list;
		if (empty($attr['options'])) {
			$this->ControllerAction->Alert->warning('Institution.InstitutionSiteStaff.institutionSitePositionId');
		}
		$attr['onChangeReload'] = true;

		return $attr;
	}

	public function onUpdateFieldSecurityRoleId(Event $event, array $attr, $action, $request) {
		$session = $this->request->session();
		$institutionSiteId = $session->read('Institutions.id');

		$attr['type'] = 'select';

		$data = $this->SecurityRoles
			->find('ByInstitution', ['id' => $institutionSiteId])
			;

		$optionsList = [];
		foreach ($data as $key => $value) {
			$optionsList[$value->id] = $value->name;
		}
		$attr['options'] = $optionsList;

		if (empty($attr['options'])) {
			$this->ControllerAction->Alert->warning('Institution.InstitutionSiteStaff.securityRoleId');
		}

		return $attr;
	}

	public function onUpdateFieldStartDate(Event $event, array $attr, $action, $request) {
		$attr['onChangeReload'] = true;
		return $attr;
	}

	public function onUpdateFieldFTE(Event $event, array $attr, $action, $request) {
		if (array_key_exists('institution_site_position_id', $this->fields)) {
			if (array_key_exists('options', $this->fields['institution_site_position_id'])) {
				$positionId = key($this->fields['institution_site_position_id']['options']);
				if (array_key_exists($this->alias(), $this->request->data)) {
					if (array_key_exists('institution_site_position_id', $this->request->data[$this->alias()][$this->InstitutionSiteStaff->table()][0])) {
						if ($this->request->data[$this->alias()][$this->InstitutionSiteStaff->table()][0]['institution_site_position_id']) {
							$positionId = $this->request->data[$this->alias()][$this->InstitutionSiteStaff->table()][0]['institution_site_position_id'];
						}
					}
				}
			}
		}

		// this is used for staffTable autocomplete - for filtering of staff that are (in institution and of same position)
		$session = $this->request->session();
		$session->delete($this->controller->name.'.'.$this->alias);
		if ($positionId) {
			$institutionSiteId = $session->read('Institutions.id');
			$session->write($this->controller->name.'.'.$this->alias.'.'.'institution_site_id', $institutionSiteId);
			$session->write($this->controller->name.'.'.$this->alias.'.'.'institution_site_position_id', $positionId);
		}

		$startDate = null;
		if (array_key_exists($this->alias(), $this->request->data)) {
			if (array_key_exists('start_date', $this->request->data[$this->alias()][$this->InstitutionSiteStaff->table()][0])) {
				if ($this->request->data[$this->alias()][$this->InstitutionSiteStaff->table()][0]['start_date']) {
					$startDate = $this->request->data[$this->alias()][$this->InstitutionSiteStaff->table()][0]['start_date'];
				}
			}
		}

		$attr['type'] = 'select';
		$attr['options'] = $this->getFTEOptions($positionId, ['startDate' => $startDate]);
		if (empty($attr['options'])) {
			$attr['attr']['empty'] = __('No available FTE');
			$this->ControllerAction->Alert->warning('Institution.InstitutionSiteStaff.FTE');
		}
		return $attr;
	}

	public function getFTEOptions($positionId, $options = []) {
		$options['showAllFTE'] = !empty($options['showAllFTE']) ? $options['showAllFTE'] : false;
		$options['includeSelfNum'] = !empty($options['includeSelfNum']) ? $options['includeSelfNum'] : false;
		$options['FTE_value'] = !empty($options['FTE_value']) ? $options['FTE_value'] : 0;
		$options['startDate'] = !empty($options['startDate']) ? date('Y-m-d', strtotime($options['startDate'])) : null;
		$options['endDate'] = !empty($options['endDate']) ? date('Y-m-d', strtotime($options['endDate'])) : null;
		$currentFTE = !empty($options['currentFTE']) ? $options['currentFTE'] : 0;

		if ($options['showAllFTE']) {
			foreach ($this->fteOptions as $obj) {
				$filterFTEOptions[$obj] = $obj;
			}
		} else {
			$query = $this->InstitutionSiteStaff->find();
			$query->where(['AND' => ['institution_site_position_id' => $positionId]]);

			if (!empty($options['startDate'])) {
				$query->where(['AND' => ['OR' => [
					'end_date >= ' => $options['startDate'],
					'end_date is null'
					]]]);
			}

			if (!empty($options['endDate'])) {
				$query->where(['AND' => ['start_date <= ' => $options['endDate']]]);
			}

			$query->select([
					// todo:mlee unable to implement 'COALESCE(SUM(FTE),0) as totalFTE'
					'totalFTE' => $query->func()->sum('FTE'),
					'institution_site_position_id'
				])
				->group('institution_site_position_id')
			;

			if (is_object($query)) {
				$data = $query->toArray();
				$totalFTE = empty($data[0]->totalFTE) ? 0 : $data[0]->totalFTE * 100;
				$remainingFTE = 100 - intval($totalFTE);
				$remainingFTE = ($remainingFTE < 0) ? 0 : $remainingFTE;

				if ($options['includeSelfNum']) {
					$remainingFTE +=  $options['FTE_value'];
				}
				$highestFTE = (($remainingFTE > $options['FTE_value']) ? $remainingFTE : $options['FTE_value']);

				$filterFTEOptions = [];

				foreach ($this->fteOptions as $obj) {
					if ($highestFTE >= $obj) {
						$objLabel = number_format($obj / 100, 2);
						$filterFTEOptions[$obj] = $objLabel;
					}
				}

				if(!empty($currentFTE) && !in_array($currentFTE, $filterFTEOptions)){
					if($remainingFTE > 0) {
						$newMaxFTE = $currentFTE + $remainingFTE;
					}else{
						$newMaxFTE = $currentFTE;
					}
					
					foreach ($this->fteOptions as $obj) {
						if ($obj <= $newMaxFTE) {
							$objLabel = number_format($obj / 100, 2);
							$filterFTEOptions[$obj] = $objLabel;
						}
					}
				}

			}

			if (count($filterFTEOptions)==0) {
				$filterFTEOptions = [];
			}
		}
		return $filterFTEOptions;
	}

	public function onUpdateFieldStaffTypeId(Event $event, array $attr, $action, $request) {
		$attr['type'] = 'select';
		$attr['options'] = $this->InstitutionSiteStaff->StaffTypes->getList();
		if (empty($attr['options'])){
			$this->ControllerAction->Alert->warning('Institution.InstitutionSiteStaff.staffTypeId');
		}
		
		return $attr;
	}

	public function onUpdateFieldStaffStatusId(Event $event, array $attr, $action, $request) {
		$attr['type'] = 'select';
		$attr['options'] = $this->InstitutionSiteStaff->StaffStatuses->getList();
		if (empty($attr['options'])){
			$this->ControllerAction->Alert->warning('Institution.InstitutionSiteStaff.staffTypeId');
		}
		
		return $attr;
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		$buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

		foreach (['view', 'edit'] as $value) {
			$buttons[$value]['url'][1] = $entity->security_user_id;
		}
		
		return $buttons;
	}

	public function addOnReload(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$newOptions = [];
		$options['associated'] = ['InstitutionSiteStaff' => ['validate' => false]];
		
		$arrayOptions = $options->getArrayCopy();
		$arrayOptions = array_merge_recursive($arrayOptions, $newOptions);
		$options->exchangeArray($arrayOptions);
	}

}