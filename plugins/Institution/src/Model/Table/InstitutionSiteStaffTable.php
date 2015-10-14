<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use App\Model\Table\AppTable;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;


class InstitutionSiteStaffTable extends AppTable {
	public $fteOptions = array(5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55, 60, 65, 70, 75, 80, 85, 90, 95, 100);

	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('Users', 		 ['className' => 'User.Users', 							'foreignKey' => 'security_user_id']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 			'foreignKey' => 'institution_site_id']);
		$this->belongsTo('Positions', 	 ['className' => 'Institution.InstitutionSitePositions','foreignKey' => 'institution_site_position_id']);
		$this->belongsTo('StaffTypes', 	 ['className' => 'FieldOption.StaffTypes', 				'foreignKey' => 'staff_type_id']);
		$this->belongsTo('StaffStatuses',['className' => 'FieldOption.StaffStatuses', 			'foreignKey' => 'staff_status_id']);

		$this->addBehavior('AcademicPeriod.Period');
	}

	public function validationDefault(Validator $validator) {
		return $validator
			// this function doesnt update... only adds
			->requirePresence('staff_status_id', 'update')
			->add('institution_site_position_id', [
			])
			->add('security_role_id', [
			])
			->add('FTE', [
			])
			->add('staff_type_id', [
			])
		;
	}

	public function beforeSave(Event $event, Entity $entity, ArrayObject $options) {
		parent::beforeSave($event, $entity, $options);
		
		if ($entity->isNew()) {
			if (isset($entity->FTE)) {
				$entity->FTE = $entity->FTE/100;
			}
		}
	}


/******************************************************************************************************************
**
** add action functions
**
******************************************************************************************************************/
	public function addAfterPatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$timeNow = strtotime("now");
		$sessionVar = $this->alias().'.add.'.strtotime("now");
		$this->Session->write($sessionVar, $this->request->data);

		if (!$entity->errors()) {
			$event->stopPropagation();
			return $this->controller->redirect(['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'add'.'?new='.$timeNow]);
		}
	}


/******************************************************************************************************************
**
** finders functions to be used with query
**
******************************************************************************************************************/
	/**
	 * $options['type'] == 0 > non-teaching
	 * $options['type'] == 1 > teaching
	 * refer to OptionsTrait
	 */
	public function findByPositions(Query $query, array $options) {
		if (array_key_exists('Institutions.id', $options) && array_key_exists('type', $options)) {
			$positions = $this->Positions->find('list')
						->find('withBelongsTo')
				        ->where([
				        	'Institutions.id' => $options['Institutions.id'],
				        	$this->Positions->aliasField('type') => $options['type']
				        ])
				        ->toArray();
			$positions = array_keys($positions);
			return $query->where([$this->aliasField('institution_site_position_id IN') => $positions]);
		} else {
			return $query;
		}
	}

	public function findByInstitution(Query $query, array $options) {
		if (array_key_exists('Institutions.id', $options)) {
			return $query->where([$this->aliasField('institution_site_id') => $options['Institutions.id']]);
		} else {
			return $query;
		}
	}

	/**
	 * currently available values:
	 * 	Full-Time
	 * 	Part-Time
	 * 	Contract
	 */
	public function findByType(Query $query, array $options) {
		if (array_key_exists('type', $options)) {
			$types = $this->StaffTypes->getList()->toArray();
			if (is_array($types) && in_array($options['type'], $types)) {
				$typeId = array_search($options['type'], $types);
				return $query->where([$this->aliasField('staff_type_id') => $typeId]);
			} else {
				return $query;
			}
		} else {
			return $query;
		}
	}

	/**
	 * currently available values:
	 * 	Current
	 * 	Transferred
	 * 	Resigned
	 * 	Leave
	 * 	Terminated
	 */
	public function findByStatus(Query $query, array $options) {
		if (array_key_exists('status', $options)) {
			$statuses = $this->StaffStatuses->getList()->toArray();
			if (is_array($statuses) && in_array($options['status'], $statuses)) {
				$statusId = array_search($options['status'], $statuses);
				return $query->where([$this->aliasField('staff_status_id') => $statusId]);
			} else {
				return $query;
			}
		} else {
			return $query;
		}
	}

	public function findWithBelongsTo(Query $query, array $options) {
		return $query
			->contain(['Users', 'Institutions', 'Positions', 'StaffTypes', 'StaffStatuses']);
	}


/******************************************************************************************************************
**
** essential functions
**
******************************************************************************************************************/

}
